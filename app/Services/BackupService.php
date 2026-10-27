<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Sauvegarde;
use PDO;

class BackupService
{
    private PDO $pdo;
    private string $backupDir;
    private Sauvegarde $model;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';
        $this->model = new Sauvegarde();

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Crée une nouvelle sauvegarde complète de la base de données
     */
    public function createBackup(string $type = 'manuelle', ?int $userId = null, bool $compress = true): array
    {
        $startTime = microtime(true);
        $dateStr = date('Ymd_His');
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $useGz = $compress && function_exists('gzopen');
        $extension = $useGz ? '.sql.gz' : '.sql';
        $filename = "backup_carte_app_{$dateStr}_{$type}{$extension}";
        $filePath = $this->backupDir . '/' . $filename;

        try {
            $sqlBuffer = $this->generateSqlDump($tables);

            if ($useGz) {
                $gz = gzopen($filePath, 'w9');
                if (!$gz) {
                    throw new \RuntimeException("Impossible d'ouvrir le fichier gzip pour écriture : {$filePath}");
                }
                gzwrite($gz, $sqlBuffer);
                gzclose($gz);
            } else {
                if (file_put_contents($filePath, $sqlBuffer) === false) {
                    throw new \RuntimeException("Impossible d'écrire le fichier SQL : {$filePath}");
                }
            }

            $size = (int)filesize($filePath);
            $duration = round(microtime(true) - $startTime, 2);

            $id = $this->model->create([
                'nom_fichier' => $filename,
                'chemin' => 'storage/backups/' . $filename,
                'type' => $type,
                'taille_octets' => $size,
                'tables_incluses' => implode(', ', $tables),
                'statut' => 'reussie',
                'duree_secondes' => $duration,
                'message' => "Sauvegarde générée avec succès (" . count($tables) . " tables).",
                'created_by' => $userId,
            ]);

            // Nettoyage automatique selon la rétention (conserve les 20 plus récentes)
            $this->applyRetentionPolicy(20);

            // Log
            LogService::log(
                'sauvegarde.creee',
                'sauvegardes',
                "Sauvegarde {$type} créée : {$filename} ({$size} octets en {$duration}s)",
                null,
                $id,
                $userId
            );

            return [
                'success' => true,
                'id' => $id,
                'filename' => $filename,
                'size' => $size,
                'duration' => $duration,
                'tables_count' => count($tables),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 2);
            $this->model->create([
                'nom_fichier' => $filename,
                'chemin' => 'storage/backups/' . $filename,
                'type' => $type,
                'taille_octets' => 0,
                'tables_incluses' => implode(', ', $tables),
                'statut' => 'echec',
                'duree_secondes' => $duration,
                'message' => "Erreur : " . $e->getMessage(),
                'created_by' => $userId,
            ]);

            LogService::log(
                'sauvegarde.echec',
                'sauvegardes',
                "Échec de la sauvegarde {$type} : " . $e->getMessage(),
                null,
                null,
                $userId
            );

            return [
                'success' => false,
                'id' => 0,
                'filename' => $filename,
                'size' => 0,
                'duration' => $duration,
                'tables_count' => count($tables),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Génère la chaîne SQL de dump complet
     */
    private function generateSqlDump(array $tables): string
    {
        $out = "-- ============================================================\n";
        $out .= "-- Carte App - Export Base de Données\n";
        $out .= "-- Date : " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Version PHP : " . PHP_VERSION . "\n";
        $out .= "-- Tables : " . count($tables) . "\n";
        $out .= "-- ============================================================\n\n";
        $out .= "SET NAMES utf8mb4;\n";
        $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $out .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        foreach ($tables as $table) {
            // Ne pas écraser les logs ou sauvegardes de façon destructive si besoin, mais on exporte tout
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "-- Structure de la table `{$table}`\n";
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if ($row && !empty($row[1])) {
                $out .= $row[1] . ";\n\n";
            }

            // Données
            $out .= "-- Contenu de la table `{$table}`\n";
            $countStmt = $this->pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $totalRows = (int)$countStmt->fetchColumn();

            if ($totalRows === 0) {
                $out .= "-- (Table vide)\n\n";
                continue;
            }

            $offset = 0;
            $batchSize = 250;

            while ($offset < $totalRows) {
                $selectStmt = $this->pdo->query("SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}");
                $rows = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $first = true;
                    $cols = array_keys($rows[0]);
                    $escapedCols = array_map(fn($c) => "`{$c}`", $cols);
                    $out .= "INSERT INTO `{$table}` (" . implode(', ', $escapedCols) . ") VALUES\n";

                    $valueLines = [];
                    foreach ($rows as $r) {
                        $escapedVals = [];
                        foreach ($r as $val) {
                            if ($val === null) {
                                $escapedVals[] = "NULL";
                            } elseif (is_numeric($val) && !is_string($val)) {
                                $escapedVals[] = (string)$val;
                            } else {
                                $escapedVals[] = $this->pdo->quote((string)$val);
                            }
                        }
                        $valueLines[] = "(" . implode(', ', $escapedVals) . ")";
                    }
                    $out .= implode(",\n", $valueLines) . ";\n";
                }

                $offset += $batchSize;
            }

            $out .= "\n";
        }

        $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $out .= "-- Fin de la sauvegarde\n";

        return $out;
    }

    /**
     * Restaure la base de données à partir d'un fichier de sauvegarde
     */
    public function restoreBackup(string $absolutePath, ?int $userId = null): array
    {
        if (!file_exists($absolutePath)) {
            return ['success' => false, 'error' => "Le fichier spécifié n'existe pas : {$absolutePath}"];
        }

        $startTime = microtime(true);
        $isGz = str_ends_with($absolutePath, '.gz');

        try {
            $sqlContent = '';
            if ($isGz) {
                if (!function_exists('gzopen')) {
                    throw new \RuntimeException("L'extension PHP Zlib n'est pas activée pour lire ce fichier compressé.");
                }
                $gz = gzopen($absolutePath, 'rb');
                while (!gzeof($gz)) {
                    $sqlContent .= gzread($gz, 65536);
                }
                gzclose($gz);
            } else {
                $sqlContent = file_get_contents($absolutePath);
            }

            if (empty(trim($sqlContent))) {
                throw new \RuntimeException("Le fichier de sauvegarde est vide.");
            }

            // Exécution du script SQL
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // Découpage et exécution par bloc ou directe
            $queries = $this->splitSqlQueries($sqlContent);
            $executed = 0;

            foreach ($queries as $q) {
                $q = trim($q);
                if (!empty($q)) {
                    $this->pdo->exec($q);
                    $executed++;
                }
            }

            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $duration = round(microtime(true) - $startTime, 2);

            LogService::log(
                'sauvegarde.restauree',
                'sauvegardes',
                "Base de données restaurée avec succès depuis " . basename($absolutePath) . " ({$executed} requêtes en {$duration}s)",
                null,
                null,
                $userId
            );

            return [
                'success' => true,
                'queries_count' => $executed,
                'duration' => $duration,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            return [
                'success' => false,
                'queries_count' => 0,
                'duration' => round(microtime(true) - $startTime, 2),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Découpeur d'instructions SQL robuste
     */
    private function splitSqlQueries(string $sql): array
    {
        $queries = [];
        $query = '';
        $inString = false;
        $stringChar = '';
        $escaped = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $query .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            } else {
                if ($char === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') {
                    // Commentaire -- jusqu'à la fin de la ligne
                    while ($i < $len && $sql[$i] !== "\n") {
                        $i++;
                    }
                    continue;
                }

                if ($char === '#' || ($char === '/' && isset($sql[$i + 1]) && $sql[$i + 1] === '*')) {
                    // Commentaire # ou /* ... */
                    if ($char === '#') {
                        while ($i < $len && $sql[$i] !== "\n") {
                            $i++;
                        }
                    } else {
                        $i += 2;
                        while ($i < $len && !($sql[$i - 1] === '*' && $sql[$i] === '/')) {
                            $i++;
                        }
                    }
                    continue;
                }

                if ($char === "'" || $char === '"' || $char === '`') {
                    $inString = true;
                    $stringChar = $char;
                    $query .= $char;
                } elseif ($char === ';') {
                    $trimmed = trim($query);
                    if ($trimmed !== '') {
                        $queries[] = $trimmed;
                    }
                    $query = '';
                } else {
                    $query .= $char;
                }
            }
        }

        $trimmed = trim($query);
        if ($trimmed !== '') {
            $queries[] = $trimmed;
        }

        return $queries;
    }

    /**
     * Applique la politique de rétention en conservant les N sauvegardes les plus récentes
     */
    public function applyRetentionPolicy(int $keepCount = 20): int
    {
        $all = $this->model->all(1000);
        if (count($all) <= $keepCount) {
            return 0;
        }

        $deleted = 0;
        $toDelete = array_slice($all, $keepCount);

        foreach ($toDelete as $item) {
            $filePath = dirname(dirname(__DIR__)) . '/' . ltrim($item['chemin'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $this->model->delete((int)$item['id']);
            $deleted++;
        }

        return $deleted;
    }
}
