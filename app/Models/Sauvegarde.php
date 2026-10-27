<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Sauvegarde
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
            FROM sauvegardes s
            LEFT JOIN users u ON s.created_by = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function all(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
            FROM sauvegardes s
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sauvegardes (nom_fichier, chemin, type, taille_octets, tables_incluses, statut, duree_secondes, message, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nom_fichier'],
            $data['chemin'],
            $data['type'] ?? 'manuelle',
            $data['taille_octets'] ?? 0,
            $data['tables_incluses'] ?? null,
            $data['statut'] ?? 'reussie',
            $data['duree_secondes'] ?? 0,
            $data['message'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        foreach ($data as $key => $val) {
            $fields[] = "{$key} = ?";
            $values[] = $val;
        }
        $values[] = $id;

        $sql = "UPDATE sauvegardes SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->pdo->prepare($sql)->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sauvegardes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function latest(): ?array
    {
        $stmt = $this->pdo->query("
            SELECT s.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
            FROM sauvegardes s
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function totalSize(): int
    {
        $res = $this->pdo->query("SELECT SUM(taille_octets) FROM sauvegardes WHERE statut = 'reussie'")->fetchColumn();
        return (int)($res ?: 0);
    }

    public function count(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM sauvegardes")->fetchColumn();
    }
}
