<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Personnel extends Model
{
    protected string $table = 'personnels';

    /**
     * Recherche paginée avec filtres multicritères
     */
    public function getAllFiltered(array $filters = [], string $search = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT p.*, 
                   et.nom AS etablissement_nom, et.code AS etablissement_code,
                   d.nom AS departement_nom, d.code AS departement_code
            FROM {$this->table} p
            JOIN etablissements et ON p.etablissement_id = et.id
            LEFT JOIN departements d ON p.departement_id = d.id
            WHERE p.deleted_at IS NULL
        ";

        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND p.etablissement_id = :etablissement_id";
            $params[':etablissement_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['departement_id'])) {
            $sql .= " AND p.departement_id = :departement_id";
            $params[':departement_id'] = (int)$filters['departement_id'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['fonction'])) {
            $sql .= " AND p.fonction LIKE :fonction";
            $params[':fonction'] = '%' . $filters['fonction'] . '%';
        }

        if (!empty($filters['service'])) {
            $sql .= " AND p.service LIKE :service";
            $params[':service'] = '%' . $filters['service'] . '%';
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND p.photo IS NOT NULL AND p.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (p.photo IS NULL OR p.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (
                p.nom LIKE :s_nom OR 
                p.prenom LIKE :s_prenom OR 
                p.numero_professionnel LIKE :s_num OR
                p.fonction LIKE :s_fonc OR
                p.service LIKE :s_serv OR
                p.email LIKE :s_email OR
                p.telephone LIKE :s_tel
            )";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_num'] = $searchTerm;
            $params[':s_fonc'] = $searchTerm;
            $params[':s_serv'] = $searchTerm;
            $params[':s_email'] = $searchTerm;
            $params[':s_tel'] = $searchTerm;
        }

        $sql .= " ORDER BY p.nom ASC, p.prenom ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Nombre total pour pagination
     */
    public function countFiltered(array $filters = [], string $search = ''): int
    {
        $sql = "
            SELECT COUNT(*) 
            FROM {$this->table} p
            JOIN etablissements et ON p.etablissement_id = et.id
            LEFT JOIN departements d ON p.departement_id = d.id
            WHERE p.deleted_at IS NULL
        ";

        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND p.etablissement_id = :etablissement_id";
            $params[':etablissement_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['departement_id'])) {
            $sql .= " AND p.departement_id = :departement_id";
            $params[':departement_id'] = (int)$filters['departement_id'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['fonction'])) {
            $sql .= " AND p.fonction LIKE :fonction";
            $params[':fonction'] = '%' . $filters['fonction'] . '%';
        }

        if (!empty($filters['service'])) {
            $sql .= " AND p.service LIKE :service";
            $params[':service'] = '%' . $filters['service'] . '%';
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND p.photo IS NOT NULL AND p.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (p.photo IS NULL OR p.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (
                p.nom LIKE :s_nom OR 
                p.prenom LIKE :s_prenom OR 
                p.numero_professionnel LIKE :s_num OR
                p.fonction LIKE :s_fonc OR
                p.service LIKE :s_serv OR
                p.email LIKE :s_email OR
                p.telephone LIKE :s_tel
            )";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_num'] = $searchTerm;
            $params[':s_fonc'] = $searchTerm;
            $params[':s_serv'] = $searchTerm;
            $params[':s_email'] = $searchTerm;
            $params[':s_tel'] = $searchTerm;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Fiche détaillée d'un membre du personnel avec son département et son établissement
     */
    public function getWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   et.nom AS etablissement_nom, et.code AS etablissement_code,
                   et.logo AS etablissement_logo, et.ville AS etablissement_ville,
                   et.adresse AS etablissement_adresse, et.categorie AS etablissement_categorie,
                   d.nom AS departement_nom, d.code AS departement_code
            FROM {$this->table} p
            JOIN etablissements et ON p.etablissement_id = et.id
            LEFT JOIN departements d ON p.departement_id = d.id
            WHERE p.id = :id AND p.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Contrôle doublon (nom + prénom + fonction)
     */
    public function checkDuplicate(int $etablissementId, string $nom, string $prenom, string $fonction, ?int $excludeId = null): ?array
    {
        $sql = "
            SELECT id, numero_professionnel, nom, prenom, fonction 
            FROM {$this->table} 
            WHERE etablissement_id = :eid 
              AND LOWER(TRIM(nom)) = LOWER(TRIM(:nom)) 
              AND LOWER(TRIM(prenom)) = LOWER(TRIM(:prenom)) 
              AND LOWER(TRIM(fonction)) = LOWER(TRIM(:fonc))
              AND deleted_at IS NULL
        ";
        $params = [
            ':eid' => $etablissementId,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':fonc' => $fonction,
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch();

        return $res ?: null;
    }

    /**
     * Génération automatique du numéro professionnel : [CODE_ETAB]-[ANNEE]-[00001]
     */
    public function generateNumeroProfessionnel(int $etablissementId): string
    {
        $stmt = $this->db->prepare("SELECT code FROM etablissements WHERE id = :id");
        $stmt->execute([':id' => $etablissementId]);
        $code = (string)$stmt->fetchColumn();
        if ($code === '') {
            $code = 'PRO';
        }

        $year = date('Y');
        $prefix = strtoupper($code) . '-' . $year . '-';

        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) 
            FROM {$this->table} 
            WHERE etablissement_id = :eid AND numero_professionnel LIKE :prefix
        ");
        $stmtCount->execute([
            ':eid' => $etablissementId,
            ':prefix' => $prefix . '%'
        ]);
        $count = (int)$stmtCount->fetchColumn();

        $nextNum = str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
        return $prefix . $nextNum;
    }
}
