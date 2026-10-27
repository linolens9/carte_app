<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Services\PhotoService;
use Throwable;

class EleveController extends Controller
{
    private Eleve $eleveModel;
    private Classe $classeModel;
    private Etablissement $etablissementModel;
    private PhotoService $photoService;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->eleveModel = new Eleve();
        $this->classeModel = new Classe();
        $this->etablissementModel = new Etablissement();
        $this->photoService = new PhotoService();
    }

    public function index(): void
    {
        $activeEtab = Auth::getActiveEtablissement();
        $isHigherEd = $activeEtab && in_array($activeEtab['type'] ?? '', ['universite', 'centre_formation'], true);
        if ($isHigherEd && !Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('info', 'Votre établissement gère des étudiants et non des élèves.');
            $this->redirect('/etudiants');
            return;
        }

        $activeEtabId = Auth::getActiveEtablissementId();
        $etabId = $this->request->query('etablissement_id') ? (int)$this->request->query('etablissement_id') : $activeEtabId;

        $filters = [
            'etablissement_id' => $etabId,
            'classe_id' => $this->request->query('classe_id'),
            'niveau' => $this->request->query('niveau'),
            'annee_scolaire' => $this->request->query('annee_scolaire'),
            'sexe' => $this->request->query('sexe'),
            'statut_photo' => $this->request->query('statut_photo'),
        ];

        $search = trim((string)$this->request->query('q', ''));
        $page = max(1, (int)$this->request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $accessibleEtabs = Auth::getAccessibleEtablissements('scolaire');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');

        if (!Auth::hasRole('admin')) {
            if (!$etabId || !in_array($etabId, $allowedEtabIds, true)) {
                $etabId = ($activeEtabId && in_array($activeEtabId, $allowedEtabIds, true)) ? $activeEtabId : ($allowedEtabIds[0] ?? null);
            }
            $filters['etablissement_id'] = $etabId;
        }

        $eleves = $this->eleveModel->getAllFiltered($filters, $search, $limit, $offset);
        $total = $this->eleveModel->countFiltered($filters, $search);
        $totalPages = (int)ceil($total / $limit);

        // Données annexes pour les filtres (uniquement les établissements accessibles)
        $etablissements = $accessibleEtabs;
        $classes = $etabId ? $this->classeModel->getByEtablissement($etabId) : [];

        $this->render('eleves.index', [
            'eleves' => $eleves,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'search' => $search,
            'etablissements' => $etablissements,
            'classes' => $classes,
            'selectedEtabId' => $etabId,
        ]);
    }

    public function create(): void
    {
        $activeEtab = Auth::getActiveEtablissement();
        $isHigherEd = $activeEtab && in_array($activeEtab['type'] ?? '', ['universite', 'centre_formation'], true);
        if ($isHigherEd && !Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('info', 'Votre établissement gère des étudiants.');
            $this->redirect('/etudiants/creer');
            return;
        }

        $etablissements = Auth::getAccessibleEtablissements('scolaire');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');
        $activeEtabId = Auth::getActiveEtablissementId();

        if ($activeEtabId === null || !in_array($activeEtabId, $allowedEtabIds, true)) {
            $activeEtabId = $allowedEtabIds[0] ?? null;
        }

        $classes = $activeEtabId ? $this->classeModel->getByEtablissement($activeEtabId) : [];

        $this->render('eleves.create', [
            'etablissements' => $etablissements,
            'classes' => $classes,
            'activeEtabId' => $activeEtabId,
        ]);
    }

    public function store(): void
    {
        $data = $this->request->post();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');
        $defaultEtabId = in_array(Auth::getActiveEtablissementId(), $allowedEtabIds, true) ? Auth::getActiveEtablissementId() : ($allowedEtabIds[0] ?? null);
        $etabId = (int)($data['etablissement_id'] ?? $defaultEtabId);

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            $errors['etablissement_id'][] = "Vous n'êtes pas autorisé à inscrire un élève pour cet établissement.";
        }

        $rules = [
            'etablissement_id' => 'required',
            'classe_id' => 'required',
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required',
            'nationalite' => 'required',
            'niveau' => 'required',
            'annee_scolaire' => 'required',
        ];

        $errors = $this->validate($data, $rules);

        // Contrôle des doublons (nom + prénom + date_naissance)
        if (empty($errors['nom']) && empty($errors['prenom']) && empty($errors['date_naissance'])) {
            $duplicate = $this->eleveModel->checkDuplicate($etabId, (string)$data['nom'], (string)$data['prenom'], (string)$data['date_naissance']);
            if ($duplicate) {
                $errors['nom'][] = "Un élève avec ce nom, prénom et date de naissance existe déjà (Matricule : {$duplicate['matricule']}).";
            }
        }

        // Gestion de la photo (Webcam / Cropper ou upload classique)
        $photoPath = null;
        try {
            if (!empty($data['photo_base64'])) {
                $photoPath = $this->photoService->processBase64((string)$data['photo_base64']);
            } elseif ($this->request->file('photo_file') && $this->request->file('photo_file')['error'] === UPLOAD_ERR_OK) {
                $photoPath = $this->photoService->processUploadedFile($this->request->file('photo_file'));
            }
        } catch (Throwable $e) {
            $errors['photo'][] = "Erreur photo : " . $e->getMessage();
        }

        if (!empty($errors)) {
            $etablissements = Auth::getAccessibleEtablissements('scolaire');
            $classes = $etabId ? $this->classeModel->getByEtablissement($etabId) : [];
            $this->render('eleves.create', [
                'errors' => $errors,
                'old' => $data,
                'etablissements' => $etablissements,
                'classes' => $classes,
                'activeEtabId' => $etabId,
            ]);
            return;
        }

        // Génération de matricule automatique si vide
        $matricule = trim((string)($data['matricule'] ?? ''));
        if ($matricule === '') {
            $matricule = $this->eleveModel->generateMatricule($etabId);
        }

        $id = $this->eleveModel->create([
            'etablissement_id' => $etabId,
            'classe_id' => (int)$data['classe_id'],
            'matricule' => strtoupper($matricule),
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'sexe' => (string)$data['sexe'],
            'date_naissance' => (string)$data['date_naissance'],
            'lieu_naissance' => trim((string)($data['lieu_naissance'] ?? '')),
            'nationalite' => trim((string)($data['nationalite'] ?? 'Gabonaise')),
            'niveau' => trim((string)$data['niveau']),
            'annee_scolaire' => trim((string)$data['annee_scolaire']),
            'tuteur_nom' => trim((string)($data['tuteur_nom'] ?? '')),
            'tuteur_telephone' => trim((string)($data['tuteur_telephone'] ?? '')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'photo' => $photoPath,
            'statut' => 'actif',
            'created_by' => Auth::id(),
        ]);

        Logger::log(Auth::id(), $etabId, 'eleve.creer', 'eleves', $id, null, [
            'matricule' => $matricule,
            'nom' => $data['nom'],
            'prenom' => $data['prenom']
        ], $this->request);

        Session::setFlash('success', "Élève enregistré avec succès (Matricule : $matricule).");
        $this->redirect('/eleves/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $eleve = $this->eleveModel->getWithDetails($id);

        if (!$eleve) {
            Session::setFlash('danger', "Élève introuvable.");
            $this->redirect('/eleves');
        }

        $this->render('eleves.show', ['eleve' => $eleve]);
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $eleve = $this->eleveModel->getWithDetails($id);

        if (!$eleve) {
            Session::setFlash('danger', "Élève introuvable.");
            $this->redirect('/eleves');
        }

        // Vérification des droits d'accès à l'établissement de l'élève
        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');
            if (!in_array((int)$eleve['etablissement_id'], $allowedEtabIds, true)) {
                Session::setFlash('danger', "Accès non autorisé à cet élève.");
                $this->redirect('/eleves');
            }
        }

        $classes = $this->classeModel->getByEtablissement((int)$eleve['etablissement_id']);
        $etablissements = Auth::getAccessibleEtablissements('scolaire');

        $this->render('eleves.edit', [
            'eleve' => $eleve,
            'classes' => $classes,
            'etablissements' => $etablissements,
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $eleve = $this->eleveModel->findById($id);

        if (!$eleve) {
            Session::setFlash('danger', "Élève introuvable.");
            $this->redirect('/eleves');
        }

        $data = $this->request->post();
        $etabId = (int)$eleve['etablissement_id'];

        $rules = [
            'classe_id' => 'required',
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required',
            'nationalite' => 'required',
            'niveau' => 'required',
            'annee_scolaire' => 'required',
            'statut' => 'required|in:actif,archive',
        ];

        $errors = $this->validate($data, $rules);

        // Photo mise à jour si fournie
        $photoPath = $eleve['photo'];
        try {
            if (!empty($data['photo_base64'])) {
                $photoPath = $this->photoService->processBase64((string)$data['photo_base64']);
            } elseif ($this->request->file('photo_file') && $this->request->file('photo_file')['error'] === UPLOAD_ERR_OK) {
                $photoPath = $this->photoService->processUploadedFile($this->request->file('photo_file'));
            }
        } catch (Throwable $e) {
            $errors['photo'][] = "Erreur photo : " . $e->getMessage();
        }

        if (!empty($errors)) {
            $classes = $this->classeModel->getByEtablissement($etabId);
            $etablissements = Auth::getAccessibleEtablissements('scolaire');
            $this->render('eleves.edit', [
                'eleve' => array_merge($eleve, $data),
                'classes' => $classes,
                'etablissements' => $etablissements,
                'errors' => $errors,
            ]);
            return;
        }

        $updatePayload = [
            'classe_id' => (int)$data['classe_id'],
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'sexe' => (string)$data['sexe'],
            'date_naissance' => (string)$data['date_naissance'],
            'lieu_naissance' => trim((string)($data['lieu_naissance'] ?? '')),
            'nationalite' => trim((string)($data['nationalite'] ?? 'Gabonaise')),
            'niveau' => trim((string)$data['niveau']),
            'annee_scolaire' => trim((string)$data['annee_scolaire']),
            'tuteur_nom' => trim((string)($data['tuteur_nom'] ?? '')),
            'tuteur_telephone' => trim((string)($data['tuteur_telephone'] ?? '')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'photo' => $photoPath,
            'statut' => (string)$data['statut'],
        ];

        $this->eleveModel->update($id, $updatePayload);

        Logger::log(Auth::id(), $etabId, 'eleve.modifier', 'eleves', $id, $eleve, $updatePayload, $this->request);

        Session::setFlash('success', "Fiche de l'élève mise à jour.");
        $this->redirect('/eleves/' . $id);
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $eleve = $this->eleveModel->findById($id);

        if (!$eleve) {
            Session::setFlash('danger', "Élève introuvable.");
            $this->redirect('/eleves');
        }

        $this->eleveModel->delete($id);
        Logger::log(Auth::id(), (int)$eleve['etablissement_id'], 'eleve.supprimer', 'eleves', $id, $eleve, null, $this->request);

        Session::setFlash('success', "Élève supprimé.");
        $this->redirect('/eleves');
    }

    public function importForm(): void
    {
        $etablissements = Auth::getAccessibleEtablissements('scolaire');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');
        $activeEtabId = Auth::getActiveEtablissementId();
        if ($activeEtabId === null || !in_array($activeEtabId, $allowedEtabIds, true)) {
            $activeEtabId = $allowedEtabIds[0] ?? null;
        }
        $classes = $activeEtabId ? $this->classeModel->getByEtablissement($activeEtabId) : [];

        $this->render('eleves.import', [
            'etablissements' => $etablissements,
            'classes' => $classes,
            'activeEtabId' => $activeEtabId,
        ]);
    }

    public function downloadTemplate(): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ini_set('display_errors', '0');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modele_import_eleves.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM pour compatibilité Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes CSV
        fputcsv($output, [
            'matricule',
            'nom',
            'prenom',
            'sexe',
            'date_naissance',
            'lieu_naissance',
            'nationalite',
            'classe',
            'niveau',
            'annee_scolaire',
            'tuteur_nom',
            'tuteur_telephone',
            'telephone',
            'adresse'
        ], ';', '"', "\\");

        // Ligne d'exemple
        fputcsv($output, [
            'LNLM-2026-00001',
            'OBAME',
            'Jean-Luc',
            'M',
            '2008-05-14',
            'Libreville',
            'Gabonaise',
            'Terminale D1',
            'Terminale',
            '2026-2027',
            'OBAME Pierre',
            '+241 07 12 34 56',
            '+241 06 98 76 54',
            'Quartier Glass'
        ], ';', '"', "\\");

        fclose($output);
        exit;
    }

    public function importProcess(): void
    {
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('scolaire');
        $defaultEtabId = in_array(Auth::getActiveEtablissementId(), $allowedEtabIds, true) ? Auth::getActiveEtablissementId() : ($allowedEtabIds[0] ?? null);
        $etabId = (int)$this->request->post('etablissement_id', $defaultEtabId);

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            Session::setFlash('danger', "Vous n'êtes pas autorisé à importer des élèves pour cet établissement.");
            $this->redirect('/eleves/importer');
            return;
        }
        $file = $this->request->file('csv_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('danger', "Veuillez sélectionner un fichier CSV valide.");
            $this->redirect('/eleves/importer');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            Session::setFlash('danger', "Impossible de lire le fichier.");
            $this->redirect('/eleves/importer');
        }

        // Cache des classes existantes par libellé en minuscule
        $classes = $this->classeModel->getByEtablissement($etabId);
        $classesMap = [];
        foreach ($classes as $c) {
            $classesMap[mb_strtolower(trim($c['libelle']))] = (int)$c['id'];
        }

        // Détection séparateur (, ou ;)
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = str_contains((string)$firstLine, ';') ? ';' : ',';

        $rowNum = 0;
        $successCount = 0;
        $errors = [];

        // Ignorer BOM si présent
        $header = fgetcsv($handle, 1000, $delimiter, '"', "\\");

        while (($row = fgetcsv($handle, 1000, $delimiter, '"', "\\")) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue;
            }

            $matricule = trim($row[0] ?? '');
            $nom = mb_strtoupper(trim($row[1] ?? ''), 'UTF-8');
            $prenom = mb_convert_case(trim($row[2] ?? ''), MB_CASE_TITLE, 'UTF-8');
            $sexe = strtoupper(trim($row[3] ?? 'M'));
            $dateNaiss = trim($row[4] ?? '');
            $lieuNaiss = trim($row[5] ?? '');
            $nationalite = trim($row[6] ?? 'Gabonaise');
            $classeNom = trim($row[7] ?? '');
            $niveau = trim($row[8] ?? '');
            $annee = trim($row[9] ?? date('Y') . '-' . (date('Y') + 1));
            $tuteurNom = trim($row[10] ?? '');
            $tuteurTel = trim($row[11] ?? '');
            $tel = trim($row[12] ?? '');
            $adresse = trim($row[13] ?? '');

            if ($nom === '' || $prenom === '' || $dateNaiss === '') {
                $errors[] = "Ligne $rowNum : Le nom, prénom et date de naissance sont obligatoires.";
                continue;
            }

            if (!in_array($sexe, ['M', 'F'], true)) {
                $sexe = 'M';
            }

            // Résolution de la classe
            $classeId = $classesMap[mb_strtolower($classeNom)] ?? null;
            if (!$classeId) {
                // Créer la classe automatiquement si manquante
                $classeId = $this->classeModel->create([
                    'etablissement_id' => $etabId,
                    'libelle' => $classeNom !== '' ? $classeNom : 'Classe Générale',
                    'niveau' => $niveau !== '' ? $niveau : 'Secondaire',
                    'annee_scolaire' => $annee,
                ]);
                $classesMap[mb_strtolower($classeNom)] = $classeId;
            }

            // Vérifier doublon
            if ($this->eleveModel->checkDuplicate($etabId, $nom, $prenom, $dateNaiss)) {
                $errors[] = "Ligne $rowNum : Élève « $nom $prenom » déjà présent dans cet établissement (ignoré).";
                continue;
            }

            if ($matricule === '') {
                $matricule = $this->eleveModel->generateMatricule($etabId);
            }

            try {
                $this->eleveModel->create([
                    'etablissement_id' => $etabId,
                    'classe_id' => $classeId,
                    'matricule' => strtoupper($matricule),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'sexe' => $sexe,
                    'date_naissance' => $dateNaiss,
                    'lieu_naissance' => $lieuNaiss,
                    'nationalite' => $nationalite,
                    'niveau' => $niveau !== '' ? $niveau : 'Secondaire',
                    'annee_scolaire' => $annee,
                    'tuteur_nom' => $tuteurNom,
                    'tuteur_telephone' => $tuteurTel,
                    'telephone' => $tel,
                    'adresse' => $adresse,
                    'statut' => 'actif',
                    'created_by' => Auth::id(),
                ]);
                $successCount++;
            } catch (Throwable $e) {
                $errors[] = "Ligne $rowNum : " . $e->getMessage();
            }
        }

        fclose($handle);

        Logger::log(Auth::id(), $etabId, 'eleve.import_csv', 'eleves', null, null, [
            'succes' => $successCount,
            'erreurs' => count($errors)
        ], $this->request);

        $msg = "Importation terminée : $successCount élève(s) importé(s) avec succès.";
        if (!empty($errors)) {
            $msg .= " " . count($errors) . " avertissement(s) ou erreur(s) détecté(s).";
            Session::setFlash('warning', $msg);
            Session::set('import_errors', $errors);
        } else {
            Session::setFlash('success', $msg);
        }

        $this->redirect('/eleves');
    }

    public function promoteBatch(): void
    {
        $eleveIds = $this->request->post('eleve_ids') ?? [];
        $targetClasseId = (int)$this->request->post('target_classe_id', 0);
        $targetAnnee = trim((string)$this->request->post('target_annee', ''));

        if (empty($eleveIds) || $targetClasseId === 0 || $targetAnnee === '') {
            Session::setFlash('danger', "Veuillez sélectionner au moins un élève, une classe cible et une année scolaire.");
            $this->redirect('/eleves');
        }

        $targetClasse = $this->classeModel->findById($targetClasseId);
        if (!$targetClasse) {
            Session::setFlash('danger', "Classe cible introuvable.");
            $this->redirect('/eleves');
        }

        $count = $this->eleveModel->promoteBatch(
            array_map('intval', (array)$eleveIds),
            $targetClasseId,
            $targetClasse['niveau'],
            $targetAnnee
        );

        Logger::log(Auth::id(), (int)$targetClasse['etablissement_id'], 'eleve.promotion_lot', 'eleves', null, null, [
            'nombre' => $count,
            'classe_cible' => $targetClasse['libelle'],
            'annee' => $targetAnnee
        ], $this->request);

        Session::setFlash('success', "$count élève(s) promu(s) avec succès en « {$targetClasse['libelle']} » ($targetAnnee).");
        $this->redirect('/eleves?classe_id=' . $targetClasseId);
    }
}
