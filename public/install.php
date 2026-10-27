<?php
declare(strict_types=1);

/**
 * Assistant d'installation et de déploiement InfinityFree - MBOLO Carte App
 */

// Endpoint AJAX de test rapide / scan
if (isset($_GET['action']) && $_GET['action'] === 'detect_host') {
    header('Content-Type: application/json; charset=utf-8');
    $dbUser = trim($_GET['user'] ?? 'if0_43279558');
    $dbPass = trim($_GET['pass'] ?? 'Linoscott9');
    
    $candidateNumbers = [
        100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110,
        200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212,
        300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313
    ];
    
    $found = null;
    $tested = 0;
    
    foreach ($candidateNumbers as $num) {
        foreach (['infinityfree.com', 'epizy.com'] as $domain) {
            $host = "sql{$num}.{$domain}";
            $tested++;
            
            $fp = @fsockopen($host, 3306, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                try {
                    $pdo = new PDO("mysql:host={$host};port=3306", $dbUser, $dbPass, [
                        PDO::ATTR_TIMEOUT => 1,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $found = $host;
                    break 2;
                } catch (PDOException $pe) {
                    if ($pe->getCode() !== 1045 && !str_contains($pe->getMessage(), 'Access denied for user')) {
                        $found = $host;
                        break 2;
                    }
                }
            }
        }
    }
    
    if ($found) {
        echo json_encode(['success' => true, 'host' => $found]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "La détection n'a pas pu identifier le serveur parmi {$tested} hôtes testés. Veuillez copier l'hôte exact depuis votre espace InfinityFree."
        ]);
    }
    exit;
}

$lockFile = dirname(__DIR__) . '/storage/installed.lock';
$envFile  = dirname(__DIR__) . '/.env';
$sqlFile  = dirname(__DIR__) . '/database/schema_infinityfree.sql';

$alreadyInstalled = file_exists($lockFile);

// Lecture de la config existante si .env existe
$currentDb = [
    'host' => '',
    'port' => 3306,
    'name' => 'if0_43279558_carteapp',
    'user' => 'if0_43279558',
    'pass' => 'Linoscott9',
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, 'DB_HOST=')) {
            $val = trim(substr($line, 8));
            if ($val !== '127.0.0.1' && strtolower($val) !== 'localhost') {
                $currentDb['host'] = $val;
            }
        }
        if (str_starts_with($line, 'DB_PORT=')) $currentDb['port'] = (int)trim(substr($line, 8));
        if (str_starts_with($line, 'DB_DATABASE=')) $currentDb['name'] = trim(substr($line, 12));
        if (str_starts_with($line, 'DB_USERNAME=')) $currentDb['user'] = trim(substr($line, 12));
        if (str_starts_with($line, 'DB_PASSWORD=')) $currentDb['pass'] = trim(substr($line, 12));
    }
}

// Préserver les valeurs soumises par l'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['db_host'])) $currentDb['host'] = trim($_POST['db_host']);
    if (!empty($_POST['db_name'])) $currentDb['name'] = trim($_POST['db_name']);
    if (!empty($_POST['db_user'])) $currentDb['user'] = trim($_POST['db_user']);
    if (isset($_POST['db_pass']))  $currentDb['pass'] = trim($_POST['db_pass']);
    if (!empty($_POST['db_port'])) $currentDb['port'] = (int)$_POST['db_port'];
}

