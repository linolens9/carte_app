<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Etudiant extends Model
{
    protected string $table = 'etudiants';

    /**
     * Recherche paginée avec filtres multicritères
     */
    public function getAllFiltered(array $filters = [], string $search = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT e.*, 
                   et.nom AS etablissement_nom, et.code AS etablissement_code,
                   f.libelle AS filiere_libelle, f.code AS filiere_code,
                   d.nom AS departement_nom, d.code AS departement_code,
                   c.libelle AS classe_libelle
            FROM {$this->table} e
            JOIN etablissements et ON e.etablissement_id = et.id
            JOIN filieres f ON e.filiere_id = f.id
            LEFT JOIN departements d ON f.departement_id = d.id
            LEFT JOIN classes c ON e.classe_id = c.id
            WHERE e.deleted_at IS NULL
        ";

        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND e.etablissement_id = :etablissement_id";
            $params[':etablissement_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['filiere_id'])) {
            $sql .= " AND e.filiere_id = :filiere_id";
            $params[':filiere_id'] = (int)$filters['filiere_id'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND e.classe_id = :classe_id";
            $params[':classe_id'] = (int)$filters['classe_id'];
        }

        if (!empty($filters['niveau'])) {
            $sql .= " AND e.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }

        if (!empty($filters['annee_scolaire'])) {
            $sql .= " AND e.annee_scolaire = :annee_scolaire";
            $params[':annee_scolaire'] = $filters['annee_scolaire'];
        }

        if (!empty($filters['sexe'])) {
            $sql .= " AND e.sexe = :sexe";
            $params[':sexe'] = $filters['sexe'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND e.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND e.photo IS NOT NULL AND e.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (e.photo IS NULL OR e.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (
                e.nom LIKE :s_nom OR 
                e.prenom LIKE :s_prenom OR 
                e.matricule LIKE :s_mat OR
                e.email LIKE :s_email OR
                e.telephone LIKE :s_tel
            )";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_mat'] = $searchTerm;
            $params[':s_email'] = $searchTerm;
            $params[':s_tel'] = $searchTerm;
        }

        $sql .= " ORDER BY e.nom ASC, e.prenom ASC LIMIT :limit OFFSET :offset";

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
            FROM {$this->table} e
            JOIN etablissements et ON e.etablissement_id = et.id
            JOIN filieres f ON e.filiere_id = f.id
            LEFT JOIN classes c ON e.classe_id = c.id
            WHERE e.deleted_at IS NULL
        ";

        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND e.etablissement_id = :etablissement_id";
            $params[':etablissement_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['filiere_id'])) {
            $sql .= " AND e.filiere_id = :filiere_id";
            $params[':filiere_id'] = (int)$filters['filiere_id'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND e.classe_id = :classe_id";
            $params[':classe_id'] = (int)$filters['classe_id'];
        }

        if (!empty($filters['niveau'])) {
            $sql .= " AND e.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }

        if (!empty($filters['annee_scolaire'])) {
            $sql .= " AND e.annee_scolaire = :annee_scolaire";
            $params[':annee_scolaire'] = $filters['annee_scolaire'];
        }

        if (!empty($filters['sexe'])) {
            $sql .= " AND e.sexe = :sexe";
            $params[':sexe'] = $filters['sexe'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND e.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND e.photo IS NOT NULL AND e.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (e.photo IS NULL OR e.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (
                e.nom LIKE :s_nom OR 
                e.prenom LIKE :s_prenom OR 
                e.matricule LIKE :s_mat OR
                e.email LIKE :s_email OR
                e.telephone LIKE :s_tel
            )";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_mat'] = $searchTerm;
            $params[':s_email'] = $searchTerm;
            $params[':s_tel'] = $searchTerm;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Fiche détaillée d'un étudiant avec toutes ses jointures
     */
    public function getWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   et.nom AS etablissement_nom, et.code AS etablissement_code,
                   et.logo AS etablissement_logo, et.ville AS etablissement_ville,
                   f.libelle AS filiere_libelle, f.code AS filiere_code,
                   d.nom AS departement_nom, d.code AS departement_code,
                   c.libelle AS classe_libelle, c.niveau AS classe_niveau
            FROM {$this->table} e
            JOIN etablissements et ON e.etablissement_id = et.id
            JOIN filieres f ON e.filiere_id = f.id
            LEFT JOIN departements d ON f.departement_id = d.id
            LEFT JOIN classes c ON e.classe_id = c.id
            WHERE e.id = :id AND e.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Vérification des doublons sur (nom + prénom + date_naissance) dans le même établissement
     */
    public function checkDuplicate(int $etablissementId, string $nom, string $prenom, string $dateNaissance, ?int $excludeId = null): ?array
    {
        $sql = "
            SELECT id, matricule, nom, prenom 
            FROM {$this->table} 
            WHERE etablissement_id = :eid 
              AND LOWER(TRIM(nom)) = LOWER(TRIM(:nom)) 
              AND LOWER(TRIM(prenom)) = LOWER(TRIM(:prenom)) 
              AND date_naissance = :date_naiss
              AND deleted_at IS NULL
        ";
        $params = [
            ':eid' => $etablissementId,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':date_naiss' => $dateNaissance,
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
     * Génération automatique d'un numéro matricule étudiant : [CODE_ETAB]-[ANNEE]-[00001]
     */
    public function generateMatricule(int $etablissementId): string
    {
        $stmt = $this->db->prepare("SELECT code FROM etablissements WHERE id = :id");
        $stmt->execute([':id' => $etablissementId]);
        $code = (string)$stmt->fetchColumn();
        if ($code === '') {
            $code = 'ETU';
        }

        $year = date('Y');
        $prefix = strtoupper($code) . '-' . $year . '-';

        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) 
            FROM {$this->table} 
            WHERE etablissement_id = :eid AND matricule LIKE :prefix
        ");
        $stmtCount->execute([
            ':eid' => $etablissementId,
            ':prefix' => $prefix . '%'
        ]);
        $count = (int)$stmtCount->fetchColumn();

        $nextNum = str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
        return $prefix . $nextNum;
    }

    /**
     * Promotion ou passage de cycle par lot
     */
    public function promoteBatch(array $etudiantIds, int $targetFiliereId, ?int $targetClasseId, string $targetNiveau, string $targetAnnee): int
    {
        if (empty($etudiantIds)) {
            return 0;
        }

        $inClause = implode(',', array_map('intval', $etudiantIds));
        $sql = "
            UPDATE {$this->table} 
            SET filiere_id = :filiere_id,
                classe_id = :classe_id,
                niveau = :niveau,
                annee_scolaire = :annee,
                updated_at = NOW()
            WHERE id IN ($inClause) AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':filiere_id' => $targetFiliereId,
            ':classe_id' => $targetClasseId ?: null,
            ':niveau' => $targetNiveau,
            ':annee' => $targetAnnee,
        ]);

        return $stmt->rowCount();
    }
}
