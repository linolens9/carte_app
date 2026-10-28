<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Departement;
use App\Models\Etablissement;

/**
 * Contrôleur CRUD complet pour Départements & Services
 */
class DepartementController extends Controller
{
    private Departement $deptModel;
    private Etablissement $etabModel;

    public function __construct(\App\Core\Request $request, \App\Core\Response $response)
    {
        parent::__construct($request, $response);
        $this->deptModel = new Departement();
        $this->etabModel = new Etablissement();
    }

    /**
     * Liste et interface CRUD des départements & services
     */
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $selectedEtabId = (int)$this->request->get('etablissement_id', Auth::getActiveEtablissementId() ?? 0);
        
        // Si aucun établissement spécifié et qu'un seul est accessible, le sélectionner
        if ($selectedEtabId === 0 && count($accessibleEtabs) === 1) {
            $selectedEtabId = (int)$accessibleEtabs[0]['id'];
        }

        $filterType = (string)$this->request->get('type', '');
        $search = trim((string)$this->request->get('q', ''));

        // Récupération des départements & services
        $items = $this->deptModel->getByEtablissement(
            $selectedEtabId > 0 ? $selectedEtabId : null,
            $filterType !== '' ? $filterType : null
        );

        // Filtrage textuel si recherche demandée
        if ($search !== '') {
            $items = array_values(array_filter($items, function ($item) use ($search) {
                return stripos($item['nom'], $search) !== false 
                    || stripos($item['code'], $search) !== false
                    || stripos($item['parent_nom'] ?? '', $search) !== false;
            }));
        }

        // Statistiques
        $counts = [
            'total' => count($items),
            'departements' => count(array_filter($items, fn($i) => ($i['type'] ?? 'departement') === 'departement')),
            'services' => count(array_filter($items, fn($i) => ($i['type'] ?? 'departement') === 'service')),
        ];

        // Liste des départements parents éligibles pour rattacher un service
        $parentDepts = $this->deptModel->getByEtablissement($selectedEtabId > 0 ? $selectedEtabId : null, 'departement');

