<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Departement;
use App\Models\Etablissement;
use App\Models\Personnel;
use App\Services\PhotoService;
use Throwable;

class PersonnelController extends Controller
{
    private Personnel $personnelModel;
    private Departement $departementModel;
    private Etablissement $etablissementModel;
    private PhotoService $photoService;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->personnelModel = new Personnel();
        $this->departementModel = new Departement();
        $this->etablissementModel = new Etablissement();
        $this->photoService = new PhotoService();
    }

    public function index(): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();

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
            'departement_id' => $this->request->query('departement_id'),
            'statut' => $this->request->query('statut'),
            'statut_photo' => $this->request->query('statut_photo'),
            'fonction' => $this->request->query('fonction'),
            'service' => $this->request->query('service'),
        ];

        $search = trim((string)$this->request->query('q', ''));
        $page = max(1, (int)$this->request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $personnels = $this->personnelModel->getAllFiltered($filters, $search, $limit, $offset);
        $total = $this->personnelModel->countFiltered($filters, $search);
        $totalPages = (int)ceil($total / $limit);

        $etablissements = $accessibleEtabs;
        $departements = $etabId ? $this->departementModel->getByEtablissement($etabId) : [];

        $this->render('personnels.index', [
            'personnels' => $personnels,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'search' => $search,
            'etablissements' => $etablissements,
            'departements' => $departements,
            'selectedEtabId' => $etabId,
        ]);
    }

    public function create(): void
    {
        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        $activeEtabId = Auth::getActiveEtablissementId();

        if (!Auth::hasRole('admin')) {
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
            }
        }

        $departements = $activeEtabId ? $this->departementModel->getByEtablissement($activeEtabId) : [];

        $this->render('personnels.create', [
            'etablissements' => $accessibleEtabs,
            'departements' => $departements,
            'activeEtabId' => $activeEtabId,
        ]);
    }

    public function store(): void
    {
        $data = $this->request->post();
        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        $activeEtabId = Auth::getActiveEtablissementId();

        $postedEtabId = !empty($data['etablissement_id']) ? (int)$data['etablissement_id'] : ($activeEtabId ?: ($allowedEtabIds[0] ?? 0));
        $etabId = $postedEtabId;

        $rules = [
            'etablissement_id' => 'required',
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'fonction' => 'required|max:150',
        ];

        $errors = $this->validate($data, $rules);

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            $errors['etablissement_id'][] = "Vous n'êtes pas autorisé à ajouter un collaborateur dans cet établissement.";
        }

        // Détection de doublons (nom + prénom + fonction)
        if (empty($errors['nom']) && empty($errors['prenom']) && empty($errors['fonction'])) {
            $duplicate = $this->personnelModel->checkDuplicate($etabId, (string)$data['nom'], (string)$data['prenom'], (string)$data['fonction']);
            if ($duplicate) {
                $errors['nom'][] = "Un agent avec ce nom, prénom et fonction existe déjà (N° pro : {$duplicate['numero_professionnel']}).";
            }
        }

        // Traitement de la photo
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
            $departements = $etabId ? $this->departementModel->getByEtablissement($etabId) : [];
            $this->render('personnels.create', [
                'errors' => $errors,
                'old' => $data,
                'etablissements' => $accessibleEtabs,
                'departements' => $departements,
                'activeEtabId' => $etabId,
            ]);
            return;
        }

        // Génération du numéro professionnel si non fourni
        $numeroPro = trim((string)($data['numero_professionnel'] ?? ''));
        if ($numeroPro === '') {
            $numeroPro = $this->personnelModel->generateNumeroProfessionnel($etabId);
        }

        $id = $this->personnelModel->create([
            'etablissement_id' => $etabId,
            'departement_id' => !empty($data['departement_id']) ? (int)$data['departement_id'] : null,
            'numero_professionnel' => strtoupper($numeroPro),
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'fonction' => trim((string)$data['fonction']),
            'service' => trim((string)($data['service'] ?? '')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'date_embauche' => !empty($data['date_embauche']) ? (string)$data['date_embauche'] : null,
            'photo' => $photoPath,
            'statut' => 'en_poste',
            'created_by' => Auth::id(),
        ]);

        Logger::log(Auth::id(), $etabId, 'personnel.creer', 'personnels', $id, null, [
            'numero_professionnel' => $numeroPro,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'fonction' => $data['fonction'],
        ], $this->request);

        Session::setFlash('success', "Membre du personnel enregistré avec succès (N° : $numeroPro).");
        $this->redirect('/personnels/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $personnel = $this->personnelModel->getWithDetails($id);

        if (!$personnel) {
            Session::setFlash('danger', "Personnel introuvable.");
            $this->redirect('/personnels');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        if (!Auth::hasRole('admin') && !in_array((int)$personnel['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à ce membre du personnel.");
            $this->redirect('/personnels');
        }

        $this->render('personnels.show', ['personnel' => $personnel]);
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $personnel = $this->personnelModel->getWithDetails($id);

        if (!$personnel) {
            Session::setFlash('danger', "Personnel introuvable.");
            $this->redirect('/personnels');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        if (!Auth::hasRole('admin') && !in_array((int)$personnel['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à ce membre du personnel.");
            $this->redirect('/personnels');
        }

        $etabId = (int)$personnel['etablissement_id'];
        $departements = $this->departementModel->getByEtablissement($etabId);
        $etablissements = Auth::getAccessibleEtablissements();

        $this->render('personnels.edit', [
            'personnel' => $personnel,
            'departements' => $departements,
            'etablissements' => $etablissements,
        ]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $personnel = $this->personnelModel->findById($id);

        if (!$personnel) {
            Session::setFlash('danger', "Personnel introuvable.");
            $this->redirect('/personnels');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        if (!Auth::hasRole('admin') && !in_array((int)$personnel['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Accès non autorisé à ce membre du personnel.");
            $this->redirect('/personnels');
        }

        $data = $this->request->post();
        $etabId = (int)$personnel['etablissement_id'];

        $rules = [
            'nom' => 'required|max:100',
            'prenom' => 'required|max:100',
            'fonction' => 'required|max:150',
            'statut' => 'required|in:en_poste,suspendu,parti',
        ];

        $errors = $this->validate($data, $rules);

        // Photo
        $photoPath = $personnel['photo'];
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
            $departements = $this->departementModel->getByEtablissement($etabId);
            $etablissements = Auth::getAccessibleEtablissements();
            $this->render('personnels.edit', [
                'personnel' => array_merge($personnel, $data),
                'departements' => $departements,
                'etablissements' => $etablissements,
                'errors' => $errors,
            ]);
            return;
        }

        $updatePayload = [
            'departement_id' => !empty($data['departement_id']) ? (int)$data['departement_id'] : null,
            'nom' => mb_strtoupper(trim((string)$data['nom']), 'UTF-8'),
            'prenom' => mb_convert_case(trim((string)$data['prenom']), MB_CASE_TITLE, 'UTF-8'),
            'fonction' => trim((string)$data['fonction']),
            'service' => trim((string)($data['service'] ?? '')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'date_embauche' => !empty($data['date_embauche']) ? (string)$data['date_embauche'] : null,
            'photo' => $photoPath,
            'statut' => (string)$data['statut'],
        ];

        $this->personnelModel->update($id, $updatePayload);

        Logger::log(Auth::id(), $etabId, 'personnel.modifier', 'personnels', $id, $personnel, $updatePayload, $this->request);

        Session::setFlash('success', "Fiche du personnel mise à jour.");
        $this->redirect('/personnels/' . $id);
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $personnel = $this->personnelModel->findById($id);

        if (!$personnel) {
            Session::setFlash('danger', "Personnel introuvable.");
            $this->redirect('/personnels');
        }

        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        if (!Auth::hasRole('admin') && !in_array((int)$personnel['etablissement_id'], $allowedEtabIds, true)) {
            Session::setFlash('danger', "Action non autorisée.");
            $this->redirect('/personnels');
        }

        $this->personnelModel->delete($id);
        Logger::log(Auth::id(), (int)$personnel['etablissement_id'], 'personnel.supprimer', 'personnels', $id, $personnel, null, $this->request);

        Session::setFlash('success', "Membre du personnel supprimé.");
        $this->redirect('/personnels');
    }

    public function importForm(): void
    {
        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        $activeEtabId = Auth::getActiveEtablissementId();

        if (!Auth::hasRole('admin')) {
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
            }
        }

        $departements = $activeEtabId ? $this->departementModel->getByEtablissement($activeEtabId) : [];

        $this->render('personnels.import', [
            'etablissements' => $accessibleEtabs,
            'departements' => $departements,
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
        header('Content-Disposition: attachment; filename="modele_import_personnel.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'numero_professionnel',
            'nom',
            'prenom',
            'fonction',
            'service',
            'departement',
            'email',
            'telephone',
            'adresse',
            'date_embauche'
        ], ';', '"', "\\");

        fputcsv($output, [
            'ANINF-2026-00001',
            'BILOGHE',
            'Serge Roland',
            'Ingénieur Systèmes & Réseaux',
            'Pôle Infrastructure Cloud',
            'DSI',
            'serge.biloghe@aninf.ga',
            '+241 07 10 20 30',
            'Boulevard Triomphal, Libreville',
            '2021-03-01'
        ], ';', '"', "\\");

        fclose($output);
        exit;
    }

    public function importProcess(): void
    {
        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $allowedEtabIds = Auth::getAccessibleEtablissementIds();
        $activeEtabId = Auth::getActiveEtablissementId();

        $etabId = (int)$this->request->post('etablissement_id', $activeEtabId ?: ($allowedEtabIds[0] ?? 0));

        if (!Auth::hasRole('admin') && !in_array($etabId, $allowedEtabIds, true)) {
            Session::setFlash('danger', "Vous n'êtes pas autorisé à importer pour cet établissement.");
            $this->redirect('/personnels/importer');
        }

        $file = $this->request->file('csv_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('danger', "Veuillez sélectionner un fichier CSV valide.");
            $this->redirect('/personnels/importer');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            Session::setFlash('danger', "Impossible d'ouvrir le fichier CSV.");
            $this->redirect('/personnels/importer');
        }

        $departements = $this->departementModel->getByEtablissement($etabId);
        $deptMap = [];
        foreach ($departements as $d) {
            $deptMap[mb_strtolower(trim($d['nom']))] = (int)$d['id'];
            $deptMap[mb_strtolower(trim($d['code']))] = (int)$d['id'];
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

            $numPro = trim($row[0] ?? '');
            $nom = mb_strtoupper(trim($row[1] ?? ''), 'UTF-8');
            $prenom = mb_convert_case(trim($row[2] ?? ''), MB_CASE_TITLE, 'UTF-8');
            $fonction = trim($row[3] ?? '');
            $service = trim($row[4] ?? '');
            $deptNom = trim($row[5] ?? '');
            $email = trim($row[6] ?? '');
            $tel = trim($row[7] ?? '');
            $adresse = trim($row[8] ?? '');
            $dateEmbauche = trim($row[9] ?? '');

            if ($nom === '' || $prenom === '' || $fonction === '') {
                $errors[] = "Ligne $rowNum : Le nom, prénom et fonction sont obligatoires.";
                continue;
            }

            // Résolution ou création automatique du département
            $deptId = null;
            if ($deptNom !== '') {
                $deptKey = mb_strtolower($deptNom);
                $deptId = $deptMap[$deptKey] ?? null;
                if (!$deptId) {
                    $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $deptNom) ?: 'DEP', 0, 8));
                    $deptId = $this->departementModel->create([
                        'etablissement_id' => $etabId,
                        'code' => $code . '_' . rand(10, 99),
                        'nom' => $deptNom,
                    ]);
                    $deptMap[$deptKey] = $deptId;
                }
            }

            // Doublon
            if ($this->personnelModel->checkDuplicate($etabId, $nom, $prenom, $fonction)) {
                $errors[] = "Ligne $rowNum : L'agent « $nom $prenom » ($fonction) existe déjà (ignoré).";
                continue;
            }

            if ($numPro === '') {
                $numPro = $this->personnelModel->generateNumeroProfessionnel($etabId);
            }

            try {
                $this->personnelModel->create([
                    'etablissement_id' => $etabId,
                    'departement_id' => $deptId,
                    'numero_professionnel' => strtoupper($numPro),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'fonction' => $fonction,
                    'service' => $service,
                    'telephone' => $tel,
                    'email' => $email,
                    'adresse' => $adresse,
                    'date_embauche' => $dateEmbauche !== '' ? $dateEmbauche : null,
                    'statut' => 'en_poste',
                    'created_by' => Auth::id(),
                ]);
                $successCount++;
            } catch (Throwable $e) {
                $errors[] = "Ligne $rowNum : " . $e->getMessage();
            }
        }

        fclose($handle);

        Logger::log(Auth::id(), $etabId, 'personnel.import_csv', 'personnels', null, null, [
            'succes' => $successCount,
            'erreurs' => count($errors)
        ], $this->request);

        $msg = "Importation terminée : $successCount membre(s) du personnel importé(s) avec succès.";
        if (!empty($errors)) {
            $msg .= " " . count($errors) . " avertissement(s) ou doublon(s) détecté(s).";
            Session::setFlash('warning', $msg);
            Session::set('import_errors', $errors);
        } else {
            Session::setFlash('success', $msg);
        }

        $this->redirect('/personnels');
    }

    public function ajaxGetDepartements(): void
    {
        $etabId = (int)$this->request->query('etablissement_id', 0);
        $departements = $this->departementModel->getByEtablissement($etabId);

        $this->response->json([
            'success' => true,
            'departements' => $departements,
        ]);
    }
}
