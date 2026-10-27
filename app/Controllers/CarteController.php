<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Carte;
use App\Models\Template;
use App\Models\Etablissement;
use App\Models\Classe;
use App\Models\Filiere;
use App\Models\Departement;

class CarteController extends Controller
{
    private Carte $carteModel;
    private Template $templateModel;
    private Etablissement $etablissementModel;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->carteModel = new Carte();
        $this->templateModel = new Template();
        $this->etablissementModel = new Etablissement();
    }

    public function index(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds();
            if (!$activeEtabId || !in_array($activeEtabId, $allowedEtabIds, true)) {
                $activeEtabId = $allowedEtabIds[0] ?? null;
                if ($activeEtabId) {
                    Auth::setActiveEtablissementId($activeEtabId);
                }
            }
        }

        $activeEtab = Auth::getActiveEtablissement();
        $isSchool = $activeEtab && in_array($activeEtab['type'] ?? '', ['primaire', 'college', 'lycee'], true);
        $isHigherEd = $activeEtab && in_array($activeEtab['type'] ?? '', ['universite', 'centre_formation'], true);

        $statut = $this->request->query('statut');
        $type = $this->request->query('type');
        if ($isSchool && $type === 'etudiant' && !Auth::hasRole('admin', 'gestionnaire')) {
            $type = null;
        }
        if ($isHigherEd && $type === 'eleve' && !Auth::hasRole('admin', 'gestionnaire')) {
            $type = null;
        }

        $search = $this->request->query('q');
        $page = max(1, (int)$this->request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($activeEtabId) {
            $filters['etablissement_id'] = (int)$activeEtabId;
        }
        if ($statut) {
            $filters['statut'] = $statut;
        }
        if ($type) {
            $filters['beneficiaire_type'] = $type;
        }
        if ($search) {
            $filters['search'] = $search;
        }

        $cartes = $this->carteModel->all($filters, $limit, $offset);
        $total = $this->carteModel->count($filters);
        $totalPages = max(1, (int)ceil($total / $limit));

        $kpi = $this->carteModel->countByStatut($activeEtabId ? (int)$activeEtabId : null);

        $this->render('cartes/index', [
            'title' => 'Gestion des Cartes d\'Identité',
            'cartes' => $cartes,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'statut_filter' => $statut,
            'type_filter' => $type,
            'search' => $search,
            'kpi' => $kpi,
            'active_etab_id' => $activeEtabId
        ]);
    }

    public function show(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $carte = $this->carteModel->find($id);

        if (!$carte) {
            Session::setFlash('danger', 'Carte d\'identité introuvable.');
            $this->response->redirect('/cartes');
            return;
        }

        $historique = $this->carteModel->getHistorique($id);
        $impressions = $this->carteModel->getImpressions($id);

        $this->render('cartes/show', [
            'title' => 'Détails de la Carte : ' . $carte['numero_carte'],
            'card' => $carte,
            'historique' => $historique,
            'impressions' => $impressions
        ]);
    }

    public function create(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $etablissements = Auth::hasRole('admin') ? $this->etablissementModel->all() : Auth::getAccessibleEtablissements();

        $allowedEtabIds = array_map('intval', array_column($etablissements, 'id'));
        $selectedEtabId = ($activeEtabId && in_array((int)$activeEtabId, $allowedEtabIds, true)) 
            ? (int)$activeEtabId 
            : ($allowedEtabIds[0] ?? 1);

        $templates = $this->templateModel->all((int)$selectedEtabId);

        $activeEtab = Auth::getActiveEtablissement();
        $isSchool = $activeEtab && in_array($activeEtab['type'] ?? '', ['primaire', 'college', 'lycee'], true);
        $isHigherEd = $activeEtab && in_array($activeEtab['type'] ?? '', ['universite', 'centre_formation'], true);
        $benefType = (string)($this->request->query('type') ?: ($isHigherEd ? 'etudiant' : 'eleve'));
        if ($isSchool && $benefType === 'etudiant') {
            $benefType = 'eleve';
        }
        if ($isHigherEd && $benefType === 'eleve') {
            $benefType = 'etudiant';
        }

        $this->render('cartes/create', [
            'title' => 'Générer une carte unitaire',
            'etablissements' => $etablissements,
            'selected_etab_id' => $selectedEtabId,
            'templates' => $templates,
            'beneficiaire_id' => $this->request->query('beneficiaire_id'),
            'beneficiaire_type' => $benefType
        ]);
    }

    public function store(array $params = []): void
    {
        $etablissementId = (int)$this->request->post('etablissement_id');
        $templateId = (int)$this->request->post('template_id');
        $type = (string)$this->request->post('beneficiaire_type');
        $beneficiaireId = (int)$this->request->post('beneficiaire_id');
        $dateExpiration = $this->request->post('date_expiration') ?: null;

        if (!Auth::hasRole('admin')) {
            $allowedEtabIds = Auth::getAccessibleEtablissementIds();
            if (!in_array($etablissementId, $allowedEtabIds, true)) {
                Session::setFlash('danger', 'Accès non autorisé à cet établissement.');
                $this->response->redirect('/cartes/generer');
                return;
            }
        }

        if (!$etablissementId || !$templateId || !$beneficiaireId || !in_array($type, ['eleve', 'etudiant', 'personnel'])) {
            Session::setFlash('danger', 'Veuillez renseigner tous les champs obligatoires.');
            $this->response->redirect('/cartes/generer');
            return;
        }

        $user = Auth::user();

        try {
            $carteId = $this->carteModel->genererUnitaire(
                $etablissementId,
                $templateId,
                $type,
                $beneficiaireId,
                (int)($user['id'] ?? 1),
                $dateExpiration
            );

            Session::setFlash('success', 'Carte générée avec succès (Statut : Brouillon).');
            $this->response->redirect("/cartes/{$carteId}");
        } catch (\Exception $e) {
            Session::setFlash('danger', $e->getMessage());
            $this->response->redirect('/cartes/generer');
        }
    }

    public function generateBatchForm(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $etablissements = Auth::hasRole('admin') ? $this->etablissementModel->all() : Auth::getAccessibleEtablissements();

        $allowedEtabIds = array_map('intval', array_column($etablissements, 'id'));
        $selectedEtabId = ($activeEtabId && in_array((int)$activeEtabId, $allowedEtabIds, true)) 
            ? (int)$activeEtabId 
            : ($allowedEtabIds[0] ?? 1);

        $templates = $this->templateModel->all((int)$selectedEtabId);

        $classes = (new Classe())->getByEtablissement((int)$selectedEtabId);
        $filieres = (new Filiere())->getByEtablissement((int)$selectedEtabId);
        $departements = (new Departement())->getByEtablissement((int)$selectedEtabId);

        $this->render('cartes/batch', [
            'title' => 'Génération groupée de cartes (Par Lot)',
            'etablissements' => $etablissements,
            'selected_etab_id' => $selectedEtabId,
            'templates' => $templates,
            'classes' => $classes,
            'filieres' => $filieres,
            'departements' => $departements
        ]);
    }

    public function generateBatchProcess(array $params = []): void
    {
        $etablissementId = (int)$this->request->post('etablissement_id');
        $templateId = (int)$this->request->post('template_id');
        $type = (string)$this->request->post('beneficiaire_type');
        $beneficiaireIds = (array)$this->request->post('beneficiaire_ids', []);
        $dateExpiration = $this->request->post('date_expiration') ?: null;

        if (!$etablissementId || !$templateId || empty($beneficiaireIds)) {
            Session::setFlash('danger', 'Veuillez sélectionner au moins un bénéficiaire et un modèle valide.');
            $this->response->redirect('/cartes/generer-lot');
            return;
        }

        $user = Auth::user();

        $result = $this->carteModel->genererLot(
            $etablissementId,
            $templateId,
            $type,
            $beneficiaireIds,
            (int)($user['id'] ?? 1),
            $dateExpiration
        );

        $succes = $result['succes'];
        $ignores = $result['ignores'];

        Session::setFlash('success', "Génération terminée : {$succes} cartes générées." . ($ignores > 0 ? " ({$ignores} ignorés car déjà pourvus de cartes actives)" : ''));
        $this->response->redirect('/cartes?statut=brouillon');
    }

    public function validateCard(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = Auth::user();

        if ($this->carteModel->valider($id, (int)($user['id'] ?? 1))) {
            Session::setFlash('success', 'Carte validée avec succès. Elle est maintenant prête pour impression.');
        } else {
            Session::setFlash('warning', 'Impossible de valider cette carte (déjà validée ou annulée).');
        }

        $this->response->redirect("/cartes/{$id}");
    }

    public function validateBatch(array $params = []): void
    {
        $ids = (array)$this->request->post('cartes_ids', []);
        $user = Auth::user();

        if (empty($ids)) {
            Session::setFlash('warning', 'Aucune carte sélectionnée.');
            $this->response->redirect('/cartes');
            return;
        }

        $count = $this->carteModel->validerLot($ids, (int)($user['id'] ?? 1));
        Session::setFlash('success', "{$count} carte(s) validée(s) avec succès.");
        $this->response->redirect('/cartes?statut=validee');
    }

    public function activate(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = Auth::user();

        if ($this->carteModel->activer($id, (int)($user['id'] ?? 1))) {
            Session::setFlash('success', 'Carte marquée comme active (remise au bénéficiaire).');
        } else {
            Session::setFlash('danger', 'Erreur lors de l\'activation.');
        }

        $this->response->redirect("/cartes/{$id}");
    }

    public function deactivate(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $motif = trim((string)$this->request->post('motif', ''));
        $user = Auth::user();

        if ($this->carteModel->desactiver($id, (int)($user['id'] ?? 1), $motif)) {
            Session::setFlash('info', 'La carte a été désactivée.');
        } else {
            Session::setFlash('danger', 'Erreur lors de la désactivation.');
        }

        $this->response->redirect("/cartes/{$id}");
    }

    public function renew(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $motif = trim((string)$this->request->post('motif', 'Renouvellement annuel'));
        $user = Auth::user();

        try {
            $nouvelleId = $this->carteModel->renouveler($id, (int)($user['id'] ?? 1), $motif);
            Session::setFlash('success', 'Carte renouvelée avec succès. L\'ancienne carte a été désactivée.');
            $this->response->redirect("/cartes/{$nouvelleId}");
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur lors du renouvellement : ' . $e->getMessage());
            $this->response->redirect("/cartes/{$id}");
        }
    }

    public function archive(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $user = Auth::user();

        if ($this->carteModel->archiver($id, (int)($user['id'] ?? 1))) {
            Session::setFlash('info', 'Carte archivée.');
        } else {
            Session::setFlash('danger', 'Erreur lors de l\'archivage.');
        }

        $this->response->redirect("/cartes/{$id}");
    }

    public function delete(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $this->carteModel->delete($id);

        Session::setFlash('info', 'Carte supprimée.');
        $this->response->redirect('/cartes');
    }

    public function ajaxGetBeneficiairesSansCarte(array $params = []): void
    {
        $etablissementId = (int)$this->request->query('etablissement_id');
        $type = (string)($this->request->query('type') ?: 'eleve');
        $groupeId = $this->request->query('groupe_id') ? (int)$this->request->query('groupe_id') : null;

        $beneficiaires = $this->carteModel->getBeneficiairesSansCarte($etablissementId, $type, $groupeId);
        $this->response->json($beneficiaires);
    }
}
