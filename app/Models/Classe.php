<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Classe extends Model
{
    protected string $table = 'classes';

    /**
     * Récupère les classes avec filière, établissement et effectifs
     */
    public function getByEtablissement(?int $etablissementId = null, ?int $filiereId = null, ?string $niveau = null, ?string $annee = null): array
    {
        $sql = "
            SELECT c.*, 
                   f.libelle AS filiere_libelle,
                   f.code AS filiere_code,
                   e.nom AS etablissement_nom,
                   (SELECT COUNT(*) FROM eleves el WHERE el.classe_id = c.id AND el.deleted_at IS NULL) AS nb_eleves
            FROM classes c
            LEFT JOIN filieres f ON c.filiere_id = f.id
            JOIN etablissements e ON c.etablissement_id = e.id
            WHERE c.deleted_at IS NULL
        ";
        $params = [];

        if ($etablissementId !== null && $etablissementId > 0) {
            $sql .= " AND c.etablissement_id = :eid";
            $params[':eid'] = $etablissementId;
        }

        if ($filiereId !== null && $filiereId > 0) {
            $sql .= " AND c.filiere_id = :fid";
            $params[':fid'] = $filiereId;
        }

        if ($niveau !== null && $niveau !== '') {
            $sql .= " AND c.niveau = :niveau";
            $params[':niveau'] = $niveau;
        }

        if ($annee !== null && $annee !== '') {
            $sql .= " AND c.annee_scolaire = :annee";
            $params[':annee'] = $annee;
        }

        $sql .= " ORDER BY c.annee_scolaire DESC, c.niveau ASC, c.libelle ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allByEtablissement(int $etablissementId): array
    {
        return $this->getByEtablissement($etablissementId);
    }

    /**
     * Vérifie si des élèves sont rattachés à cette classe
     */
    public function getDependenciesCount(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS nb_eleves
            FROM eleves
            WHERE classe_id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        return [
            'eleves' => (int)$stmt->fetchColumn()
        ];
    }
}
