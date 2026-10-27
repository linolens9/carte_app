<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Eleve extends Model
{
    protected string $table = 'eleves';

    public function getAllFiltered(array $filters = [], string $search = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT el.*,
                   c.libelle AS classe_libelle, c.niveau AS classe_niveau,
                   e.nom AS etab_nom, e.code AS etab_code,
                   (SELECT c2.statut FROM cartes c2 WHERE c2.beneficiaire_id = el.id AND c2.beneficiaire_type = 'eleve' AND c2.deleted_at IS NULL ORDER BY c2.id DESC LIMIT 1) AS statut_carte,
                   (SELECT c2.id FROM cartes c2 WHERE c2.beneficiaire_id = el.id AND c2.beneficiaire_type = 'eleve' AND c2.deleted_at IS NULL ORDER BY c2.id DESC LIMIT 1) AS derniere_carte_id
            FROM eleves el
            JOIN classes c ON el.classe_id = c.id
            JOIN etablissements e ON el.etablissement_id = e.id
            WHERE el.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND el.etablissement_id = :etab_id";
            $params[':etab_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND el.classe_id = :classe_id";
            $params[':classe_id'] = (int)$filters['classe_id'];
        }

        if (!empty($filters['niveau'])) {
            $sql .= " AND el.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }

        if (!empty($filters['annee_scolaire'])) {
            $sql .= " AND el.annee_scolaire = :annee";
            $params[':annee'] = $filters['annee_scolaire'];
        }

        if (!empty($filters['sexe'])) {
            $sql .= " AND el.sexe = :sexe";
            $params[':sexe'] = $filters['sexe'];
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND el.photo IS NOT NULL AND el.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (el.photo IS NULL OR el.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (el.nom LIKE :s_nom OR el.prenom LIKE :s_prenom OR el.matricule LIKE :s_mat)";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_mat'] = $searchTerm;
        }

        $sql .= " ORDER BY el.nom ASC, el.prenom ASC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countFiltered(array $filters = [], string $search = '') : int
    {
        $sql = "
            SELECT COUNT(*)
            FROM eleves el
            JOIN classes c ON el.classe_id = c.id
            WHERE el.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filters['etablissement_id'])) {
            $sql .= " AND el.etablissement_id = :etab_id";
            $params[':etab_id'] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND el.classe_id = :classe_id";
            $params[':classe_id'] = (int)$filters['classe_id'];
        }

        if (!empty($filters['niveau'])) {
            $sql .= " AND el.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }

        if (!empty($filters['annee_scolaire'])) {
            $sql .= " AND el.annee_scolaire = :annee";
            $params[':annee'] = $filters['annee_scolaire'];
        }

        if (!empty($filters['sexe'])) {
            $sql .= " AND el.sexe = :sexe";
            $params[':sexe'] = $filters['sexe'];
        }

        if (!empty($filters['statut_photo'])) {
            if ($filters['statut_photo'] === 'avec') {
                $sql .= " AND el.photo IS NOT NULL AND el.photo != ''";
            } elseif ($filters['statut_photo'] === 'sans') {
                $sql .= " AND (el.photo IS NULL OR el.photo = '')";
            }
        }

        if ($search !== '') {
            $sql .= " AND (el.nom LIKE :s_nom OR el.prenom LIKE :s_prenom OR el.matricule LIKE :s_mat)";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_mat'] = $searchTerm;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getWithDetails(int $id): ?array
    {
        $sql = "
            SELECT el.*,
                   c.libelle AS classe_libelle, c.niveau AS classe_niveau,
                   f.libelle AS filiere_libelle,
                   e.nom AS etab_nom, e.nom AS etablissement_nom, e.code AS etab_code, e.code AS etablissement_code,
                   e.logo AS etab_logo, e.logo AS etablissement_logo, e.couleur_principale AS etab_couleur,
                   u.nom AS creator_nom, u.prenom AS creator_prenom
            FROM eleves el
            JOIN classes c ON el.classe_id = c.id
            LEFT JOIN filieres f ON c.filiere_id = f.id
            JOIN etablissements e ON el.etablissement_id = e.id
            LEFT JOIN users u ON el.created_by = u.id
            WHERE el.id = :id AND el.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generateMatricule(int $etablissementId): string
    {
        $stmt = $this->db->prepare("SELECT code FROM etablissements WHERE id = :id");
        $stmt->execute([':id' => $etablissementId]);
        $code = (string)$stmt->fetchColumn();
        if ($code === '') {
            $code = 'ELV';
        }

        $year = date('Y');

        // Récupérer le plus grand numéro de séquence pour cette année
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM eleves
            WHERE etablissement_id = :eid AND matricule LIKE :prefix
        ");
        $countStmt->execute([
            ':eid' => $etablissementId,
            ':prefix' => $code . '-' . $year . '-%'
        ]);
        $nextSeq = (int)$countStmt->fetchColumn() + 1;

        return sprintf('%s-%s-%05d', $code, $year, $nextSeq);
    }

    public function checkDuplicate(int $etablissementId, string $nom, string $prenom, string $dateNaissance, ?int $ignoreId = null): ?array
    {
        $sql = "
            SELECT id, matricule, nom, prenom
            FROM eleves
            WHERE etablissement_id = :eid
              AND UPPER(nom) = UPPER(:nom)
              AND UPPER(prenom) = UPPER(:prenom)
              AND date_naissance = :dnaiss
              AND deleted_at IS NULL
        ";
        $params = [
            ':eid' => $etablissementId,
            ':nom' => trim($nom),
            ':prenom' => trim($prenom),
            ':dnaiss' => $dateNaissance,
        ];

        if ($ignoreId !== null) {
            $sql .= " AND id != :ignore_id";
            $params[':ignore_id'] = $ignoreId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function promoteBatch(array $eleveIds, int $targetClasseId, string $targetNiveau, string $targetAnnee): int
    {
        if (empty($eleveIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($eleveIds), '?'));
        $sql = "
            UPDATE eleves
            SET classe_id = ?, niveau = ?, annee_scolaire = ?
            WHERE id IN ($placeholders) AND deleted_at IS NULL
        ";

        $params = array_merge([$targetClasseId, $targetNiveau, $targetAnnee], $eleveIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
