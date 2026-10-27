<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Modèle de base pour l'accès aux données PDO
 */
abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $useSoftDelete = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        if ($this->useSoftDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(array $conditions = [], string $orderBy = 'id DESC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];

        if ($this->useSoftDelete) {
            $sql .= " AND deleted_at IS NULL";
        }

        foreach ($conditions as $col => $val) {
            $paramKey = ':param_' . str_replace('.', '_', $col);
            $sql .= " AND `{$col}` = {$paramKey}";
            $params[$paramKey] = $val;
        }

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE 1=1";
        $params = [];

        if ($this->useSoftDelete) {
            $sql .= " AND deleted_at IS NULL";
        }

        foreach ($conditions as $col => $val) {
            $paramKey = ':param_' . str_replace('.', '_', $col);
            $sql .= " AND `{$col}` = {$paramKey}";
            $params[$paramKey] = $val;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $fields = array_keys($data);
        $columns = implode('`, `', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);

        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }

        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [':id' => $id];

        foreach ($data as $col => $val) {
            $setClauses[] = "`{$col}` = :set_{$col}";
            $params[":set_{$col}"] = $val;
        }

        $setSql = implode(', ', $setClauses);
        $sql = "UPDATE `{$this->table}` SET {$setSql} WHERE `{$this->primaryKey}` = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        if ($this->useSoftDelete) {
            $sql = "UPDATE `{$this->table}` SET deleted_at = NOW() WHERE `{$this->primaryKey}` = :id";
        } else {
            $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
