<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Gestionnaire de connexion PDO Singleton
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = self::$config;
            if (empty($cfg)) {
                $all = require dirname(__DIR__, 2) . '/config/config.php';
                $cfg = $all['db'] ?? [];
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'] ?? '127.0.0.1',
                $cfg['port'] ?? 3306,
                $cfg['database'] ?? 'carte_app',
                $cfg['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO(
                    $dsn,
                    $cfg['username'] ?? 'root',
                    $cfg['password'] ?? '',
                    $options
                );
                self::$instance->exec("SET NAMES " . ($cfg['charset'] ?? 'utf8mb4') . " COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                throw new RuntimeException("Erreur de connexion à la base de données : " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::getInstance()->inTransaction()) {
            return self::getInstance()->rollBack();
        }
        return false;
    }
}
