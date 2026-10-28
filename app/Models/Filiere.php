<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Filiere extends Model
{
    protected string $table = 'filieres';

    /**
     * Récupère les filières avec nom de département, nom d'établissement et compteurs
     */
    public function getByEtablissement(?int $etablissementId = null, ?int $departementId = null): array
    {
        $sql = "
            SELECT f.*, 
                   d.nom AS departement_nom, 
                   e.nom AS etablissement_nom,
                   (SELECT COUNT(*) FROM classes c WHERE c.filiere_id = f.id AND c.deleted_at IS NULL) AS nb_classes,
                   (SELECT COUNT(*) FROM etudiants et WHERE et.filiere_id = f.id AND et.deleted_at IS NULL) AS nb_etudiants
            FROM filieres f
            LEFT JOIN departements d ON f.departement_id = d.id
            JOIN etablissements e ON f.etablissement_id = e.id
            WHERE f.deleted_at IS NULL
        ";
        $params = [];

        if ($etablissementId !== null && $etablissementId > 0) {
            $sql .= " AND f.etablissement_id = :eid";
            $params[':eid'] = $etablissementId;
        }

        if ($departementId !== null && $departementId > 0) {
            $sql .= " AND f.departement_id = :did";
            $params[':did'] = $departementId;
        }

        $sql .= " ORDER BY f.libelle ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }

    /**
     * Vérifie si des éléments dépendent de cette filière
     */
    public function getDependenciesCount(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM classes WHERE filiere_id = :id1 AND deleted_at IS NULL) AS nb_classes,
                (SELECT COUNT(*) FROM etudiants WHERE filiere_id = :id2 AND deleted_at IS NULL) AS nb_etudiants
        ");
        $stmt->execute([':id1' => $id, ':id2' => $id]);
        $res = $stmt->fetch();
        return [
            'classes' => (int)($res['nb_classes'] ?? 0),
            'etudiants' => (int)($res['nb_etudiants'] ?? 0),
        ];
    }
}
