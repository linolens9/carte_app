<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Parametre
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Récupère la valeur d'un paramètre
     */
    public function get(string $cle, $default = null, ?int $etablissementId = null)
    {
        $sql = "SELECT valeur, type FROM parametres WHERE cle = ? AND " .
               ($etablissementId !== null ? "etablissement_id = ?" : "etablissement_id IS NULL");
        $stmt = $this->pdo->prepare($sql);
        $params = [$cle];
        if ($etablissementId !== null) {
            $params[] = $etablissementId;
        }
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Fallback global si recherché pour un établissement
            if ($etablissementId !== null) {
                return $this->get($cle, $default, null);
            }
            return $default;
        }

        return $this->castValue($row['valeur'], $row['type']);
    }

    /**
     * Enregistre ou met à jour un paramètre
     */
    public function set(string $cle, $valeur, string $type = 'texte', ?int $etablissementId = null, ?int $userId = null): bool
    {
        $valeurStr = is_array($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE) : (string)$valeur;

        $checkSql = "SELECT id FROM parametres WHERE cle = ? AND " .
                    ($etablissementId !== null ? "etablissement_id = ?" : "etablissement_id IS NULL");
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkParams = [$cle];
        if ($etablissementId !== null) {
            $checkParams[] = $etablissementId;
        }
        $checkStmt->execute($checkParams);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $currTypeStmt = $this->pdo->prepare("SELECT type FROM parametres WHERE id = ?");
            $currTypeStmt->execute([$existingId]);
            $existingType = $currTypeStmt->fetchColumn();
            if ($existingType && $type === 'texte') {
                $type = $existingType;
            }
            $updateSql = "UPDATE parametres SET valeur = ?, type = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
            return $this->pdo->prepare($updateSql)->execute([$valeurStr, $type, $userId, $existingId]);
        } else {
            $insertSql = "INSERT INTO parametres (etablissement_id, cle, valeur, type, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, NOW())";
            return $this->pdo->prepare($insertSql)->execute([$etablissementId, $cle, $valeurStr, $type, $userId]);
        }
    }

    /**
     * Récupère tous les paramètres organisés par catégorie (ex: 'app', 'carte', 'securite', 'photo', 'sauvegarde')
     */
    public function all(?int $etablissementId = null): array
    {
        $sql = "SELECT * FROM parametres WHERE " .
               ($etablissementId !== null ? "etablissement_id = ?" : "etablissement_id IS NULL") .
               " ORDER BY cle ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($etablissementId !== null ? [$etablissementId] : []);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[$r['cle']] = [
                'valeur' => $this->castValue($r['valeur'], $r['type']),
                'type' => $r['type'],
                'updated_at' => $r['updated_at'],
            ];
        }

        return $result;
    }

    private function castValue(?string $val, string $type)
    {
        if ($val === null) {
            return null;
        }
        return match ($type) {
            'nombre' => is_numeric($val) ? (str_contains($val, '.') ? (float)$val : (int)$val) : 0,
            'booleen' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($val, true) ?? [],
            default => $val,
        };
    }
}
