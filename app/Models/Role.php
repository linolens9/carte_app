<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Role extends Model
{
    protected string $table = 'roles';
    protected bool $useSoftDelete = false;

    public function getPermissions(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*
            FROM permissions p
            JOIN role_permission rp ON p.id = rp.permission_id
            WHERE rp.role_id = :rid
            ORDER BY p.module ASC, p.libelle ASC
        ");
        $stmt->execute([':rid' => $roleId]);
        return $stmt->fetchAll();
    }

    public function getPermissionIds(int $roleId): array
    {
        $stmt = $this->db->prepare("SELECT permission_id FROM role_permission WHERE role_id = :rid");
        $stmt->execute([':rid' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $del = $this->db->prepare("DELETE FROM role_permission WHERE role_id = :rid");
        $del->execute([':rid' => $roleId]);

        if (!empty($permissionIds)) {
            $ins = $this->db->prepare("INSERT INTO role_permission (role_id, permission_id) VALUES (:rid, :pid)");
            foreach ($permissionIds as $pid) {
                if ((int)$pid > 0) {
                    $ins->execute([':rid' => $roleId, ':pid' => (int)$pid]);
                }
            }
        }
    }

    public function getAllWithUserCount(): array
    {
        $stmt = $this->db->query("
            SELECT r.*, COUNT(u.id) AS user_count
            FROM roles r
            LEFT JOIN users u ON r.id = u.role_id AND u.deleted_at IS NULL
            GROUP BY r.id
            ORDER BY r.id ASC
        ");
        return $stmt->fetchAll();
    }
}