$error = null;
$errorDetails = null;
$errorType = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $dbHost = $currentDb['host'];
    $dbPort = $currentDb['port'];
    $dbName = $currentDb['name'];
    $dbUser = $currentDb['user'];
    $dbPass = $currentDb['pass'];

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = "Veuillez remplir l'hôte MySQL, le nom de la base de données et l'identifiant.";
    } elseif ($dbHost === '127.0.0.1' || strtolower($dbHost) === 'localhost') {
        $error = "Sur InfinityFree, vous ne devez PAS utiliser '127.0.0.1' ou 'localhost'.";
        $errorDetails = "InfinityFree héberge les bases de données sur un serveur séparé. Veuillez indiquer votre hôte MySQL exact (ex: sql202.infinityfree.com ou sql300.epizy.com) visible dans votre espace InfinityFree > 'Database Details'.";
    } else {
        $debugLog = dirname(__DIR__) . '/storage/logs/install_debug.log';
        @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Attempt: Host=[{$dbHost}], Port=[{$dbPort}], DB=[{$dbName}], User=[{$dbUser}], PassLen=[" . strlen($dbPass) . "]\n", FILE_APPEND);

        try {
            // Test préalable : le serveur répond-il et l'utilisateur a-t-il créé des bases ?
            $detectedDbs = [];
            try {
                $testServerPdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                $stmt = $testServerPdo->query("SHOW DATABASES");
                $allDbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $detectedDbs = array_values(array_filter($allDbs, fn($d) => str_starts_with($d, $dbUser . '_')));
                @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Server connected! Detected DBs: " . json_encode($detectedDbs) . "\n", FILE_APPEND);
            } catch (PDOException $ePre) {
                @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Pre-check notice: " . $ePre->getMessage() . "\n", FILE_APPEND);
            }

            // Connexion directe avec le nom de base fourni
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 8,
            ]);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Exécution du schéma SQL
            if (!file_exists($sqlFile)) {
                $sqlFile = dirname(__DIR__) . '/database/carte_app.sql';
            }

            if (!file_exists($sqlFile)) {
                throw new Exception("Fichier SQL de schéma introuvable.");
            }

            $sqlContent = file_get_contents($sqlFile);
            $sqlContent = preg_replace('/^DROP DATABASE\s+[^;]+;/im', '', $sqlContent);
            $sqlContent = preg_replace('/^CREATE DATABASE\s+[^;]+;/im', '', $sqlContent);
            $sqlContent = preg_replace('/^USE\s+[^;]+;/im', '', $sqlContent);

            // Convertir les CREATE TABLE en CREATE TABLE IF NOT EXISTS
            $sqlContent = preg_replace('/CREATE TABLE\s+(?!IF NOT EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $sqlContent);
            // Convertir les INSERT INTO en INSERT IGNORE INTO
            $sqlContent = preg_replace('/INSERT INTO\s+/i', 'INSERT IGNORE INTO ', $sqlContent);

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            $queries = explode(";\n", $sqlContent);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $qe) {
                        $msgQe = $qe->getMessage();
                        // Ignorer si la table existe déjà (42S01), doublon (23000), ou colonne inconnue (42S22)
                        if (str_contains($msgQe, 'already exists') 
                            || str_contains($msgQe, 'Duplicate entry') 
                            || str_contains($msgQe, 'Column not found')
                            || $qe->getCode() == '42S01' 
                            || $qe->getCode() == '23000'
                            || $qe->getCode() == '42S22') {
                            @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Statement notice (skipped): " . $msgQe . "\n", FILE_APPEND);
                            continue;
                        }
                        throw $qe;
                    }
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // Mise à jour du fichier .env
            $envData = [
                'APP_NAME' => "'MBOLO'",
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_TIMEZONE' => 'Africa/Libreville',
                'APP_URL' => '', // Auto-détection du domaine
                'DB_HOST' => $dbHost,
                'DB_PORT' => (string)$dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'SESSION_LIFETIME' => '1800',
                'MAIL_DRIVER' => 'mail',
                'WHATSAPP_DRIVER' => 'direct',
                'SMS_DRIVER' => 'direct'
            ];

            $envLines = [];
            foreach ($envData as $k => $v) {
                $envLines[] = "{$k}={$v}";
            }
            file_put_contents($envFile, implode("\n", $envLines) . "\n");

            @touch($lockFile);
            $alreadyInstalled = true;
            $success = "La base de données a été installée avec succès et la configuration a été enregistrée !";
            @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - SUCCESS! Tables installed and .env written.\n", FILE_APPEND);

        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            $error = "Erreur de connexion MySQL : " . $msg;
            @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - PDOException caught: [{$code}] {$msg}\n", FILE_APPEND);

            if (str_contains($msg, 'Connection refused') || str_contains($msg, '[2002]')) {
                $errorType = 'connection_refused';
                $errorDetails = "Le serveur MySQL <strong>" . htmlspecialchars($dbHost) . "</strong> est injoignable ou refuse la connexion. Vérifiez l'hôte dans votre espace InfinityFree.";
            } elseif (str_contains($msg, 'Access denied') || str_contains($msg, '[1045]')) {
                $errorType = 'access_denied';
                $errorDetails = "Le serveur MySQL a bien été joint, mais les identifiants ont été rejetés.";
            } elseif (str_contains($msg, 'Unknown database') || str_contains($msg, '[1049]')) {
                $errorType = 'unknown_database';
                $errorDetails = "La base de données <strong>" . htmlspecialchars($dbName) . "</strong> n'existe pas encore. Créez-la d'abord dans votre panneau cPanel &gt; MySQL Databases.";
            }
        } catch (Exception $e) {
            $error = "Erreur d'installation : " . $e->getMessage();
            @file_put_contents($debugLog, date('Y-m-d H:i:s') . " - General Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Vérification des prérequis
$checks = [
    'PHP >= 8.0' => ['status' => version_compare(PHP_VERSION, '8.0.0', '>='), 'current' => PHP_VERSION],
    'Extension PDO' => ['status' => extension_loaded('pdo'), 'current' => 'Activée'],
    'Extension PDO MySQL' => ['status' => extension_loaded('pdo_mysql'), 'current' => 'Activée'],
    'Extension GD (Photos)' => ['status' => extension_loaded('gd'), 'current' => 'Activée'],
    'Extension cURL' => ['status' => extension_loaded('curl'), 'current' => 'Activée'],
    'Extension Mbstring' => ['status' => extension_loaded('mbstring'), 'current' => 'Activée'],
    'Dossier storage/ inscriptible' => ['status' => is_writable(dirname(__DIR__) . '/storage'), 'current' => 'Accessible'],
    'Dossier public/uploads/ inscriptible' => ['status' => is_writable(__DIR__ . '/uploads'), 'current' => 'Accessible'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant d'Installation - MBOLO Carte App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #334155;
        }
        .install-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            max-width: 840px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .install-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 35px 30px;
            text-align: center;
        }
        .badge-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            margin-right: 8px;
        }
        .guide-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
        }
    </style>
