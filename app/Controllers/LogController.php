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

class LogController extends Controller
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
     * Liste et filtre du journal d'audit
     */
    public function index(array $params = []): void
    {
        $page = max(1, (int)$this->request->query('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $actionFilter = $this->request->query('action');
        $moduleFilter = $this->request->query('module');
        $dateDebut = $this->request->query('date_debut');
        $dateFin = $this->request->query('date_fin');
        $filterEtabId = $this->request->query('etablissement_id');

        $where = ["1=1"];
        $queryParams = [];

        if (!empty($actionFilter)) {
            $where[] = "l.action LIKE :action";
            $queryParams[':action'] = "%{$actionFilter}%";
        }
        if (!empty($moduleFilter)) {
            $where[] = "l.objet_type = :module";
            $queryParams[':module'] = $moduleFilter;
        }
        if (!empty($dateDebut)) {
            $where[] = "DATE(l.created_at) >= :date_debut";
            $queryParams[':date_debut'] = $dateDebut;
        }
        if (!empty($dateFin)) {
            $where[] = "DATE(l.created_at) <= :date_fin";
            $queryParams[':date_fin'] = $dateFin;
        }
        if (!empty($filterEtabId)) {
            $where[] = "l.etablissement_id = :eid";
            $queryParams[':eid'] = (int)$filterEtabId;
        }

        $whereSql = implode(" AND ", $where);

        // Total pour pagination
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs l WHERE {$whereSql}");
        $countStmt->execute($queryParams);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = ceil($total / $limit);

        // Logs
        $sql = "
            SELECT l.*, u.nom as user_nom, u.prenom as user_prenom, u.email as user_email, e.nom as etab_nom
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN etablissements e ON l.etablissement_id = e.id
            WHERE {$whereSql}
            ORDER BY l.created_at DESC
            LIMIT {$offset}, {$limit}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($queryParams);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $modules = $this->pdo->query("SELECT DISTINCT objet_type FROM logs WHERE objet_type IS NOT NULL AND objet_type != '' ORDER BY objet_type")->fetchAll(PDO::FETCH_COLUMN);
        $etablissements = $this->etablissementModel->all();

        $this->render('logs/index', [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'modules' => $modules,
            'etablissements' => $etablissements,
            'filters' => [
                'action' => $actionFilter,
                'module' => $moduleFilter,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'etablissement_id' => $filterEtabId,
            ]
        ]);
    }

    /**
     * Export du journal au format CSV
     */
    public function exportCsv(array $params = []): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ini_set('display_errors', '0');

        $filename = "audit_logs_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        fputcsv($output, ['ID', 'DATE', 'UTILISATEUR', 'EMAIL', 'ÉTABLISSEMENT', 'ACTION', 'MODULE', 'ID OBJET', 'ADRESSE IP', 'DONNÉES'], ';', '"', "\\");

        $stmt = $this->pdo->query("
            SELECT l.*, u.nom as user_nom, u.prenom as user_prenom, u.email as user_email, e.nom as etab_nom
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN etablissements e ON l.etablissement_id = e.id
            ORDER BY l.created_at DESC
            LIMIT 5000
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = !empty($row['user_nom']) ? "{$row['user_prenom']} {$row['user_nom']}" : 'Système';
            $details = !empty($row['donnees_apres']) ? $row['donnees_apres'] : '';
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $user,
                $row['user_email'] ?? '',
                $row['etab_nom'] ?? 'Global',
                $row['action'],
                $row['objet_type'] ?? '',
                $row['objet_id'] ?? '',
                $row['adresse_ip'] ?? '',
                $details,
            ], ';', '"', "\\");
        }

        fclose($output);
        exit;
    }
}
