<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Etablissement extends Model
{
    protected string $table = 'etablissements';

    public function getAllWithStats(string $search = '', string $categorie = '', string $type = '', string $statut = ''): array
    {
        $sql = "
            SELECT e.*,
                (CASE WHEN e.statut = 'archive' OR e.deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS is_archived,
                (SELECT COUNT(*) FROM eleves el WHERE el.etablissement_id = e.id AND el.deleted_at IS NULL AND el.statut = 'actif') AS nb_eleves,
                (SELECT COUNT(*) FROM etudiants et WHERE et.etablissement_id = e.id AND et.deleted_at IS NULL AND et.statut = 'actif') AS nb_etudiants,
                (SELECT COUNT(*) FROM personnels p WHERE p.etablissement_id = e.id AND p.deleted_at IS NULL AND p.statut = 'en_poste') AS nb_personnels,
                (SELECT COUNT(*) FROM cartes c WHERE c.etablissement_id = e.id AND c.deleted_at IS NULL) AS nb_cartes,
                (SELECT COUNT(*) FROM cartes c WHERE c.etablissement_id = e.id AND c.deleted_at IS NULL AND c.statut = 'active') AS nb_cartes_actives
            FROM etablissements e
            WHERE 1=1
        ";
        $params = [];

        if ($statut === 'archive') {
            $sql .= " AND (e.statut = 'archive' OR e.deleted_at IS NOT NULL)";
        } elseif ($statut === 'actif') {
            $sql .= " AND e.deleted_at IS NULL AND e.statut = 'actif'";
        } elseif ($statut === 'suspendu') {
            $sql .= " AND e.deleted_at IS NULL AND e.statut = 'suspendu'";
        } elseif ($statut === 'tous') {
            // Tous les établissements, y compris archivés
        } else {
            // Par défaut: uniquement les établissements en service (non archivés)
            $sql .= " AND e.deleted_at IS NULL AND e.statut != 'archive'";
        }

        if ($search !== '') {
            $sql .= " AND (e.nom LIKE :s_nom OR e.code LIKE :s_code OR e.ville LIKE :s_ville)";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_code'] = $searchTerm;
            $params[':s_ville'] = $searchTerm;
        }

        if ($categorie !== '') {
            $sql .= " AND e.categorie = :cat";
            $params[':cat'] = $categorie;
        }

        if ($type !== '') {
            $sql .= " AND e.type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY e.nom ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCounts(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(CASE WHEN deleted_at IS NULL AND statut = 'actif' THEN 1 END) AS actifs,
                COUNT(CASE WHEN deleted_at IS NULL AND statut = 'suspendu' THEN 1 END) AS suspendus,
                COUNT(CASE WHEN deleted_at IS NOT NULL OR statut = 'archive' THEN 1 END) AS archives,
                COUNT(CASE WHEN deleted_at IS NULL AND statut != 'archive' THEN 1 END) AS total_en_service,
                COUNT(*) AS total
            FROM etablissements
        ");
        return $stmt->fetch() ?: [
            'actifs' => 0,
            'suspendus' => 0,
            'archives' => 0,
            'total_en_service' => 0,
            'total' => 0
        ];
    }

    public function findByIdWithArchived(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM etablissements WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function archive(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE etablissements SET statut = 'archive', deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE etablissements SET statut = 'actif', deleted_at = NULL WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getStats(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM eleves WHERE etablissement_id = :id1 AND deleted_at IS NULL) AS total_eleves,
                (SELECT COUNT(*) FROM etudiants WHERE etablissement_id = :id2 AND deleted_at IS NULL) AS total_etudiants,
                (SELECT COUNT(*) FROM personnels WHERE etablissement_id = :id3 AND deleted_at IS NULL) AS total_personnels,
                (SELECT COUNT(*) FROM cartes WHERE etablissement_id = :id4 AND deleted_at IS NULL) AS total_cartes,
                (SELECT COUNT(*) FROM cartes WHERE etablissement_id = :id5 AND deleted_at IS NULL AND statut = 'active') AS cartes_actives,
                (SELECT COUNT(*) FROM cartes WHERE etablissement_id = :id6 AND deleted_at IS NULL AND statut = 'expiree') AS cartes_expirees,
                (SELECT COUNT(*) FROM classes WHERE etablissement_id = :id7 AND deleted_at IS NULL) AS total_classes,
                (SELECT COUNT(*) FROM departements WHERE etablissement_id = :id8 AND deleted_at IS NULL) AS total_departements
        ");
        $stmt->execute([
            ':id1' => $id, ':id2' => $id, ':id3' => $id, ':id4' => $id,
            ':id5' => $id, ':id6' => $id, ':id7' => $id, ':id8' => $id
        ]);
        return $stmt->fetch() ?: [];
    }

    public function hasActiveCards(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM cartes
            WHERE etablissement_id = :id AND deleted_at IS NULL AND statut IN ('active', 'validee', 'imprimee')
        ");
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function all(bool $includeArchived = false): array
    {
        if ($includeArchived) {
            $stmt = $this->db->query("SELECT * FROM etablissements ORDER BY nom ASC");
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT * FROM etablissements WHERE deleted_at IS NULL AND statut != 'archive' ORDER BY nom ASC");
        return $stmt->fetchAll();
    }
}

