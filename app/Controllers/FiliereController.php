<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Filiere;
use App\Models\Departement;
use App\Models\Etablissement;

/**
 * Contrôleur CRUD complet pour les Filières & Séries d'études
 */
class FiliereController extends Controller
{
    private Filiere $filiereModel;
    private Departement $deptModel;
    private Etablissement $etabModel;

    public function __construct(\App\Core\Request $request, \App\Core\Response $response)
    {
        parent::__construct($request, $response);
        $this->filiereModel = new Filiere();
        $this->deptModel = new Departement();
        $this->etabModel = new Etablissement();
    }

    /**
     * Liste et interface CRUD des filières
     */
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $selectedEtabId = (int)$this->request->get('etablissement_id', Auth::getActiveEtablissementId() ?? 0);

        if ($selectedEtabId === 0 && count($accessibleEtabs) === 1) {
            $selectedEtabId = (int)$accessibleEtabs[0]['id'];
        }

        $selectedDeptId = (int)$this->request->get('departement_id', 0);
        $search = trim((string)$this->request->get('q', ''));

        // Récupération des filières
        $items = $this->filiereModel->getByEtablissement(
            $selectedEtabId > 0 ? $selectedEtabId : null,
            $selectedDeptId > 0 ? $selectedDeptId : null
        );

        if ($search !== '') {
            $items = array_values(array_filter($items, function ($item) use ($search) {
                return stripos($item['libelle'], $search) !== false 
                    || stripos($item['code'], $search) !== false
                    || stripos($item['departement_nom'] ?? '', $search) !== false;
            }));
        }

        // Départements disponibles pour le filtrage et la création
        $departements = $this->deptModel->getByEtablissement($selectedEtabId > 0 ? $selectedEtabId : null);

        // Statistiques
        $counts = [
            'total' => count($items),
            'total_classes' => array_sum(array_column($items, 'nb_classes')),
            'total_etudiants' => array_sum(array_column($items, 'nb_etudiants')),
        ];

        $this->render('filieres.index', [
            'items' => $items,
            'counts' => $counts,
            'accessibleEtabs' => $accessibleEtabs,
            'selectedEtabId' => $selectedEtabId,
            'selectedDeptId' => $selectedDeptId,
            'departements' => $departements,
            'search' => $search,
            'title' => 'Filières & Séries'
        ]);
    }

    /**
     * Création d'une nouvelle filière
     */
    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $etabId = (int)$this->request->post('etablissement_id', Auth::getActiveEtablissementId() ?? 0);
        $code = strtoupper(trim((string)$this->request->post('code', '')));
        $libelle = trim((string)$this->request->post('libelle', ''));
        $deptId = $this->request->post('departement_id') ? (int)$this->request->post('departement_id') : null;

        if ($etabId <= 0) {
            Session::setFlash('danger', "Veuillez sélectionner un établissement valide.");
            $this->redirect('/filieres');
            return;
        }

        if ($code === '' || $libelle === '') {
            Session::setFlash('danger', "Le code et le libellé de la filière sont obligatoires.");
            $this->redirect('/filieres?etablissement_id=' . $etabId);
            return;
        }

        // Vérifier doublon de code
        $existing = $this->filiereModel->findAll([
            'etablissement_id' => $etabId,
            'code' => $code
        ]);
        if (!empty($existing)) {
            Session::setFlash('danger', "Une filière avec le code '{$code}' existe déjà dans cet établissement.");
            $this->redirect('/filieres?etablissement_id=' . $etabId);
            return;
        }

        $id = $this->filiereModel->create([
            'etablissement_id' => $etabId,
            'departement_id' => $deptId,
            'code' => $code,
            'libelle' => $libelle,
        ]);

        Logger::log(Auth::id(), $etabId, 'filiere.creer', 'filieres', $id, null, [
            'code' => $code,
            'libelle' => $libelle
        ], $this->request);

        Session::setFlash('success', "Filière '{$libelle}' créée avec succès.");
        $this->redirect('/filieres?etablissement_id=' . $etabId);
    }

    /**
     * Modification d'une filière existante
     */
    public function update(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $filiere = $this->filiereModel->findById($id);

        if (!$filiere) {
            Session::setFlash('danger', "Filière introuvable.");
            $this->redirect('/filieres');
            return;
        }

        $code = strtoupper(trim((string)$this->request->post('code', '')));
        $libelle = trim((string)$this->request->post('libelle', ''));
        $deptId = $this->request->post('departement_id') ? (int)$this->request->post('departement_id') : null;

        if ($code === '' || $libelle === '') {
            Session::setFlash('danger', "Le code et le libellé de la filière sont obligatoires.");
            $this->redirect('/filieres?etablissement_id=' . $filiere['etablissement_id']);
            return;
        }

        // Vérifier doublon de code (hors lui-même)
        $existing = $this->filiereModel->findAll([
            'etablissement_id' => (int)$filiere['etablissement_id'],
            'code' => $code
        ]);
        foreach ($existing as $ex) {
            if ((int)$ex['id'] !== $id) {
                Session::setFlash('danger', "Le code '{$code}' est déjà utilisé par une autre filière.");
                $this->redirect('/filieres?etablissement_id=' . $filiere['etablissement_id']);
                return;
            }
        }

        $this->filiereModel->update($id, [
            'code' => $code,
            'libelle' => $libelle,
            'departement_id' => $deptId
        ]);

        Logger::log(Auth::id(), (int)$filiere['etablissement_id'], 'filiere.modifier', 'filieres', $id, $filiere, [
            'code' => $code,
            'libelle' => $libelle
        ], $this->request);

        Session::setFlash('success', "Filière '{$libelle}' mise à jour avec succès.");
        $this->redirect('/filieres?etablissement_id=' . $filiere['etablissement_id']);
    }

    /**
     * Suppression d'une filière avec contrôle de dépendance
     */
    public function delete(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $filiere = $this->filiereModel->findById($id);

        if (!$filiere) {
            Session::setFlash('danger', "Filière introuvable.");
            $this->redirect('/filieres');
            return;
        }

        $deps = $this->filiereModel->getDependenciesCount($id);
        if ($deps['classes'] > 0 || $deps['etudiants'] > 0) {
            $reasons = [];
            if ($deps['classes'] > 0) $reasons[] = "{$deps['classes']} classe(s)";
            if ($deps['etudiants'] > 0) $reasons[] = "{$deps['etudiants']} étudiant(s)";
            
            Session::setFlash('danger', "Impossible de supprimer la filière '{$filiere['libelle']}' car elle contient des éléments actifs : " . implode(', ', $reasons) . ".");
            $this->redirect('/filieres?etablissement_id=' . $filiere['etablissement_id']);
            return;
        }

        $this->filiereModel->delete($id);

        Logger::log(Auth::id(), (int)$filiere['etablissement_id'], 'filiere.supprimer', 'filieres', $id, $filiere, null, $this->request);

        Session::setFlash('success', "Filière supprimée avec succès.");
        $this->redirect('/filieres?etablissement_id=' . $filiere['etablissement_id']);
    }
}
