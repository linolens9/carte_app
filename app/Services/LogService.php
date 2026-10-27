<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class LogService
{
    /**
     * Enregistre un événement dans le journal d'audit (table logs)
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?int $etablissementId = null,
        ?int $objetId = null,
        ?int $userId = null,
        ?array $donneesAvant = null,
        ?array $donneesApres = null
    ): void {
        try {
            $pdo = Database::getInstance();
            $uid = $userId ?? Auth::id();
            $eid = $etablissementId ?? Auth::getActiveEtablissementId();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 255);

            // Si donnees_apres n'est pas fourni mais qu'on a une description, on la stocke
            if (!$donneesApres && $description !== '') {
                $donneesApres = ['description' => $description];
            }

            $stmt = $pdo->prepare("
                INSERT INTO logs (user_id, etablissement_id, action, objet_type, objet_id, donnees_avant, donnees_apres, adresse_ip, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $uid,
                $eid,
                $action,
                $module,
                $objetId,
                $donneesAvant ? json_encode($donneesAvant, JSON_UNESCAPED_UNICODE) : null,
                $donneesApres ? json_encode($donneesApres, JSON_UNESCAPED_UNICODE) : null,
                $ip,
                $ua,
            ]);
        } catch (\Throwable $e) {
            error_log("LogService error: " . $e->getMessage());
        }
    }
}
