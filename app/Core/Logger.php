<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Journal d'audit et de sécurité inaltérable
 */
class Logger
{
    public static function log(
        ?int $userId,
        ?int $etablissementId,
        string $action,
        ?string $objetType = null,
        ?int $objetId = null,
        mixed $donneesAvant = null,
        mixed $donneesApres = null,
        ?Request $request = null
    ): void {
        try {
            $db = Database::getInstance();

            $ip = $request ? $request->getIp() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $ua = $request ? $request->getUserAgent() : ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

            $avantJson = $donneesAvant !== null ? json_encode($donneesAvant, JSON_UNESCAPED_UNICODE) : null;
            $apresJson = $donneesApres !== null ? json_encode($donneesApres, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $db->prepare("
                INSERT INTO logs (
                    user_id, etablissement_id, action,
                    objet_type, objet_id,
                    donnees_avant, donnees_apres,
                    adresse_ip, user_agent, created_at
                ) VALUES (
                    :uid, :eid, :action,
                    :obj_type, :obj_id,
                    :d_avant, :d_apres,
                    :ip, :ua, NOW()
                )
            ");

            $stmt->execute([
                ':uid' => $userId,
                ':eid' => $etablissementId,
                ':action' => $action,
                ':obj_type' => $objetType,
                ':obj_id' => $objetId,
                ':d_avant' => $avantJson,
                ':d_apres' => $apresJson,
                ':ip' => $ip,
                ':ua' => substr($ua, 0, 255)
            ]);
        } catch (Throwable $e) {
            // Écriture de secours dans un fichier de logs
            $logFile = dirname(__DIR__, 2) . '/storage/logs/system.log';
            $date = date('Y-m-d H:i:s');
            $line = "[$date] LOG_FAIL: " . $e->getMessage() . " | Action: $action\n";
            @file_put_contents($logFile, $line, FILE_APPEND);
        }
    }
}
