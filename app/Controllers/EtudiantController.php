<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Services\PhotoService;
use Throwable;

class EtudiantController extends Controller
{
    private Etudiant $etudiantModel;
    private Filiere $filiereModel;
    private Classe $classeModel;
    private Etablissement $etablissementModel;
    private PhotoService $photoService;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->etudiantModel = new Etudiant();
        $this->filiereModel = new Filiere();
        $this->classeModel = new Classe();
        $this->etablissementModel = new Etablissement();
        $this->photoService = new PhotoService();
    }

    public function index(): void
    {
        $activeEtab = Auth::getActiveEtablissement();
        $isSchool = $activeEtab && in_array($activeEtab['type'] ?? '', ['primaire', 'college', 'lycee'], true);
        if ($isSchool && !Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('info', 'Votre établissement est de type scolaire (lycée/collège/école primaire) et gère des élèves.');
            $this->redirect('/eleves');
            return;
        }

        $activeEtabId = Auth::getActiveEtablissementId();
        $accessibleEtabs = Auth::getAccessibleEtablissements('superieur');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');

        $requestedEtabId = $this->request->query('etablissement_id');
        if ($requestedEtabId !== null && $requestedEtabId !== '') {
            $etabId = (int)$requestedEtabId;
            if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
                $etabId = ($activeEtabId && in_array($activeEtabId, $allowedEtabIds, true)) ? $activeEtabId : ($allowedEtabIds[0] ?? null);
            }
        } else {
            $etabId = ($activeEtabId && in_array($activeEtabId, $allowedEtabIds, true)) ? $activeEtabId : ($allowedEtabIds[0] ?? null);
        }

        $filters = [
            'etablissement_id' => $etabId,
            'filiere_id' => $this->request->query('filiere_id'),
            'classe_id' => $this->request->query('classe_id'),
            'niveau' => $this->request->query('niveau'),
            'annee_scolaire' => $this->request->query('annee_scolaire'),
            'sexe' => $this->request->query('sexe'),
            'statut' => $this->request->query('statut'),
            'statut_photo' => $this->request->query('statut_photo'),
        ];

        $search = trim((string)$this->request->query('q', ''));
        $page = max(1, (int)$this->request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $etudiants = $this->etudiantModel->getAllFiltered($filters, $search, $limit, $offset);
        $total = $this->etudiantModel->countFiltered($filters, $search);
        $totalPages = (int)ceil($total / $limit);

        // Données complémentaires pour les listes déroulantes de filtres
        $etablissements = $accessibleEtabs;
        $filieres = $etabId ? $this->filiereModel->getByEtablissement($etabId) : [];
        $classes = $etabId ? $this->classeModel->getByEtablissement($etabId) : [];

        $this->render('etudiants.index', [
            'etudiants' => $etudiants,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'search' => $search,
            'etablissements' => $etablissements,
            'filieres' => $filieres,
            'classes' => $classes,
            'selectedEtabId' => $etabId,
        ]);
    }

    public function create(): void
    {
        $activeEtab = Auth::getActiveEtablissement();
        $isSchool = $activeEtab && in_array($activeEtab['type'] ?? '', ['primaire', 'college', 'lycee'], true);
        if ($isSchool && !Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('info', 'Votre établissement est de type scolaire et gère des élèves.');
            $this->redirect('/eleves/creer');
            return;
        }

        $accessibleEtabs = Auth::getAccessibleEtablissements('superieur');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        $activeEtabId = Auth::getActiveEtablissementId();

        if (!Auth::hasRole('admin')) {
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
            }
        }

        $filieres = $activeEtabId ? $this->filiereModel->getByEtablissement($activeEtabId) : [];
        $classes = $activeEtabId ? $this->classeModel->getByEtablissement($activeEtabId) : [];

        $this->render('etudiants.create', [
            'etablissements' => $accessibleEtabs,
            'filieres' => $filieres,
            'classes' => $classes,
            'activeEtabId' => $activeEtabId,
        ]);
    }

    public function store(): void
    {
        $activeEtab = Auth::getActiveEtablissement();
        $isSchool = $activeEtab && in_array($activeEtab['type'] ?? '', ['primaire', 'college', 'lycee'], true);
        if ($isSchool && !Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('danger', 'Action non autorisée pour cet établissement.');
            $this->redirect('/eleves');
            return;
        }

        $data = $this->request->post();
        $accessibleEtabs = Auth::getAccessibleEtablissements('superieur');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        $activeEtabId = Auth::getActiveEtablissementId();

        $postedEtabId = !empty($data['etablissement_id']) ? (int)$data['etablissement_id'] : ($activeEtabId ?: ($allowedEtabIds[0] ?? 0));
        $etabId = $postedEtabId;

        $rules = [
            'etablissement_id' => 'required',
            'filiere_id' => 'required',
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required',
            'nationalite' => 'required',
            'niveau' => 'required',
            'annee_scolaire' => 'required',
        ];

        $errors = $this->validate($data, $rules);

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            $errors['etablissement_id'][] = "Vous n'êtes pas autorisé à inscrire un étudiant dans cet établissement.";
        }

        // Vérification des doublons (nom + prénom + date_naissance)
        if (empty($errors['nom']) && empty($errors['prenom']) && empty($errors['date_naissance'])) {
            $duplicate = $this->etudiantModel->checkDuplicate($etabId, (string)$data['nom'], (string)$data['prenom'], (string)$data['date_naissance']);
            if ($duplicate) {
                $errors['nom'][] = "Un étudiant avec ce nom, prénom et date de naissance existe déjà (Matricule : {$duplicate['matricule']}).";
            }
        }

        // Traitement de la photo d'identité (Cropper / Webcam Base64 ou upload direct)
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
            $filieres = $etabId ? $this->filiereModel->getByEtablissement($etabId) : [];
            $classes = $etabId ? $this->classeModel->getByEtablissement($etabId) : [];
            $this->render('etudiants.create', [
                'errors' => $errors,
                'old' => $data,
                'etablissements' => $accessibleEtabs,
                'filieres' => $filieres,
                'classes' => $classes,
                'activeEtabId' => $etabId,
            ]);
            return;
        }

        // Génération automatique du matricule étudiant si vide
        $matricule = trim((string)($data['matricule'] ?? ''));
        if ($matricule === '') {
            $matricule = $this->etudiantModel->generateMatricule($etabId);
        }

        $id = $this->etudiantModel->create([
            'etablissement_id' => $etabId,
            'filiere_id' => (int)$data['filiere_id'],
            'classe_id' => !empty($data['classe_id']) ? (int)$data['classe_id'] : null,
            'matricule' => strtoupper($matricule),
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'sexe' => (string)$data['sexe'],
            'date_naissance' => (string)$data['date_naissance'],
            'nationalite' => trim((string)($data['nationalite'] ?? 'Gabonaise')),
            'niveau' => trim((string)$data['niveau']),
            'annee_scolaire' => trim((string)$data['annee_scolaire']),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'photo' => $photoPath,
            'statut' => 'actif',
            'created_by' => Auth::id(),
        ]);

        Logger::log(Auth::id(), $etabId, 'etudiant.creer', 'etudiants', $id, null, [
            'matricule' => $matricule,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'filiere_id' => $data['filiere_id'],
        ], $this->request);

        Session::setFlash('success', "Étudiant inscrit avec succès (Matricule : $matricule).");
        $this->redirect('/etudiants/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etudiant = $this->etudiantModel->getWithDetails($id);

        if (!$etudiant) {
            Session::setFlash('danger', "Étudiant introuvable.");
            $this->redirect('/etudiants');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        if (!Auth::hasRole('admin') && !in_array((int)$etudiant['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à cet étudiant.");
            $this->redirect('/etudiants');
        }

        $this->render('etudiants.show', ['etudiant' => $etudiant]);
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etudiant = $this->etudiantModel->getWithDetails($id);

        if (!$etudiant) {
            Session::setFlash('danger', "Étudiant introuvable.");
            $this->redirect('/etudiants');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        if (!Auth::hasRole('admin') && !in_array((int)$etudiant['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à cet étudiant.");
            $this->redirect('/etudiants');
        }

        $etabId = (int)$etudiant['etablissement_id'];
        $filieres = $this->filiereModel->getByEtablissement($etabId);
        $classes = $this->classeModel->getByEtablissement($etabId);
        $etablissements = Auth::getAccessibleEtablissements('superieur');

        $this->render('etudiants.edit', [
            'etudiant' => $etudiant,
            'filieres' => $filieres,
            'classes' => $classes,
            'etablissements' => $etablissements,
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etudiant = $this->etudiantModel->findById($id);

        if (!$etudiant) {
            Session::setFlash('danger', "Étudiant introuvable.");
            $this->redirect('/etudiants');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        if (!Auth::hasRole('admin') && !in_array((int)$etudiant['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à cet étudiant.");
            $this->redirect('/etudiants');
        }

        $data = $this->request->post();
        $etabId = (int)$etudiant['etablissement_id'];

        $rules = [
            'filiere_id' => 'required',
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required',
            'nationalite' => 'required',
            'niveau' => 'required',
            'annee_scolaire' => 'required',
            'statut' => 'required|in:actif,diplome,archive',
        ];

        $errors = $this->validate($data, $rules);

        // Mise à jour de la photo si une nouvelle est fournie
        $photoPath = $etudiant['photo'];
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
            $filieres = $this->filiereModel->getByEtablissement($etabId);
            $classes = $this->classeModel->getByEtablissement($etabId);
            $etablissements = Auth::getAccessibleEtablissements('superieur');
            $this->render('etudiants.edit', [
                'etudiant' => array_merge($etudiant, $data),
                'filieres' => $filieres,
                'classes' => $classes,
                'etablissements' => $etablissements,
                'errors' => $errors,
            ]);
            return;
        }

        $updatePayload = [
            'filiere_id' => (int)$data['filiere_id'],
            'classe_id' => !empty($data['classe_id']) ? (int)$data['classe_id'] : null,
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'sexe' => (string)$data['sexe'],
            'date_naissance' => (string)$data['date_naissance'],
            'nationalite' => trim((string)($data['nationalite'] ?? 'Gabonaise')),
            'niveau' => trim((string)$data['niveau']),
            'annee_scolaire' => trim((string)$data['annee_scolaire']),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'photo' => $photoPath,
            'statut' => (string)$data['statut'],
        ];

        $this->etudiantModel->update($id, $updatePayload);

        Logger::log(Auth::id(), $etabId, 'etudiant.modifier', 'etudiants', $id, $etudiant, $updatePayload, $this->request);

        Session::setFlash('success', "Fiche étudiante mise à jour.");
        $this->redirect('/etudiants/' . $id);
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etudiant = $this->etudiantModel->findById($id);

        if (!$etudiant) {
            Session::setFlash('danger', "Étudiant introuvable.");
            $this->redirect('/etudiants');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        if (!Auth::hasRole('admin') && !in_array((int)$etudiant['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Action non autorisée.");
            $this->redirect('/etudiants');
        }

        $this->etudiantModel->delete($id);
        Logger::log(Auth::id(), (int)$etudiant['etablissement_id'], 'etudiant.supprimer', 'etudiants', $id, $etudiant, null, $this->request);

        Session::setFlash('success', "Étudiant supprimé.");
        $this->redirect('/etudiants');
    }

    public function importForm(): void
    {
        $accessibleEtabs = Auth::getAccessibleEtablissements('superieur');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        $activeEtabId = Auth::getActiveEtablissementId();

        if (!Auth::hasRole('admin')) {
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
            }
        }

        $filieres = $activeEtabId ? $this->filiereModel->getByEtablissement($activeEtabId) : [];

        $this->render('etudiants.import', [
            'etablissements' => $accessibleEtabs,
            'filieres' => $filieres,
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
        header('Content-Disposition: attachment; filename="modele_import_etudiants.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'matricule',
            'nom',
            'prenom',
            'sexe',
            'date_naissance',
            'nationalite',
            'filiere',
            'classe_groupe',
            'niveau',
            'annee_scolaire',
            'email',
            'telephone',
            'adresse'
        ], ';', '"', "\\");

        fputcsv($output, [
            'UOB-2026-00001',
            'MOUSSAVOU',
            'Aïcha Grâce',
            'F',
            '2004-11-20',
            'Gabonaise',
            'Sciences Juridiques et Politiques',
            'Licence 1 Droit',
            'Licence 1',
            '2026-2027',
            'aicha.moussavou@uob.ga',
            '+241 07 88 99 00',
            'Quartier Awendjé, Libreville'
        ], ';', '"', "\\");

        fclose($output);
        exit;
    }

    public function importProcess(): void
    {
        $accessibleEtabs = Auth::getAccessibleEtablissements('superieur');
        $allowedEtabIds = Auth::getAccessibleEtablissementIds('superieur');
        $activeEtabId = Auth::getActiveEtablissementId();

        $etabId = (int)$this->request->post('etablissement_id', $activeEtabId ?: ($allowedEtabIds[0] ?? 0));

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            Session::setFlash('danger', "Vous n'êtes pas autorisé à importer pour cet établissement.");
            $this->redirect('/etudiants/importer');
        }

        $file = $this->request->file('csv_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('danger', "Veuillez sélectionner un fichier CSV valide.");
            $this->redirect('/etudiants/importer');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            Session::setFlash('danger', "Impossible d'ouvrir le fichier CSV.");
            $this->redirect('/etudiants/importer');
        }

        // Cache des filières et classes existantes
        $filieres = $this->filiereModel->getByEtablissement($etabId);
        $filieresMap = [];
        foreach ($filieres as $f) {
            $filieresMap[mb_strtolower(trim($f['libelle']))] = (int)$f['id'];
            $filieresMap[mb_strtolower(trim($f['code']))] = (int)$f['id'];
        }

        $classes = $this->classeModel->getByEtablissement($etabId);
        $classesMap = [];
        foreach ($classes as $c) {
            $classesMap[mb_strtolower(trim($c['libelle']))] = (int)$c['id'];
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = str_contains((string)$firstLine, ';') ? ';' : ',';

        $rowNum = 0;
        $successCount = 0;
        $errors = [];

        // Ignorer l'en-tête
        fgetcsv($handle, 1000, $delimiter, '"', "\\");

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
            $nationalite = trim($row[5] ?? 'Gabonaise');
            $filiereNom = trim($row[6] ?? '');
            $classeNom = trim($row[7] ?? '');
            $niveau = trim($row[8] ?? 'Licence 1');
            $annee = trim($row[9] ?? date('Y') . '-' . (date('Y') + 1));
            $email = trim($row[10] ?? '');
            $tel = trim($row[11] ?? '');
            $adresse = trim($row[12] ?? '');

            if ($nom === '' || $prenom === '' || $dateNaiss === '') {
                $errors[] = "Ligne $rowNum : Le nom, prénom et date de naissance sont obligatoires.";
                continue;
            }

            if (!in_array($sexe, ['M', 'F'], true)) {
                $sexe = 'M';
            }

            // Résolution ou création automatique de la filière
            $filiereKey = mb_strtolower($filiereNom);
            $filiereId = $filieresMap[$filiereKey] ?? null;
            if (!$filiereId) {
                $newFiliereCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $filiereNom) ?: 'FIL', 0, 10));
                $filiereId = $this->filiereModel->create([
                    'etablissement_id' => $etabId,
                    'code' => $newFiliereCode . '_' . rand(10, 99),
                    'libelle' => $filiereNom !== '' ? $filiereNom : 'Filière Générale',
                ]);
                $filieresMap[$filiereKey] = $filiereId;
            }

            // Résolution classe / groupe (optionnel)
            $classeId = null;
            if ($classeNom !== '') {
                $classeKey = mb_strtolower($classeNom);
                $classeId = $classesMap[$classeKey] ?? null;
                if (!$classeId) {
                    $classeId = $this->classeModel->create([
                        'etablissement_id' => $etabId,
                        'filiere_id' => $filiereId,
                        'libelle' => $classeNom,
                        'niveau' => $niveau,
                        'annee_scolaire' => $annee,
                        'effectif_max' => 150,
                    ]);
                    $classesMap[$classeKey] = $classeId;
                }
            }

            // Vérification doublon
            if ($this->etudiantModel->checkDuplicate($etabId, $nom, $prenom, $dateNaiss)) {
                $errors[] = "Ligne $rowNum : Étudiant « $nom $prenom » déjà existant dans cet établissement (ignoré).";
                continue;
            }

            if ($matricule === '') {
                $matricule = $this->etudiantModel->generateMatricule($etabId);
            }

            try {
                $this->etudiantModel->create([
                    'etablissement_id' => $etabId,
                    'filiere_id' => $filiereId,
                    'classe_id' => $classeId,
                    'matricule' => strtoupper($matricule),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'sexe' => $sexe,
                    'date_naissance' => $dateNaiss,
                    'nationalite' => $nationalite,
                    'niveau' => $niveau,
                    'annee_scolaire' => $annee,
                    'telephone' => $tel,
                    'email' => $email,
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

        Logger::log(Auth::id(), $etabId, 'etudiant.import_csv', 'etudiants', null, null, [
            'succes' => $successCount,
            'erreurs' => count($errors)
        ], $this->request);

        $msg = "Importation terminée : $successCount étudiant(s) importé(s) avec succès.";
        if (!empty($errors)) {
            $msg .= " " . count($errors) . " avertissement(s) ou erreur(s) détecté(s).";
            Session::setFlash('warning', $msg);
            Session::set('import_errors', $errors);
        } else {
            Session::setFlash('success', $msg);
        }

        $this->redirect('/etudiants');
    }

    public function promoteBatch(): void
    {
        $etudiantIds = $this->request->post('etudiant_ids') ?? [];
        $targetFiliereId = (int)$this->request->post('target_filiere_id', 0);
        $targetClasseId = $this->request->post('target_classe_id') ? (int)$this->request->post('target_classe_id') : null;
        $targetNiveau = trim((string)$this->request->post('target_niveau', ''));
        $targetAnnee = trim((string)$this->request->post('target_annee', ''));

        if (empty($etudiantIds) || $targetFiliereId === 0 || $targetNiveau === '' || $targetAnnee === '') {
            Session::setFlash('danger', "Veuillez sélectionner au moins un étudiant, une filière cible, un niveau et une année académique.");
            $this->redirect('/etudiants');
        }

        $targetFiliere = $this->filiereModel->findById($targetFiliereId);
        if (!$targetFiliere) {
            Session::setFlash('danger', "Filière cible introuvable.");
            $this->redirect('/etudiants');
        }

        $count = $this->etudiantModel->promoteBatch(
            array_map('intval', (array)$etudiantIds),
            $targetFiliereId,
            $targetClasseId,
            $targetNiveau,
            $targetAnnee
        );

        Logger::log(Auth::id(), (int)$targetFiliere['etablissement_id'], 'etudiant.promotion_lot', 'etudiants', null, null, [
            'nombre' => $count,
            'filiere_cible' => $targetFiliere['libelle'],
            'niveau' => $targetNiveau,
            'annee' => $targetAnnee
        ], $this->request);

        Session::setFlash('success', "$count étudiant(s) promu(s) / réinscrit(s) avec succès en « {$targetFiliere['libelle']} » ($targetNiveau, $targetAnnee).");
        $this->redirect('/etudiants?filiere_id=' . $targetFiliereId);
    }

    /**
     * Endpoint AJAX pour recharger les filières et groupes d'un établissement
     */
    public function ajaxGetFilieres(): void
    {
        $etabId = (int)$this->request->query('etablissement_id', 0);
        $filieres = $this->filiereModel->getByEtablissement($etabId);
        $classes = $this->classeModel->getByEtablissement($etabId);

        $this->response->json([
            'success' => true,
            'filieres' => $filieres,
            'classes' => $classes,
        ]);
    }
}
