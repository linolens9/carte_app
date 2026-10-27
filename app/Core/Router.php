<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur HTTP léger et performant avec gestion des paramètres et middlewares
 */
class Router
{
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        // Convertit les variables {id} en expressions régulières
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $normalizedPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $normalizedPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(): void
    {
        $currentMethod = $this->request->getMethod();
        $currentPath = $this->request->getPath();

        $matchedMethod = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $currentPath, $matches)) {
                if ($route['method'] !== $currentMethod) {
                    $matchedMethod = true;
                    continue;
                }

                // Extraction des paramètres nommés
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = urldecode($value);
                    }
                }

                // Vérification CSRF automatique pour les requêtes POST/PUT/DELETE
                if (in_array($currentMethod, ['POST', 'PUT', 'DELETE'], true)) {
                    $isCsrfExempt = in_array('no-csrf', $route['middlewares'], true);
                    if (!$isCsrfExempt) {
                        $token = $this->request->getCsrfToken();
                        if (!Csrf::validateToken($token)) {
                            if ($this->request->isAjax()) {
                                $this->response->json([
                                    'success' => false,
                                    'message' => 'Jeton CSRF invalide ou expiré. Veuillez rafraîchir la page.'
                                ], 403);
                            } else {
                                Session::setFlash('danger', 'Session expirée ou requête invalide (erreur CSRF). Veuillez réessayer.');
                                $this->response->redirect('/');
                            }
                            return;
                        }
                    }
                }

                // Exécution des middlewares
                foreach ($route['middlewares'] as $mw) {
                    if ($mw === 'no-csrf') {
                        continue;
                    }
                    $this->runMiddleware($mw);
                }

                // Appel du handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $this->request, $this->response, $params);
                    return;
                }

                if (is_array($handler)) {
                    [$controllerClass, $method] = $handler;
                    if (!class_exists($controllerClass)) {
                        throw new \RuntimeException("Contrôleur non trouvé : $controllerClass");
                    }
                    $controller = new $controllerClass($this->request, $this->response);
                    if (!method_exists($controller, $method)) {
                        throw new \RuntimeException("Méthode $method non trouvée sur $controllerClass");
                    }
                    $controller->$method($params);
                    return;
                }
            }
        }

        if ($matchedMethod) {
            $this->response->setStatusCode(405);
            if ($this->request->isAjax()) {
                $this->response->json(['success' => false, 'message' => 'Méthode HTTP non autorisée.'], 405);
            } else {
                echo "<h1>405 - Méthode non autorisée</h1>";
            }
            return;
        }

        // 404
        $this->response->setStatusCode(404);
        if ($this->request->isAjax()) {
            $this->response->json(['success' => false, 'message' => 'Page introuvable.'], 404);
        } else {
            // Vue 404
            $basePath = (require dirname(__DIR__, 2) . '/config/config.php')['app']['base_path'] ?? '';
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>404 - Page non trouvée</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light d-flex align-items-center justify-content-center vh-100">
                <div class="text-center p-5 bg-white rounded-4 shadow-sm" style="max-width: 500px;">
                    <h1 class="display-1 fw-bold text-primary">404</h1>
                    <h3 class="fw-bold mb-3">Page non trouvée</h3>
                    <p class="text-muted mb-4">La page demandée n'existe pas ou a été déplacée.</p>
                    <a href="<?= $basePath ?>/" class="btn btn-primary px-4 py-2 rounded-pill">Retour à l'accueil</a>
                </div>
            </body>
            </html>
            <?php
        }
    }

    private function runMiddleware(string $middleware): void
    {
        // auth
        if ($middleware === 'auth') {
            if (!Auth::check()) {
                if ($this->request->isAjax()) {
                    $this->response->json(['success' => false, 'message' => 'Authentification requise.'], 401);
                } else {
                    Session::setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
                    $this->response->redirect('/login');
                }
            }
            return;
        }

        // guest (seulement si pas connecté)
        if ($middleware === 'guest') {
            if (Auth::check()) {
                $this->response->redirect('/dashboard');
            }
            return;
        }

        // role:admin
        if (str_starts_with($middleware, 'role:')) {
            $roleCode = substr($middleware, 5);
            if (!Auth::hasRole($roleCode)) {
                $this->forbidden('Accès refusé. Rôle insuffisant.');
            }
            return;
        }

        // permission:etablissement.creer
        if (str_starts_with($middleware, 'permission:')) {
            $permissionCode = substr($middleware, 11);
            if (!Auth::hasPermission($permissionCode)) {
                $this->forbidden('Accès refusé. Permission manquante : ' . $permissionCode);
            }
            return;
        }
    }

    private function forbidden(string $message): void
    {
        if ($this->request->isAjax()) {
            $this->response->json(['success' => false, 'message' => $message], 403);
        } else {
            Session::setFlash('danger', $message);
            $this->response->redirect('/dashboard');
        }
        exit;
    }
}
