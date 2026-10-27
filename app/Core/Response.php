<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulation de la réponse HTTP
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(mixed $data, int $status = 200): void
    {
        $this->setStatusCode($status);
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->sendHeaders();
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function redirect(string $url, int $status = 302): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $basePath = $config['app']['base_path'] ?? '';

        // Si l'URL commence par /, on préfixe avec basePath si pas déjà préfixé
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            if ($basePath !== '' && !str_starts_with($url, $basePath)) {
                $url = $basePath . $url;
            }
        }

        http_response_code($status);
        header("Location: $url");
        exit;
    }

    public function send(string $content, int $status = 200): void
    {
        $this->setStatusCode($status);
        $this->sendHeaders();
        echo $content;
        exit;
    }

    private function sendHeaders(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }
}
