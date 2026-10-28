<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulation de la requête HTTP
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private ?array $jsonPayload = null;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $override = $this->post['_method'] ?? $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
            if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'], true)) {
                return strtoupper($override);
            }
        }
        return $method;
    }

    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        // Enlever la query string
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Détection et suppression du préfixe de sous-dossier (ex: /carte_app ou /carte_app/public)
        $scriptName = str_replace('\\', '/', $this->server['SCRIPT_NAME'] ?? '');
        $scriptDir = dirname($scriptName);

        if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.') {
            if (str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
            } elseif (str_ends_with($scriptDir, '/public')) {
                $parentDir = substr($scriptDir, 0, -7);
                if ($parentDir !== '' && str_starts_with($path, $parentDir)) {
                    $path = substr($path, strlen($parentDir));
                }
            }
        }

        // Si le chemin commence par /index.php, on l'enlève
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, 10);
        }

        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * Alias de query() pour compatibilité
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        return $this->query($key, $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = array_merge($this->get, $this->post, $this->json());
        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->input();
    }

    public function json(): array
    {
        if ($this->jsonPayload === null) {
            $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw, true);
                $this->jsonPayload = is_array($decoded) ? $decoded : [];
            } else {
                $this->jsonPayload = [];
            }
        }
        return $this->jsonPayload;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return (!empty($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains($this->server['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function getIp(): string
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return substr($this->server['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
    }

    public function getCsrfToken(): ?string
    {
        return $this->post['_csrf_token']
            ?? $this->server['HTTP_X_CSRF_TOKEN']
            ?? $this->server['HTTP_X_XSRF_TOKEN']
            ?? null;
    }
}
