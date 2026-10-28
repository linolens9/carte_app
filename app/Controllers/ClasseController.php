<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Logger;
use App\Models\Classe;
use App\Models\Filiere;
use App\Models\Etablissement;

/**
 * Contrôleur CRUD complet pour les Classes scolaires & académiques
 */
class ClasseController extends Controller
{
    private Classe $classeModel;
    private Filiere $filiereModel;
    private Etablissement $etabModel;

    public function __construct(\App\Core\Request $request, \App\Core\Response $response)
    {
        parent::__construct($request, $response);
        $this->classeModel = new Classe();
        $this->filiereModel = new Filiere();
        $this->etabModel = new Etablissement();
    }

    /**
     * Liste et interface CRUD des classes
     */
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $accessibleEtabs = Auth::getAccessibleEtablissements();
        $selectedEtabId = (int)$this->request->query('etablissement_id', Auth::getActiveEtablissementId() ?? 0);

        if ($selectedEtabId === 0 && count($accessibleEtabs) === 1) {
            $selectedEtabId = (int)$accessibleEtabs[0]['id'];
        }

        $selectedFiliereId = (int)$this->request->query('filiere_id', 0);
        $niveau = trim((string)$this->request->query('niveau', ''));
        $annee = trim((string)$this->request->query('annee', ''));
        $search = trim((string)$this->request->query('q', ''));

        // Récupération des classes
        $items = $this->classeModel->getByEtablissement(
            $selectedEtabId > 0 ? $selectedEtabId : null,
            $selectedFiliereId > 0 ? $selectedFiliereId : null,
            $niveau !== '' ? $niveau : null,
            $annee !== '' ? $annee : null
        );

        if ($search !== '') {
            $items = array_values(array_filter($items, function ($item) use ($search) {
                return stripos($item['libelle'], $search) !== false 
                    || stripos($item['niveau'], $search) !== false
                    || stripos($item['annee_scolaire'], $search) !== false
                    || stripos($item['filiere_libelle'] ?? '', $search) !== false;
            }));
        }

        // Filières disponibles pour cet établissement
        $filieres = $this->filiereModel->getByEtablissement($selectedEtabId > 0 ? $selectedEtabId : null);

        // Niveaux et années scolaires distincts pour les filtres
        $niveauxList = array_values(array_unique(array_filter(array_column($items, 'niveau'))));
        sort($niveauxList);
        $anneesList = array_values(array_unique(array_filter(array_column($items, 'annee_scolaire'))));
        rsort($anneesList);

        // Statistiques
        $counts = [
            'total' => count($items),
            'total_eleves' => array_sum(array_column($items, 'nb_eleves')),
        ];

