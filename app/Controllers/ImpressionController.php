<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Carte;
use App\Models\Etablissement;
use App\Core\Database;
use PDO;

class ImpressionController extends Controller
{
    private Carte $carteModel;
    private Etablissement $etablissementModel;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->carteModel = new Carte();
        $this->etablissementModel = new Etablissement();
    }

    public function index(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();

        $pdo = Database::getInstance();

        $sql = "
            SELECT imp.*, c.numero_carte, c.beneficiaire_type,
                   u.nom AS user_nom, u.prenom AS user_prenom,
                   e.nom AS etablissement_nom
            FROM impressions imp
            JOIN cartes c ON imp.carte_id = c.id
            JOIN etablissements e ON c.etablissement_id = e.id
            LEFT JOIN users u ON imp.user_id = u.id
        ";
        $sqlParams = [];
        if ($activeEtabId) {
            $sql .= " WHERE c.etablissement_id = ?";
            $sqlParams[] = (int)$activeEtabId;
        }
        $sql .= " ORDER BY imp.id DESC LIMIT 30";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($sqlParams);
        $recentImpressions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cartes en attente d'impression (statut = 'validee')
        $cartesEnAttente = $this->carteModel->all([
            'statut' => 'validee',
            'etablissement_id' => $activeEtabId
        ], 100);

        $this->render('impressions/index', [
            'title' => 'Impression & Planches A4',
            'recentImpressions' => $recentImpressions,
            'cartesEnAttente' => $cartesEnAttente,
            'active_etab_id' => $activeEtabId
        ]);
    }

    public function printSingle(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);
        $carte = $this->carteModel->find($id);

        if (!$carte) {
            Session::setFlash('danger', 'Carte introuvable.');
            $this->response->redirect('/cartes');
            return;
        }

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 1);

        $estReimp = in_array($carte['statut'], ['imprimee', 'active']);
        $this->carteModel->marquerImprimee($id, $userId, 'impression', 1, $estReimp, $estReimp ? 'Réimpression carte unique' : 'Impression initiale');

        $this->render('impressions/single', [
            'title' => 'Impression Carte ' . $carte['numero_carte'],
            'card' => $carte,
            'mode' => $this->request->query('face') ?: 'both'
        ], 'blank');
    }

    public function printA4Sheet(array $params = []): void
    {
        $ids = $this->request->post('cartes_ids') ?: (explode(',', (string)$this->request->query('ids')));
        $ids = array_filter(array_map('intval', (array)$ids));

        if (empty($ids)) {
            Session::setFlash('warning', 'Aucune carte sélectionnée pour l\'impression.');
            $this->response->redirect('/cartes');
            return;
        }

        $cartes = [];
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 1);

        foreach ($ids as $id) {
            $c = $this->carteModel->find($id);
            if ($c) {
                $cartes[] = $c;
                $estReimp = in_array($c['statut'], ['imprimee', 'active']);
                $this->carteModel->marquerImprimee($id, $userId, 'impression', 1, $estReimp, 'Impression sur planche A4');
            }
        }

        $face = $this->request->query('face') ?: 'duplex';

        $this->render('impressions/sheet_a4', [
            'title' => 'Planche d\'impression A4 (' . count($cartes) . ' cartes)',
            'cartes' => $cartes,
            'face' => $face
        ], 'blank');
    }
}
