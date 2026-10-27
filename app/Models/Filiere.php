<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Filiere extends Model
{
    protected string $table = 'filieres';

    public function getByEtablissement(int $etablissementId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, d.nom AS departement_nom
            FROM filieres f
            LEFT JOIN departements d ON f.departement_id = d.id
            WHERE f.etablissement_id = :eid AND f.deleted_at IS NULL
            ORDER BY f.libelle ASC
        ");
        $stmt->execute([':eid' => $etablissementId]);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }
}