        $this->render('departements.index', [
            'items' => $items,
            'counts' => $counts,
            'accessibleEtabs' => $accessibleEtabs,
            'selectedEtabId' => $selectedEtabId,
            'filterType' => $filterType,
            'search' => $search,
            'parentDepts' => $parentDepts,
            'title' => 'Départements & Services'
        ]);
    }

    /**
     * Création d'un département ou service
     */
    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $etabId = (int)$this->request->post('etablissement_id', Auth::getActiveEtablissementId() ?? 0);
        $code = strtoupper(trim((string)$this->request->post('code', '')));
        $nom = trim((string)$this->request->post('nom', ''));
        $type = (string)$this->request->post('type', 'departement');
        $parentId = $this->request->post('parent_id') ? (int)$this->request->post('parent_id') : null;

        if (!in_array($type, ['departement', 'service'], true)) {
            $type = 'departement';
        }

        if ($etabId <= 0) {
            Session::setFlash('danger', "Veuillez sélectionner un établissement valide.");
            $this->redirect('/departements');
            return;
        }

        if ($code === '' || $nom === '') {
            Session::setFlash('danger', "Le code et le nom sont obligatoires.");
            $this->redirect('/departements?etablissement_id=' . $etabId);
            return;
        }

        // Vérification de doublon de code au sein du même établissement
        $existing = $this->deptModel->findAll([
            'etablissement_id' => $etabId,
            'code' => $code
        ]);
        if (!empty($existing)) {
            Session::setFlash('danger', "Un département ou service avec le code '{$code}' existe déjà dans cet établissement.");
            $this->redirect('/departements?etablissement_id=' . $etabId);
            return;
        }

        $id = $this->deptModel->create([
            'etablissement_id' => $etabId,
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
            'parent_id' => $parentId
        ]);

        Logger::log(Auth::id(), $etabId, 'departement.creer', 'departements', $id, null, [
            'code' => $code,
            'nom' => $nom,
            'type' => $type
        ], $this->request);

        $label = $type === 'service' ? 'Service' : 'Département';
        Session::setFlash('success', "{$label} '{$nom}' créé avec succès.");
        $this->redirect('/departements?etablissement_id=' . $etabId);
    }

    /**
     * Modification d'un département ou service
     */
    public function update(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $dept = $this->deptModel->findById($id);

        if (!$dept) {
            Session::setFlash('danger', "Département ou service introuvable.");
            $this->redirect('/departements');
            return;
        }

        $code = strtoupper(trim((string)$this->request->post('code', '')));
        $nom = trim((string)$this->request->post('nom', ''));
        $type = (string)$this->request->post('type', $dept['type'] ?? 'departement');
        $parentId = $this->request->post('parent_id') ? (int)$this->request->post('parent_id') : null;

        if (!in_array($type, ['departement', 'service'], true)) {
            $type = 'departement';
        }

        if ($code === '' || $nom === '') {
            Session::setFlash('danger', "Le code et le nom sont obligatoires.");
            $this->redirect('/departements?etablissement_id=' . $dept['etablissement_id']);
            return;
        }

        // Éviter qu'un département soit son propre parent
        if ($parentId === $id) {
            $parentId = null;
        }

        // Vérifier unicité du code hors de soi-même
        $existing = $this->deptModel->findAll([
            'etablissement_id' => (int)$dept['etablissement_id'],
            'code' => $code
        ]);
        foreach ($existing as $ex) {
            if ((int)$ex['id'] !== $id) {
                Session::setFlash('danger', "Le code '{$code}' est déjà utilisé par une autre structure.");
                $this->redirect('/departements?etablissement_id=' . $dept['etablissement_id']);
                return;
            }
        }

        $this->deptModel->update($id, [
            'code' => $code,
            'nom' => $nom,
            'type' => $type,
            'parent_id' => $parentId
        ]);

        Logger::log(Auth::id(), (int)$dept['etablissement_id'], 'departement.modifier', 'departements', $id, $dept, [
            'code' => $code,
            'nom' => $nom,
            'type' => $type
        ], $this->request);

        $label = $type === 'service' ? 'Service' : 'Département';
        Session::setFlash('success', "{$label} '{$nom}' mis à jour avec succès.");
        $this->redirect('/departements?etablissement_id=' . $dept['etablissement_id']);
    }

    /**
     * Suppression sécurisée avec vérification des dépendances
     */
    public function delete(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $dept = $this->deptModel->findById($id);

        if (!$dept) {
            Session::setFlash('danger', "Élément introuvable.");
            $this->redirect('/departements');
            return;
        }

        // Contrôle d'intégrité
        $deps = $this->deptModel->getDependenciesCount($id);
        if ($deps['personnels'] > 0 || $deps['filieres'] > 0 || $deps['enfants'] > 0) {
            $reasons = [];
            if ($deps['personnels'] > 0) $reasons[] = "{$deps['personnels']} personnel(s) rattaché(s)";
            if ($deps['filieres'] > 0) $reasons[] = "{$deps['filieres']} filière(s) rattachée(s)";
            if ($deps['enfants'] > 0) $reasons[] = "{$deps['enfants']} sous-service(s) rattaché(s)";
            
            Session::setFlash('danger', "Impossible de supprimer '{$dept['nom']}' car des éléments y sont liés : " . implode(', ', $reasons) . ".");
            $this->redirect('/departements?etablissement_id=' . $dept['etablissement_id']);
            return;
        }

        $this->deptModel->delete($id);

        Logger::log(Auth::id(), (int)$dept['etablissement_id'], 'departement.supprimer', 'departements', $id, $dept, null, $this->request);

        Session::setFlash('success', "Élément supprimé avec succès.");
        $this->redirect('/departements?etablissement_id=' . $dept['etablissement_id']);
    }
}
