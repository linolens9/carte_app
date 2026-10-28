<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class Departement extends Model
{
    protected string $table = 'departements';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTypeColumnExists();
    }

    private function ensureTypeColumnExists(): void
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM departements LIKE 'type'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE departements ADD COLUMN type ENUM('departement', 'service') NOT NULL DEFAULT 'departement' AFTER nom");
            }
        } catch (Throwable $e) {
            // Silencieux si déjà existant
        }
    }

    /**
     * Récupère la liste des départements et services avec stats et rattachements
     */
    public function getByEtablissement(?int $etablissementId = null, ?string $type = null): array
    {
        $sql = "
            SELECT d.*, 
                   p.nom AS parent_nom, 
                   e.nom AS etablissement_nom,
                   (SELECT COUNT(*) FROM personnels pers WHERE pers.departement_id = d.id AND pers.deleted_at IS NULL) AS nb_personnels,
                   (SELECT COUNT(*) FROM filieres f WHERE f.departement_id = d.id AND f.deleted_at IS NULL) AS nb_filieres,
                   (SELECT COUNT(*) FROM departements sub WHERE sub.parent_id = d.id AND sub.deleted_at IS NULL) AS nb_sous_structures
            FROM departements d
            LEFT JOIN departements p ON d.parent_id = p.id
            JOIN etablissements e ON d.etablissement_id = e.id
            WHERE d.deleted_at IS NULL
        ";
        $params = [];

        if ($etablissementId !== null && $etablissementId > 0) {
            $sql .= " AND d.etablissement_id = :eid";
            $params[':eid'] = $etablissementId;
        }

        if ($type !== null && in_array($type, ['departement', 'service'], true)) {
            $sql .= " AND d.type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY d.type ASC, d.nom ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }

    /**
     * Vérifie si des éléments dépendent de ce département/service avant suppression
     */
    public function getDependenciesCount(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM personnels WHERE departement_id = :id1 AND deleted_at IS NULL) AS nb_personnels,
                (SELECT COUNT(*) FROM filieres WHERE departement_id = :id2 AND deleted_at IS NULL) AS nb_filieres,
                (SELECT COUNT(*) FROM departements WHERE parent_id = :id3 AND deleted_at IS NULL) AS nb_enfants
        ");
        $stmt->execute([':id1' => $id, ':id2' => $id, ':id3' => $id]);
        $res = $stmt->fetch();
        return [
            'personnels' => (int)($res['nb_personnels'] ?? 0),
            'filieres' => (int)($res['nb_filieres'] ?? 0),
            'enfants' => (int)($res['nb_enfants'] ?? 0),
        ];
    }
}
