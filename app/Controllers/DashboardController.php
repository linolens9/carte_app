<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use PDO;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $user = Auth::user();
        $activeEtabId = Auth::getActiveEtablissementId();

        // Clause de filtre par établissement si ce n'est pas un admin ou si un établissement précis est sélectionné
        if (!Auth::hasRole('admin')) {
            $accessibleEtabIds = Auth::getAccessibleEtablissementIds();
            if ($activeEtabId === null || !in_array($activeEtabId, $accessibleEtabIds, true)) {
                $activeEtabId = $accessibleEtabIds[0] ?? null;
                if ($activeEtabId !== null) {
                    Auth::setActiveEtablissementId($activeEtabId);
                }
            }
        }

        $etabFilter = "";
        $params = [];
        if ($activeEtabId !== null) {
            $etabFilter = " AND etablissement_id = :active_eid";
            $params[':active_eid'] = $activeEtabId;
        }

        // 1. KPI Cards
        // Etablissements count
        if (Auth::hasRole('admin')) {
            $nbEtablissements = (int)$db->query("SELECT COUNT(*) FROM etablissements WHERE deleted_at IS NULL AND statut != 'archive'")->fetchColumn();
        } else {
            $nbEtablissements = count(Auth::getAccessibleEtablissements());
        }

        // Eleves
        $stmt = $db->prepare("SELECT COUNT(*) FROM eleves WHERE deleted_at IS NULL AND statut = 'actif' $etabFilter");
        $stmt->execute($params);
        $nbEleves = (int)$stmt->fetchColumn();

        // Etudiants
        $stmt = $db->prepare("SELECT COUNT(*) FROM etudiants WHERE deleted_at IS NULL AND statut = 'actif' $etabFilter");
        $stmt->execute($params);
        $nbEtudiants = (int)$stmt->fetchColumn();

        // Personnels
        $stmt = $db->prepare("SELECT COUNT(*) FROM personnels WHERE deleted_at IS NULL AND statut = 'en_poste' $etabFilter");
        $stmt->execute($params);
        $nbPersonnels = (int)$stmt->fetchColumn();

        // Cartes
        $stmt = $db->prepare("SELECT COUNT(*) FROM cartes WHERE deleted_at IS NULL $etabFilter");
        $stmt->execute($params);
        $nbCartes = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM cartes WHERE deleted_at IS NULL AND statut = 'active' $etabFilter");
        $stmt->execute($params);
        $nbCartesActives = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM cartes WHERE deleted_at IS NULL AND (statut = 'expiree' OR (date_expiration < CURDATE() AND statut = 'active')) $etabFilter");
        $stmt->execute($params);
        $nbCartesExpirees = (int)$stmt->fetchColumn();

        // 2. Alertes
        // Cartes expirant dans les 30 prochains jours
        $stmt = $db->prepare("
            SELECT c.*, e.nom AS etab_nom
            FROM cartes c
            JOIN etablissements e ON c.etablissement_id = e.id
            WHERE c.deleted_at IS NULL AND c.statut = 'active'
              AND c.date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              $etabFilter
            ORDER BY c.date_expiration ASC
            LIMIT 5
        ");
        $stmt->execute($params);
        $cartesBientotExpirees = $stmt->fetchAll();

        // Bénéficiaires sans photo
        $unionFilter = "";
        $unionParams = [];
        if ($activeEtabId !== null) {
            $unionFilter1 = " AND etablissement_id = :active_eid1";
            $unionFilter2 = " AND etablissement_id = :active_eid2";
            $unionFilter3 = " AND etablissement_id = :active_eid3";
            $unionParams = [
                ':active_eid1' => $activeEtabId,
                ':active_eid2' => $activeEtabId,
                ':active_eid3' => $activeEtabId,
            ];
        } else {
            $unionFilter1 = "";
            $unionFilter2 = "";
            $unionFilter3 = "";
        }

        $stmt = $db->prepare("
            SELECT 'eleve' as type, nom, prenom, matricule, created_at FROM eleves WHERE (photo IS NULL OR photo = '') AND deleted_at IS NULL $unionFilter1
            UNION ALL
            SELECT 'etudiant' as type, nom, prenom, matricule, created_at FROM etudiants WHERE (photo IS NULL OR photo = '') AND deleted_at IS NULL $unionFilter2
            UNION ALL
            SELECT 'personnel' as type, nom, prenom, numero_professionnel as matricule, created_at FROM personnels WHERE (photo IS NULL OR photo = '') AND deleted_at IS NULL $unionFilter3
            LIMIT 5
        ");
        $stmt->execute($unionParams);
        $sansPhoto = $stmt->fetchAll();

        // 3. Activités récentes (logs) - visible uniquement par l'administrateur et le chef d'établissement (responsable)
        $recentLogs = [];
        if (Auth::hasRole('admin', 'responsable', 'chef_etablissement')) {
            $logFilter = "";
            $logParams = [];
            if ($activeEtabId !== null) {
                if (Auth::hasRole('admin')) {
                    $logFilter = "WHERE l.etablissement_id = :eid OR l.etablissement_id IS NULL";
                } else {
                    $logFilter = "WHERE l.etablissement_id = :eid";
                }
                $logParams[':eid'] = $activeEtabId;
            }
            $stmt = $db->prepare("
                SELECT l.*, u.nom AS user_nom, u.prenom AS user_prenom, e.nom AS etab_nom
                FROM logs l
                LEFT JOIN users u ON l.user_id = u.id
                LEFT JOIN etablissements e ON l.etablissement_id = e.id
                $logFilter
                ORDER BY l.created_at DESC
                LIMIT 15
            ");
            $stmt->execute($logParams);
            $recentLogs = $stmt->fetchAll();
        }

        // 4. Données pour graphiques
        // Répartition élèves / étudiants / personnels selon le type de structure
        $activeEtab = Auth::getActiveEtablissement();
        $etabType = $activeEtab['type'] ?? '';
        $etabCat = $activeEtab['categorie'] ?? '';
        $isSchool = in_array($etabType, ['primaire', 'college', 'lycee'], true);
        $isHigherEd = in_array($etabType, ['universite', 'centre_formation'], true);
        $isPro = ($etabCat === 'professionnel') || in_array($etabType, ['entreprise', 'administration', 'association', 'ong', 'etablissement_prive'], true);

        if ($isSchool) {
            $chartBeneficiaires = [
                'labels' => ['Élèves', 'Personnel'],
                'data' => [$nbEleves, $nbPersonnels],
                'colors' => ['#0ea5e9', '#64748b']
            ];
        } elseif ($isHigherEd) {
            $chartBeneficiaires = [
                'labels' => ['Étudiants', 'Personnel'],
                'data' => [$nbEtudiants, $nbPersonnels],
                'colors' => ['#6366f1', '#64748b']
            ];
        } elseif ($isPro) {
            $chartBeneficiaires = [
                'labels' => ['Personnel'],
                'data' => [$nbPersonnels],
                'colors' => ['#64748b']
            ];
        } else {
            $chartBeneficiaires = [
                'labels' => ['Élèves', 'Étudiants', 'Personnel'],
                'data' => [$nbEleves, $nbEtudiants, $nbPersonnels],
                'colors' => ['#0ea5e9', '#6366f1', '#64748b']
            ];
        }

        // Statuts des cartes
        $stmt = $db->prepare("
            SELECT statut, COUNT(*) as total
            FROM cartes
            WHERE deleted_at IS NULL $etabFilter
            GROUP BY statut
        ");
        $stmt->execute($params);
        $statutsRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $chartStatuts = [
            'labels' => ['Brouillon', 'Validée', 'Imprimée', 'Active', 'Expirée', 'Désactivée'],
            'data' => [
                (int)($statutsRows['brouillon'] ?? 0),
                (int)($statutsRows['validee'] ?? 0),
                (int)($statutsRows['imprimee'] ?? 0),
                (int)($statutsRows['active'] ?? 0),
                (int)($statutsRows['expiree'] ?? 0),
                (int)($statutsRows['desactivee'] ?? 0),
            ]
        ];

        // Évolution mensuelle sur 12 mois
        $moisLabels = [];
        $moisData = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = date('Y-m', strtotime("-$i months"));
            $moisLabels[] = date('M Y', strtotime("-$i months"));
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM cartes
                WHERE deleted_at IS NULL AND DATE_FORMAT(created_at, '%Y-%m') = :m $etabFilter
            ");
            $p = array_merge($params, [':m' => $mois]);
            $stmt->execute($p);
            $moisData[] = (int)$stmt->fetchColumn();
        }

        // Top 5 établissements
        $topEtabWhere = "WHERE e.deleted_at IS NULL";
        if (!Auth::hasRole('admin')) {
            $accessibleEtabIds = Auth::getAccessibleEtablissementIds();
            if (!empty($accessibleEtabIds)) {
                $placeholders = implode(',', array_map('intval', $accessibleEtabIds));
                $topEtabWhere .= " AND e.id IN ($placeholders)";
            } else {
                $topEtabWhere .= " AND 1=0";
            }
        }
        $stmt = $db->query("
            SELECT e.nom, COUNT(c.id) AS total_cartes
            FROM etablissements e
            LEFT JOIN cartes c ON e.id = c.etablissement_id AND c.deleted_at IS NULL
            $topEtabWhere
            GROUP BY e.id
            ORDER BY total_cartes DESC
            LIMIT 5
        ");
        $topEtabs = $stmt->fetchAll();

        $this->render('dashboard.index', [
            'kpis' => [
                'etablissements' => $nbEtablissements,
                'eleves' => $nbEleves,
                'etudiants' => $nbEtudiants,
                'personnels' => $nbPersonnels,
                'cartes' => $nbCartes,
                'cartes_actives' => $nbCartesActives,
                'cartes_expirees' => $nbCartesExpirees,
            ],
            'cartesBientotExpirees' => $cartesBientotExpirees,
            'sansPhoto' => $sansPhoto,
            'recentLogs' => $recentLogs,
            'chartBeneficiaires' => $chartBeneficiaires,
            'chartStatuts' => $chartStatuts,
            'chartMois' => ['labels' => $moisLabels, 'data' => $moisData],
            'topEtabs' => $topEtabs,
            'latestBackup' => (new \App\Models\Sauvegarde())->latest(),
        ]);
    }

    public function switchEtablissement(): void
    {
        $etabId = (int)$this->request->post('etablissement_id', 0);
        if ($etabId === 0) {
            // Vue globale (admin seulement)
            if (Auth::hasRole('admin')) {
                Session::remove('_active_etab_id');
                Session::setFlash('info', 'Affichage basculé en vue globale multi-établissements.');
            }
        } else {
            if (Auth::setActiveEtablissementId($etabId)) {
                $etab = Auth::getActiveEtablissement();
                Session::setFlash('info', 'Établissement actif : ' . htmlspecialchars($etab['nom'] ?? ''));
            } else {
                Session::setFlash('danger', 'Accès non autorisé à cet établissement.');
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        $this->redirect($referer);
    }
}
