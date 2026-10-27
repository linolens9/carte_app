<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected string $table = 'users';

    public function getAllWithRoles(?int $etablissementId = null, string $search = '', ?array $allowedEtabIds = null): array
    {
        $sql = "
            SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.statut,
                   u.derniere_connexion, u.doit_changer_mdp, u.created_at,
                   r.id AS role_id, r.code AS role_code, r.libelle AS role_libelle,
                   GROUP_CONCAT(DISTINCT e.nom ORDER BY e.nom ASC SEPARATOR '||') AS etablissements_noms,
                   GROUP_CONCAT(DISTINCT e.code ORDER BY e.code ASC SEPARATOR '||') AS etablissements_codes,
                   GROUP_CONCAT(DISTINCT e.id ORDER BY e.id ASC SEPARATOR ',') AS etablissements_ids
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_etablissement ue ON u.id = ue.user_id
            LEFT JOIN etablissements e ON ue.etablissement_id = e.id AND e.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                u.nom LIKE :s_nom 
                OR u.prenom LIKE :s_prenom 
                OR u.email LIKE :s_email
                OR CONCAT(u.nom, ' ', u.prenom) LIKE :s_full1
                OR CONCAT(u.prenom, ' ', u.nom) LIKE :s_full2
            )";
            $searchTerm = '%' . $search . '%';
            $params[':s_nom'] = $searchTerm;
            $params[':s_prenom'] = $searchTerm;
            $params[':s_email'] = $searchTerm;
            $params[':s_full1'] = $searchTerm;
            $params[':s_full2'] = $searchTerm;
        }

        if ($etablissementId !== null) {
            $sql .= " AND (
                r.code = 'admin' OR
                EXISTS (SELECT 1 FROM user_etablissement ue2 WHERE ue2.user_id = u.id AND ue2.etablissement_id = :etab_id)
            )";
            $params[':etab_id'] = $etablissementId;
        } elseif ($allowedEtabIds !== null) {
            if (empty($allowedEtabIds)) {
                $sql .= " AND r.code = 'admin'";
            } else {
                $placeholders = [];
                foreach (array_values($allowedEtabIds) as $idx => $id) {
                    $key = ":allowed_etab_" . $idx;
                    $placeholders[] = $key;
                    $params[$key] = (int)$id;
                }
                $sql .= " AND (
                    r.code = 'admin' OR
                    EXISTS (SELECT 1 FROM user_etablissement ue2 WHERE ue2.user_id = u.id AND ue2.etablissement_id IN (" . implode(',', $placeholders) . "))
                )";
            }
        }

        $sql .= " GROUP BY u.id, r.id, r.code, r.libelle ORDER BY u.nom ASC, u.prenom ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getEtablissements(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*
            FROM etablissements e
            JOIN user_etablissement ue ON e.id = ue.etablissement_id
            WHERE ue.user_id = :uid AND e.deleted_at IS NULL
            ORDER BY e.nom ASC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function syncEtablissements(int $userId, array $etablissementIds): void
    {
        // Supprimer les affectations actuelles
        $del = $this->db->prepare("DELETE FROM user_etablissement WHERE user_id = :uid");
        $del->execute([':uid' => $userId]);

        // Insérer les nouvelles
        if (!empty($etablissementIds)) {
            $ins = $this->db->prepare("INSERT INTO user_etablissement (user_id, etablissement_id) VALUES (:uid, :eid)");
            foreach ($etablissementIds as $eid) {
                if ((int)$eid > 0) {
                    $ins->execute([':uid' => $userId, ':eid' => (int)$eid]);
                }
            }
        }
    }
}
