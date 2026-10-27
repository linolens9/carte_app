<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use App\Models\Classe;
use App\Models\Departement;
use App\Models\Etablissement;
use App\Models\Filiere;

class EtablissementController extends Controller
{
    private Etablissement $etablissementModel;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        $this->etablissementModel = new Etablissement();
    }

    public function index(): void
    {
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('warning', 'Vous n\'avez pas accès à la liste globale des établissements.');
            $this->redirect('/dashboard');
            return;
        }

        $search = (string)$this->request->query('q', '');
        $categorie = (string)$this->request->query('categorie', '');
        $type = (string)$this->request->query('type', '');
        $statut = (string)$this->request->query('statut', '');

        $counts = $this->etablissementModel->getCounts();
        $etablissements = $this->etablissementModel->getAllWithStats($search, $categorie, $type, $statut);

        $this->render('etablissements.index', [
            'etablissements' => $etablissements,
            'counts' => $counts,
            'filters' => [
                'q' => $search,
                'categorie' => $categorie,
                'type' => $type,
                'statut' => $statut,
            ]
        ]);
    }

    public function create(): void
    {
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('danger', 'Action non autorisée.');
            $this->redirect('/dashboard');
            return;
        }

        $this->render('etablissements.create');
    }

    public function store(): void
    {
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            Session::setFlash('danger', 'Action non autorisée.');
            $this->redirect('/dashboard');
            return;
        }

        $data = $this->request->post();

        $rules = [
            'code' => 'required|max:20|unique:etablissements,code',
            'nom' => 'required|max:200',
            'categorie' => 'required|in:scolaire,professionnel',
            'type' => 'required|in:primaire,college,lycee,universite,centre_formation,entreprise,administration,association,ong,etablissement_prive',
            'email' => 'email|max:190',
        ];

        $errors = $this->validate($data, $rules);

        // Uploads Logo & Cachet
        $logoPath = $this->handleUpload('logo', 'logos');
        $cachetPath = $this->handleUpload('cachet', 'cachets');

        if (!empty($errors)) {
            $this->render('etablissements.create', ['errors' => $errors, 'old' => $data]);
            return;
        }

        $id = $this->etablissementModel->create([
            'code' => strtoupper(trim((string)$data['code'])),
            'nom' => trim((string)$data['nom']),
            'categorie' => (string)$data['categorie'],
            'type' => (string)$data['type'],
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'ville' => trim((string)($data['ville'] ?? '')),
            'pays' => trim((string)($data['pays'] ?? 'Gabon')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'site_web' => trim((string)($data['site_web'] ?? '')),
            'devise' => trim((string)($data['devise'] ?? '')),
            'couleur_principale' => trim((string)($data['couleur_principale'] ?? '#0d6efd')),
            'annee_active' => trim((string)($data['annee_active'] ?? date('Y') . '-' . (date('Y') + 1))),
            'logo' => $logoPath,
            'cachet' => $cachetPath,
            'statut' => 'actif',
        ]);

        Logger::log(Auth::id(), $id, 'etablissement.creer', 'etablissements', $id, null, $data, $this->request);

        Session::setFlash('success', 'Établissement créé avec succès.');
        $this->redirect('/etablissements/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            $allowedIds = Auth::getAccessibleEtablissementIds();
            if (!in_array($id, $allowedIds, true)) {
                Session::setFlash('danger', 'Accès non autorisé à cet établissement.');
                $this->redirect('/dashboard');
                return;
            }
        }

        $etablissement = $this->etablissementModel->findByIdWithArchived($id);

        if (!$etablissement) {
            Session::setFlash('danger', 'Établissement introuvable.');
            $this->redirect('/dashboard');
            return;
        }

        $stats = $this->etablissementModel->getStats($id);

        $deptModel = new Departement();
        $departements = $deptModel->getByEtablissement($id);

        $filiereModel = new Filiere();
        $filieres = $filiereModel->getByEtablissement($id);

        $classeModel = new Classe();
        $classes = $classeModel->getByEtablissement($id);

        $this->render('etablissements.show', [
            'etab' => $etablissement,
            'stats' => $stats,
            'departements' => $departements,
            'filieres' => $filieres,
            'classes' => $classes,
        ]);
    }

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            $allowedIds = Auth::getAccessibleEtablissementIds();
            if (!in_array($id, $allowedIds, true)) {
                Session::setFlash('danger', 'Accès non autorisé à cet établissement.');
                $this->redirect('/dashboard');
                return;
            }
        }

        $etablissement = $this->etablissementModel->findByIdWithArchived($id);

        if (!$etablissement) {
            Session::setFlash('danger', 'Établissement introuvable.');
            $this->redirect('/dashboard');
            return;
        }

        $this->render('etablissements.edit', ['etab' => $etablissement]);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!Auth::hasRole('admin', 'gestionnaire')) {
            $allowedIds = Auth::getAccessibleEtablissementIds();
            if (!in_array($id, $allowedIds, true)) {
                Session::setFlash('danger', 'Accès non autorisé à cet établissement.');
                $this->redirect('/dashboard');
                return;
            }
        }

        $etablissement = $this->etablissementModel->findByIdWithArchived($id);

        if (!$etablissement) {
            Session::setFlash('danger', 'Établissement introuvable.');
            $this->redirect('/dashboard');
            return;
        }

        $data = $this->request->post();

        $rules = [
            'code' => "required|max:20|unique:etablissements,code,$id",
            'nom' => 'required|max:200',
            'categorie' => 'required|in:scolaire,professionnel',
            'type' => 'required|in:primaire,college,lycee,universite,centre_formation,entreprise,administration,association,ong,etablissement_prive',
            'statut' => 'required|in:actif,suspendu,archive',
        ];

        $errors = $this->validate($data, $rules);

        $logoPath = $this->handleUpload('logo', 'logos');
        $cachetPath = $this->handleUpload('cachet', 'cachets');

        if (!empty($errors)) {
            $this->render('etablissements.edit', ['etab' => array_merge($etablissement, $data), 'errors' => $errors]);
            return;
        }

        $updatePayload = [
            'code' => strtoupper(trim((string)$data['code'])),
            'nom' => trim((string)$data['nom']),
            'categorie' => (string)$data['categorie'],
            'type' => (string)$data['type'],
            'adresse' => trim((string)($data['adresse'] ?? '')),
            'ville' => trim((string)($data['ville'] ?? '')),
            'pays' => trim((string)($data['pays'] ?? '')),
            'telephone' => trim((string)($data['telephone'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'site_web' => trim((string)($data['site_web'] ?? '')),
            'devise' => trim((string)($data['devise'] ?? '')),
            'couleur_principale' => trim((string)($data['couleur_principale'] ?? '#0d6efd')),
            'annee_active' => trim((string)($data['annee_active'] ?? '')),
            'statut' => (string)$data['statut'],
        ];

        if ($data['statut'] === 'actif' && !empty($etablissement['deleted_at'])) {
            $updatePayload['deleted_at'] = null;
        } elseif ($data['statut'] === 'archive' && empty($etablissement['deleted_at'])) {
            $updatePayload['deleted_at'] = date('Y-m-d H:i:s');
        }

        if ($logoPath) {
            $updatePayload['logo'] = $logoPath;
        }
        if ($cachetPath) {
            $updatePayload['cachet'] = $cachetPath;
        }

        $this->etablissementModel->update($id, $updatePayload);

        Logger::log(Auth::id(), $id, 'etablissement.modifier', 'etablissements', $id, $etablissement, $updatePayload, $this->request);

        Session::setFlash('success', 'Établissement mis à jour avec succès.');
        $this->redirect('/etablissements/' . $id);
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etablissement = $this->etablissementModel->findByIdWithArchived($id);

        if (!$etablissement) {
            Session::setFlash('danger', 'Établissement introuvable.');
            $this->redirect('/etablissements');
        }

        // Règle du cahier des charges 3.1.3 : archivage/suppression refusée si des cartes actives existent
        if ($this->etablissementModel->hasActiveCards($id)) {
            Session::setFlash('danger', 'Impossible d\'archiver cet établissement car des cartes actives ou en cours y sont associées. Veuillez les archiver ou désactiver au préalable.');
            $this->redirect('/etablissements/' . $id);
        }

        $this->etablissementModel->archive($id);
        Logger::log(Auth::id(), $id, 'etablissement.archiver', 'etablissements', $id, $etablissement, ['statut' => 'archive'], $this->request);

        Session::setFlash('success', 'Établissement archivé avec succès.');
        $this->redirect('/etablissements');
    }

    public function restore(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $etablissement = $this->etablissementModel->findByIdWithArchived($id);

        if (!$etablissement) {
            Session::setFlash('danger', 'Établissement introuvable.');
            $this->redirect('/etablissements');
        }

        $this->etablissementModel->restore($id);
        Logger::log(Auth::id(), $id, 'etablissement.restaurer', 'etablissements', $id, $etablissement, ['statut' => 'actif'], $this->request);

        Session::setFlash('success', 'Établissement restauré avec succès.');
        $this->redirect('/etablissements/' . $id);
    }

    public function addDepartement(array $params): void
    {
        $etabId = (int)($params['id'] ?? 0);
        $code = trim((string)$this->request->post('code', ''));
        $nom = trim((string)$this->request->post('nom', ''));
        $parentId = $this->request->post('parent_id') ? (int)$this->request->post('parent_id') : null;

        if ($code === '' || $nom === '') {
            Session::setFlash('danger', 'Le code et le nom du département sont obligatoires.');
        } else {
            $deptModel = new Departement();
            $deptModel->create([
                'etablissement_id' => $etabId,
                'code' => strtoupper($code),
                'nom' => $nom,
                'parent_id' => $parentId
            ]);
            Session::setFlash('success', 'Département / Service ajouté.');
        }

        $this->redirect('/etablissements/' . $etabId);
    }

    public function addFiliere(array $params): void
    {
        $etabId = (int)($params['id'] ?? 0);
        $code = trim((string)$this->request->post('code', ''));
        $libelle = trim((string)$this->request->post('libelle', ''));
        $deptId = $this->request->post('departement_id') ? (int)$this->request->post('departement_id') : null;

        if ($code === '' || $libelle === '') {
            Session::setFlash('danger', 'Le code et le libellé de la filière sont obligatoires.');
        } else {
            $filiereModel = new Filiere();
            $filiereModel->create([
                'etablissement_id' => $etabId,
                'departement_id' => $deptId,
                'code' => strtoupper($code),
                'libelle' => $libelle,
            ]);
            Session::setFlash('success', 'Filière ajoutée.');
        }

        $this->redirect('/etablissements/' . $etabId);
    }

    public function addClasse(array $params): void
    {
        $etabId = (int)($params['id'] ?? 0);
        $libelle = trim((string)$this->request->post('libelle', ''));
        $niveau = trim((string)$this->request->post('niveau', ''));
        $annee = trim((string)$this->request->post('annee_scolaire', date('Y') . '-' . (date('Y') + 1)));
        $filiereId = $this->request->post('filiere_id') ? (int)$this->request->post('filiere_id') : null;
        $max = (int)$this->request->post('effectif_max', 0);

        if ($libelle === '' || $niveau === '') {
            Session::setFlash('danger', 'Le libellé et le niveau de la classe sont obligatoires.');
        } else {
            $classeModel = new Classe();
            $classeModel->create([
                'etablissement_id' => $etabId,
                'filiere_id' => $filiereId,
                'libelle' => $libelle,
                'niveau' => $niveau,
                'annee_scolaire' => $annee,
                'effectif_max' => $max > 0 ? $max : null
            ]);
            Session::setFlash('success', 'Classe créée.');
        }

        $this->redirect('/etablissements/' . $etabId);
    }

    private function handleUpload(string $inputName, string $subfolder): ?string
    {
        $file = $this->request->file($inputName);
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return null;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            return null;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'png'
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetDir = dirname(__DIR__, 2) . '/public/uploads/' . $subfolder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $destination = $targetDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/' . $subfolder . '/' . $filename;
        }

        return null;
    }
}
