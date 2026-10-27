<?php

declare(strict_types=1);

/**
 * Script CLI de sauvegarde automatique planifiée
 * Usage : php bin/cron_backup.php
 */

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require dirname(__DIR__) . '/config/config.php';
\App\Core\Database::init($config['db']);

echo "[" . date('Y-m-d H:i:s') . "] Démarrage de la sauvegarde automatique...\n";

$backupService = new \App\Services\BackupService();
$res = $backupService->createBackup('automatique', null, true);

if ($res['success']) {
    echo "[" . date('Y-m-d H:i:s') . "] SUCCÈS : Archive {$res['filename']} ({$res['size']} octets) générée en {$res['duration']}s.\n";
    exit(0);
} else {
    echo "[" . date('Y-m-d H:i:s') . "] ÉCHEC : {$res['error']}\n";
    exit(1);
}
