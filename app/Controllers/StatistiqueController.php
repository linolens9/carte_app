<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Etablissement;
use PDO;

class StatistiqueController extends Controller
{
    private PDO $pdo;
    private Etablissement $etablissementModel;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->pdo = Database::getInstance();
        $this->etablissementModel = new Etablissement();
    }

    /**
     * Tableau de bord analytique et statistiques avancées
     */
    public function index(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $filterEtabId = $this->request->query('etablissement_id');
        $selectedEtabId = $filterEtabId !== null && $filterEtabId !== '' ? (int)$filterEtabId : $activeEtabId;

        $stats = $this->calculateStatistics($selectedEtabId);
        $etablissements = $this->etablissementModel->all();

        $this->render('statistiques/index', [
            'stats' => $stats,
            'etablissements' => $etablissements,
            'selectedEtabId' => $selectedEtabId,
        ]);
    }

    /**
     * Export des données statistiques au format CSV
     */
    public function exportCsv(array $params = []): void
    {
        $activeEtabId = Auth::getActiveEtablissementId();
        $filterEtabId = $this->request->query('etablissement_id');
        $selectedEtabId = $filterEtabId !== null && $filterEtabId !== '' ? (int)$filterEtabId : $activeEtabId;

        $stats = $this->calculateStatistics($selectedEtabId);

        if (ob_get_level()) {
            ob_end_clean();
        }
        ini_set('display_errors', '0');

        $filename = "statistiques_carte_app_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM pour Excel
        fputs($output, "\xEF\xBB\xBF");

        // En-tête du rapport
        fputcsv($output, ['RAPPORT STATISTIQUE - CARTE APP', date('d/m/Y H:i:s')], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        // 1. Indicateurs Globaux
        fputcsv($output, ['INDICATEUR', 'VALEUR'], ';', '"', "\\");
        fputcsv($output, ['Total Cartes Générées', $stats['kpi']['total_cartes']], ';', '"', "\\");
        fputcsv($output, ['Cartes Actives', $stats['kpi']['cartes_actives']], ';', '"', "\\");
        fputcsv($output, ['Cartes Imprimées', $stats['kpi']['cartes_imprimees']], ';', '"', "\\");
        fputcsv($output, ['Cartes Validées', $stats['kpi']['cartes_validees']], ';', '"', "\\");
        fputcsv($output, ['Cartes Expirées', $stats['kpi']['cartes_expirees']], ';', '"', "\\");
        fputcsv($output, ['Cartes Désactivées', $stats['kpi']['cartes_desactivees']], ';', '"', "\\");
        fputcsv($output, ['Total Bénéficiaires', $stats['kpi']['total_beneficiaires']], ';', '"', "\\");
        fputcsv($output, ['Élèves Actifs', $stats['kpi']['nb_eleves']], ';', '"', "\\");
        fputcsv($output, ['Étudiants Actifs', $stats['kpi']['nb_etudiants']], ';', '"', "\\");
        fputcsv($output, ['Personnels en Poste', $stats['kpi']['nb_personnels']], ';', '"', "\\");
        fputcsv($output, ['Taux de Photos Conformes (%)', $stats['kpi']['taux_photos'] . '%'], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        // 2. Répartition par Établissement
        fputcsv($output, ['ÉTABLISSEMENT', 'TYPE', 'BÉNÉFICIAIRES', 'CARTES PRODUITES', 'CARTES ACTIVES', 'CARTES IMPRIMÉES'], ';', '"', "\\");
        foreach ($stats['etabs_table'] as $row) {
            fputcsv($output, [
                $row['nom'],
                $row['type'],
                $row['total_beneficiaires'],
                $row['total_cartes'],
                $row['cartes_actives'],
                $row['cartes_imprimees'],
            ], ';', '"', "\\");
        }
        fputcsv($output, [], ';', '"', "\\");

        // 3. Évolution sur 12 mois
        fputcsv($output, ['MOIS', 'CARTES GÉNÉRÉES', 'CARTES IMPRIMÉES'], ';', '"', "\\");
        foreach ($stats['evolution']['labels'] as $idx => $mois) {
            fputcsv($output, [
                $mois,
                $stats['evolution']['creees'][$idx] ?? 0,
                $stats['evolution']['imprimees'][$idx] ?? 0,
            ], ';', '"', "\\");
        }

        fclose($output);
        exit;
    }

    /**
     * Calcul de l'ensemble des indicateurs et matrices de graphiques
     */
    private function calculateStatistics(?int $etabId = null): array
    {
        $etabFilter = "";
        $params = [];
        if ($etabId !== null) {
            $etabFilter = " AND etablissement_id = :eid";
            $params[':eid'] = $etabId;
        }

        // 1. Bénéficiaires
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM eleves WHERE deleted_at IS NULL AND statut = 'actif' $etabFilter");
        $stmt->execute($params);
        $nbEleves = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE deleted_at IS NULL AND statut = 'actif' $etabFilter");
        $stmt->execute($params);
        $nbEtudiants = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM personnels WHERE deleted_at IS NULL AND statut = 'en_poste' $etabFilter");
        $stmt->execute($params);
        $nbPersonnels = (int)$stmt->fetchColumn();

        $totalBeneficiaires = $nbEleves + $nbEtudiants + $nbPersonnels;

        // Photos
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM eleves WHERE deleted_at IS NULL AND statut = 'actif' AND photo IS NOT NULL AND photo != '' $etabFilter");
        $stmt->execute($params);
        $nbPhotosEleves = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE deleted_at IS NULL AND statut = 'actif' AND photo IS NOT NULL AND photo != '' $etabFilter");
        $stmt->execute($params);
        $nbPhotosEtudiants = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM personnels WHERE deleted_at IS NULL AND statut = 'en_poste' AND photo IS NOT NULL AND photo != '' $etabFilter");
        $stmt->execute($params);
        $nbPhotosPersonnels = (int)$stmt->fetchColumn();

        $totalAvecPhoto = $nbPhotosEleves + $nbPhotosEtudiants + $nbPhotosPersonnels;
        $tauxPhotos = $totalBeneficiaires > 0 ? round(($totalAvecPhoto / $totalBeneficiaires) * 100, 1) : 0.0;

        // Répartition Sexe
        $sexeParams = [];
        $sexeFilter1 = "";
        $sexeFilter2 = "";
        if ($etabId !== null) {
            $sexeFilter1 = " AND etablissement_id = :eid1";
            $sexeFilter2 = " AND etablissement_id = :eid2";
            $sexeParams = [':eid1' => $etabId, ':eid2' => $etabId];
        }
        $stmt = $this->pdo->prepare("
            SELECT sexe, SUM(nb) as total FROM (
                SELECT sexe, COUNT(*) as nb FROM eleves WHERE deleted_at IS NULL AND statut = 'actif' $sexeFilter1 GROUP BY sexe
                UNION ALL
                SELECT sexe, COUNT(*) as nb FROM etudiants WHERE deleted_at IS NULL AND statut = 'actif' $sexeFilter2 GROUP BY sexe
            ) u GROUP BY sexe
        ");
        $stmt->execute($sexeParams);
        $sexeRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $nbHommes = (int)($sexeRows['M'] ?? 0);
        $nbFemmes = (int)($sexeRows['F'] ?? 0);

        // 2. Cartes
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cartes WHERE deleted_at IS NULL $etabFilter");
        $stmt->execute($params);
        $totalCartes = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT statut, COUNT(*) as total
            FROM cartes
            WHERE deleted_at IS NULL $etabFilter
            GROUP BY statut
        ");
        $stmt->execute($params);
        $statutsRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $cartesBrouillon = (int)($statutsRows['brouillon'] ?? 0);
        $cartesValidees = (int)($statutsRows['validee'] ?? 0);
        $cartesImprimees = (int)($statutsRows['imprimee'] ?? 0);
        $cartesActives = (int)($statutsRows['active'] ?? 0);
        $cartesExpirees = (int)($statutsRows['expiree'] ?? 0);
        $cartesDesactivees = (int)($statutsRows['desactivee'] ?? 0);
        $cartesArchivees = (int)($statutsRows['archivee'] ?? 0);

        // 3. Évolution mensuelle sur 12 mois
        $moisLabels = [];
        $creeesData = [];
        $imprimeesData = [];

        for ($i = 11; $i >= 0; $i--) {
            $anneeMois = date('Y-m', strtotime("-$i months"));
            $moisLabels[] = date('M Y', strtotime("-$i months"));

            // Cartes créées ce mois
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM cartes
                WHERE deleted_at IS NULL AND DATE_FORMAT(created_at, '%Y-%m') = :m $etabFilter
            ");
            $mParams = $params;
            $mParams[':m'] = $anneeMois;
            $stmt->execute($mParams);
            $creeesData[] = (int)$stmt->fetchColumn();

            // Cartes imprimées ce mois (d'après table impressions ou date_impression)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM impressions i
                JOIN cartes c ON i.carte_id = c.id
                WHERE DATE_FORMAT(i.imprime_le, '%Y-%m') = :m " . ($etabId !== null ? "AND c.etablissement_id = :eid" : "") . "
            ");
            $stmt->execute($mParams);
            $imprimeesData[] = (int)$stmt->fetchColumn();
        }

        // 4. Données détaillées par établissement
        $sqlEtabs = "
            SELECT e.id, e.nom, e.code, e.type, e.statut,
                   (SELECT COUNT(*) FROM eleves el WHERE el.etablissement_id = e.id AND el.deleted_at IS NULL AND el.statut = 'actif') as nb_el,
                   (SELECT COUNT(*) FROM etudiants et WHERE et.etablissement_id = e.id AND et.deleted_at IS NULL AND et.statut = 'actif') as nb_et,
                   (SELECT COUNT(*) FROM personnels p WHERE p.etablissement_id = e.id AND p.deleted_at IS NULL AND p.statut = 'en_poste') as nb_pe,
                   (SELECT COUNT(*) FROM cartes c WHERE c.etablissement_id = e.id AND c.deleted_at IS NULL) as total_c,
                   (SELECT COUNT(*) FROM cartes c WHERE c.etablissement_id = e.id AND c.statut = 'active' AND c.deleted_at IS NULL) as actives_c,
                   (SELECT COUNT(*) FROM cartes c WHERE c.etablissement_id = e.id AND c.statut IN ('imprimee', 'active') AND c.deleted_at IS NULL) as imp_c
            FROM etablissements e
            WHERE e.deleted_at IS NULL " . ($etabId !== null ? "AND e.id = {$etabId}" : "") . "
            ORDER BY total_c DESC, e.nom ASC
        ";
        $etabsQuery = $this->pdo->query($sqlEtabs)->fetchAll(PDO::FETCH_ASSOC);

        $etabsTable = [];
        $topLabels = [];
        $topData = [];

        foreach ($etabsQuery as $row) {
            $totB = (int)$row['nb_el'] + (int)$row['nb_et'] + (int)$row['nb_pe'];
            $etabsTable[] = [
                'id' => (int)$row['id'],
                'nom' => $row['nom'],
                'code' => $row['code'],
                'type' => ucfirst($row['type']),
                'total_beneficiaires' => $totB,
                'total_cartes' => (int)$row['total_c'],
                'cartes_actives' => (int)$row['actives_c'],
                'cartes_imprimees' => (int)$row['imp_c'],
                'taux_couverture' => $totB > 0 ? round(((int)$row['actives_c'] / $totB) * 100, 1) : 0,
            ];

            if (count($topLabels) < 8) {
                $topLabels[] = $row['nom'];
                $topData[] = (int)$row['total_c'];
            }
        }

        return [
            'kpi' => [
                'total_cartes' => $totalCartes,
                'cartes_actives' => $cartesActives,
                'cartes_imprimees' => $cartesImprimees,
                'cartes_validees' => $cartesValidees,
                'cartes_expirees' => $cartesExpirees,
                'cartes_desactivees' => $cartesDesactivees,
                'cartes_archivees' => $cartesArchivees,
                'cartes_brouillon' => $cartesBrouillon,
                'total_beneficiaires' => $totalBeneficiaires,
                'nb_eleves' => $nbEleves,
                'nb_etudiants' => $nbEtudiants,
                'nb_personnels' => $nbPersonnels,
                'total_avec_photo' => $totalAvecPhoto,
                'taux_photos' => $tauxPhotos,
                'nb_hommes' => $nbHommes,
                'nb_femmes' => $nbFemmes,
            ],
            'evolution' => [
                'labels' => $moisLabels,
                'creees' => $creeesData,
                'imprimees' => $imprimeesData,
            ],
            'statuts_chart' => [
                'labels' => ['Brouillons', 'Validées', 'Imprimées', 'Actives', 'Expirées', 'Désactivées', 'Archivées'],
                'data' => [$cartesBrouillon, $cartesValidees, $cartesImprimees, $cartesActives, $cartesExpirees, $cartesDesactivees, $cartesArchivees],
            ],
            'beneficiaires_chart' => [
                'labels' => ['Élèves', 'Étudiants', 'Personnel'],
                'data' => [$nbEleves, $nbEtudiants, $nbPersonnels],
            ],
            'top_etabs' => [
                'labels' => $topLabels,
                'data' => $topData,
            ],
            'etabs_table' => $etabsTable,
        ];
    }
}