        $this->render('classes.index', [
            'items' => $items,
            'counts' => $counts,
            'accessibleEtabs' => $accessibleEtabs,
            'selectedEtabId' => $selectedEtabId,
            'selectedFiliereId' => $selectedFiliereId,
            'filieres' => $filieres,
            'niveauxList' => $niveauxList,
            'anneesList' => $anneesList,
            'niveau' => $niveau,
            'annee' => $annee,
            'search' => $search,
            'title' => 'Gestion des Classes'
        ]);
    }

    /**
     * Création d'une nouvelle classe
     */
    public function store(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $etabId = (int)$this->request->post('etablissement_id', Auth::getActiveEtablissementId() ?? 0);
        $libelle = trim((string)$this->request->post('libelle', ''));
        $niveau = trim((string)$this->request->post('niveau', ''));
        $annee = trim((string)$this->request->post('annee_scolaire', date('Y') . '-' . (date('Y') + 1)));
        $filiereId = $this->request->post('filiere_id') ? (int)$this->request->post('filiere_id') : null;
        $effectifMax = $this->request->post('effectif_max') ? (int)$this->request->post('effectif_max') : null;

        if ($etabId <= 0) {
            Session::setFlash('danger', "Veuillez sélectionner un établissement valide.");
            $this->redirect('/classes');
            return;
        }

        if ($libelle === '' || $niveau === '') {
            Session::setFlash('danger', "Le libellé et le niveau de la classe sont obligatoires.");
            $this->redirect('/classes?etablissement_id=' . $etabId);
            return;
        }

        // Vérification doublon de classe pour la même année scolaire
        $existing = $this->classeModel->findAll([
            'etablissement_id' => $etabId,
            'libelle' => $libelle,
            'annee_scolaire' => $annee
        ]);
        if (!empty($existing)) {
            Session::setFlash('danger', "Une classe '{$libelle}' existe déjà pour l'année scolaire {$annee} dans cet établissement.");
            $this->redirect('/classes?etablissement_id=' . $etabId);
            return;
        }

        $id = $this->classeModel->create([
            'etablissement_id' => $etabId,
            'filiere_id' => $filiereId,
            'libelle' => $libelle,
            'niveau' => $niveau,
            'annee_scolaire' => $annee,
            'effectif_max' => $effectifMax,
        ]);

        Logger::log(Auth::id(), $etabId, 'classe.creer', 'classes', $id, null, [
            'libelle' => $libelle,
            'niveau' => $niveau,
            'annee_scolaire' => $annee
        ], $this->request);

        Session::setFlash('success', "Classe '{$libelle}' créée avec succès.");
        $this->redirect('/classes?etablissement_id=' . $etabId);
    }

    /**
     * Modification d'une classe existante
     */
    public function update(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $classe = $this->classeModel->findById($id);

        if (!$classe) {
            Session::setFlash('danger', "Classe introuvable.");
            $this->redirect('/classes');
            return;
        }

        $libelle = trim((string)$this->request->post('libelle', ''));
        $niveau = trim((string)$this->request->post('niveau', ''));
        $annee = trim((string)$this->request->post('annee_scolaire', $classe['annee_scolaire']));
        $filiereId = $this->request->post('filiere_id') ? (int)$this->request->post('filiere_id') : null;
        $effectifMax = $this->request->post('effectif_max') ? (int)$this->request->post('effectif_max') : null;

        if ($libelle === '' || $niveau === '') {
            Session::setFlash('danger', "Le libellé et le niveau de la classe sont obligatoires.");
            $this->redirect('/classes?etablissement_id=' . $classe['etablissement_id']);
            return;
        }

        // Vérification doublon hors de lui-même
        $existing = $this->classeModel->findAll([
            'etablissement_id' => (int)$classe['etablissement_id'],
            'libelle' => $libelle,
            'annee_scolaire' => $annee
        ]);
        foreach ($existing as $ex) {
            if ((int)$ex['id'] !== $id) {
                Session::setFlash('danger', "Une autre classe nommée '{$libelle}' existe déjà pour l'année {$annee}.");
                $this->redirect('/classes?etablissement_id=' . $classe['etablissement_id']);
                return;
            }
        }

        $this->classeModel->update($id, [
            'filiere_id' => $filiereId,
            'libelle' => $libelle,
            'niveau' => $niveau,
            'annee_scolaire' => $annee,
            'effectif_max' => $effectifMax,
        ]);

        Logger::log(Auth::id(), (int)$classe['etablissement_id'], 'classe.modifier', 'classes', $id, $classe, [
            'libelle' => $libelle,
            'niveau' => $niveau,
            'annee_scolaire' => $annee
        ], $this->request);

        Session::setFlash('success', "Classe '{$libelle}' mise à jour avec succès.");
        $this->redirect('/classes?etablissement_id=' . $classe['etablissement_id']);
    }

    /**
     * Suppression d'une classe avec contrôle des élèves
     */
    public function delete(array $params): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $classe = $this->classeModel->findById($id);

        if (!$classe) {
            Session::setFlash('danger', "Classe introuvable.");
            $this->redirect('/classes');
            return;
        }

        $deps = $this->classeModel->getDependenciesCount($id);
        if ($deps['eleves'] > 0) {
            Session::setFlash('danger', "Impossible de supprimer la classe '{$classe['libelle']}' car {$deps['eleves']} élève(s) y sont actuellement inscrit(s). Réaffectez d'abord ces élèves.");
            $this->redirect('/classes?etablissement_id=' . $classe['etablissement_id']);
            return;
        }

        $this->classeModel->delete($id);

        Logger::log(Auth::id(), (int)$classe['etablissement_id'], 'classe.supprimer', 'classes', $id, $classe, null, $this->request);

        Session::setFlash('success', "Classe supprimée avec succès.");
        $this->redirect('/classes?etablissement_id=' . $classe['etablissement_id']);
    }
}