</head>
<body>

<div class="install-card">
    <div class="install-header">
        <div class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle mb-3 p-3 shadow-sm">
            <i class="bi bi-credit-card-2-front-fill fs-2"></i>
        </div>
        <h2 class="fw-bold mb-1">MBOLO Carte App</h2>
        <p class="text-white-50 mb-0">Assistant de déploiement & configuration InfinityFree</p>
    </div>

    <div class="p-4 p-md-5">
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center rounded-4 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Félicitations ! Déploiement réussi</h5>
                    <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>

            <div class="card border-0 bg-light rounded-4 p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Identifiants d'administration par défaut :</h6>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <span class="text-muted small d-block">Identifiant / Email :</span>
                            <strong class="text-dark">admin@carteapp.com</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-white rounded-3 border">
                            <span class="text-muted small d-block">Mot de passe :</span>
                            <strong class="text-dark">Admin@2026</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="./login" class="btn btn-primary btn-lg rounded-pill fw-semibold py-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Accéder à l'application
                </a>
            </div>

        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger rounded-4 p-4 mb-4 border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger flex-shrink-0 mt-1"></i>
                        <div class="w-100">
                            <h6 class="alert-heading fw-bold mb-1 text-danger"><?= htmlspecialchars($error) ?></h6>
                            <?php if ($errorDetails): ?>
                                <div class="small mt-2 text-dark"><?= $errorDetails ?></div>
                            <?php endif; ?>

                            <?php if ($errorType === 'access_denied'): ?>
                                <div class="mt-3 p-3 bg-white rounded-3 border border-danger-subtle small text-dark">
                                    <h6 class="fw-bold text-danger mb-2"><i class="bi bi-key-fill me-1"></i>Comment résoudre l'erreur [1045] Access denied :</h6>
                                    <p class="mb-2">Le serveur MySQL a bien répondu, mais le mot de passe est rejeté. Sur InfinityFree :</p>
                                    <ol class="mb-2 ps-3">
                                        <li>Allez sur votre espace client : <strong><a href="https://dash.infinityfree.com/accounts" target="_blank">dash.infinityfree.com/accounts</a></strong></li>
                                        <li>Cliquez sur votre compte <strong>if0_43279558</strong>.</li>
                                        <li>Dans l'encadré <strong>Account Details</strong>, repérez la ligne <strong>Password</strong> et cliquez sur <strong>« Show »</strong> (ou l'icône œil) pour afficher le mot de passe exact de votre compte.</li>
                                        <li>Copiez ce mot de passe exact et collez-le dans le champ ci-dessous.</li>
                                        <li><em>Vérifiez également</em> que vous avez bien créé la base de données dans <strong>Control Panel &gt; MySQL Databases</strong>.</li>
                                    </ol>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($alreadyInstalled): ?>
                <div class="alert alert-warning d-flex align-items-center rounded-4 p-3 mb-4" role="alert">
                    <i class="bi bi-info-circle-fill fs-3 me-3 text-warning"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Application déjà installée</h6>
                        <div class="small">L'application a déjà été configurée. Vous pouvez vous reconnecter directement ou modifier les paramètres ci-dessous si vous souhaitez réinstaller la base de données.</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Étape 1 : Vérification système -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3">
                    <span class="badge-step bg-primary">1</span>Vérification de l'environnement d'hébergement
                </h6>
                <div class="row g-2">
                    <?php foreach ($checks as $title => $c): ?>
                        <div class="col-sm-6">
                            <div class="p-2 px-3 border rounded-3 bg-white d-flex align-items-center justify-content-between">
                                <span class="small fw-semibold"><?= htmlspecialchars($title) ?></span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                    <i class="bi bi-check2"></i> OK
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Étape 2 : Configuration MySQL InfinityFree -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="install">
                
                <h6 class="fw-bold text-dark mb-3">
                    <span class="badge-step bg-primary">2</span>Paramètres MySQL InfinityFree
                </h6>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold mb-1">Hôte MySQL (MySQL Hostname) <span class="text-danger">*</span></label>
                        <input type="text" name="db_host" id="inputDbHost" class="form-control rounded-3" 
                               value="<?= htmlspecialchars($currentDb['host']) ?>" 
                               placeholder="ex: sql202.infinityfree.com" required>
                        <div class="form-text small">Indiqué dans votre espace client InfinityFree sous <strong>Database Details</strong>.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Port MySQL</label>
                        <input type="number" name="db_port" class="form-control rounded-3" 
                               value="<?= htmlspecialchars((string)$currentDb['port']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Nom de la base de données <span class="text-danger">*</span></label>
                        <input type="text" name="db_name" class="form-control rounded-3" 
                               value="<?= htmlspecialchars($currentDb['name']) ?>" 
                               placeholder="ex: if0_43279558_carteapp" required>
                        <div class="form-text small">Créée au préalable dans cPanel &gt; MySQL Databases.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Nom d'utilisateur MySQL <span class="text-danger">*</span></label>
                        <input type="text" name="db_user" id="inputDbUser" class="form-control rounded-3" 
                               value="<?= htmlspecialchars($currentDb['user']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">
                            Mot de passe MySQL (Account Password) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="db_pass" id="inputDbPass" class="form-control rounded-start-3" 
                                   value="<?= htmlspecialchars($currentDb['pass']) ?>" required>
                            <button type="button" class="btn btn-outline-secondary rounded-end-3" id="btnTogglePass" title="Afficher/Masquer le mot de passe">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <div class="form-text small">
                            <i class="bi bi-info-circle me-1"></i>Dans <a href="https://dash.infinityfree.com/accounts" target="_blank">dash.infinityfree.com</a> &gt; compte <strong>if0_43279558</strong> &gt; section <strong>Account Details</strong> &gt; cliquez sur <strong>« Show »</strong> pour copier le mot de passe réel du compte.
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-semibold py-3 shadow-sm">
                        <i class="bi bi-lightning-charge-fill me-2"></i>Tester la connexion et Initialiser la Base
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btnTogglePass');
    const inputPass = document.getElementById('inputDbPass');
    const eyeIcon = document.getElementById('eyeIcon');

    if (btnToggle && inputPass && eyeIcon) {
        btnToggle.addEventListener('click', function () {
            if (inputPass.type === 'password') {
                inputPass.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                inputPass.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }
});
</script>

</body>
</html>
