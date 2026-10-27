<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Classe extends Model
{
    protected string $table = 'classes';

    public function getByEtablissement(int $etablissementId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, f.libelle AS filiere_libelle,
                   (SELECT COUNT(*) FROM eleves e WHERE e.classe_id = c.id AND e.deleted_at IS NULL) AS nb_eleves
            FROM classes c
            LEFT JOIN filieres f ON c.filiere_id = f.id
            WHERE c.etablissement_id = :eid AND c.deleted_at IS NULL
            ORDER BY c.niveau ASC, c.libelle ASC
        ");
        $stmt->execute([':eid' => $etablissementId]);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }
}
