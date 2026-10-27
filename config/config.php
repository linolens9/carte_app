<?php
declare(strict_types=1);

/**
 * Configuration globale de l'application
 */

// Chargement éventuel d'un fichier .env simple si présent
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = dirname($scriptName);
if ($scriptDir === '/' || $scriptDir === '\\') {
    $scriptDir = '';
}

// Si la requête courante (REQUEST_URI) n'inclut pas '/public' (accès via le .htaccess racine)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (str_ends_with($scriptDir, '/public') && !str_contains($requestUri, '/public')) {
    $basePath = substr($scriptDir, 0, -7);
} else {
    $basePath = $scriptDir;
}
$basePath = rtrim($basePath, '/');
if ($basePath !== '' && !str_starts_with($basePath, '/')) {
    $basePath = '/' . $basePath;
}

if (PHP_SAPI === 'cli' && in_array($basePath, ['/scratch', '/bin'], true)) {
    $basePath = '/carte_app';
}

// Protocole
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = !empty($_ENV['APP_URL']) ? rtrim($_ENV['APP_URL'], '/') : ($protocol . $host . ($basePath !== '' ? $basePath : ''));

$config = [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'MBOLO',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'base_url' => $baseUrl,
        'base_path' => $basePath,
        'url' => $baseUrl,
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Libreville',
        'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 1800), // 30 minutes
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'carte_app',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
    'upload' => [
        'max_photo_size' => 10 * 1024 * 1024, // 10 Mo avant traitement
        'allowed_photo_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_logo_types' => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'],
        'path' => dirname(__DIR__) . '/public/uploads',
    ],
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 15,
        'min_password_length' => 10,
    ],
    'mail' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp', // smtp, mail, log
        'host' => $_ENV['MAIL_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 1025),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? null,
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@carteapp.ga',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'Carte App'),
    ],
    'sms' => [
        'driver' => $_ENV['SMS_DRIVER'] ?? 'direct', // direct (sms: uri), gateway, log
        'gateway_url' => $_ENV['SMS_GATEWAY_URL'] ?? '',
        'api_key' => $_ENV['SMS_API_KEY'] ?? '',
        'sender_id' => $_ENV['SMS_SENDER_ID'] ?? 'CarteApp',
    ],
    'whatsapp' => [
        'driver' => $_ENV['WHATSAPP_DRIVER'] ?? 'direct', // direct (wa.me), cloud_api, log
        'api_url' => $_ENV['WHATSAPP_API_URL'] ?? '',
        'token' => $_ENV['WHATSAPP_TOKEN'] ?? '',
        'phone_number_id' => $_ENV['WHATSAPP_PHONE_ID'] ?? '',
    ]
];

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}

$GLOBALS['app_config'] = $config;

if (!function_exists('config')) {
    function config(?string $key = null, $default = null) {
        $cfg = $GLOBALS['app_config'] ?? null;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/config.php';
        }
        if ($key === null) {
            return $cfg;
        }
        $parts = explode('.', $key);
        $val = $cfg;
        foreach ($parts as $p) {
            if (!is_array($val) || !array_key_exists($p, $val)) {
                return $default;
            }
            $val = $val[$p];
        }
        return $val;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string {
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
        $base = rtrim((string)config('app.base_path', '/carte_app'), '/');
        return ($base !== '' ? $base : '') . '/' . $cleanPath;
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string {
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
        $base = defined('PUBLIC_PATH') ? PUBLIC_PATH : (dirname(__DIR__) . '/public');
        return $cleanPath !== '' ? ($base . '/' . $cleanPath) : $base;
    }
}

return $config;
