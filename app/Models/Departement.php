<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Departement extends Model
{
    protected string $table = 'departements';

    public function getByEtablissement(int $etablissementId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, p.nom AS parent_nom
            FROM departements d
            LEFT JOIN departements p ON d.parent_id = p.id
            WHERE d.etablissement_id = :eid AND d.deleted_at IS NULL
            ORDER BY d.nom ASC
        ");
        $stmt->execute([':eid' => $etablissementId]);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }
}
