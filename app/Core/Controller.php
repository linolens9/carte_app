<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Contrôleur de base avec moteur de rendu de vues et validation
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected array $config;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    /**
     * Rendu d'une vue avec layout
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Données globales injectées dans toutes les vues
        $data['base_url'] = $this->config['app']['base_url'] ?? '';
        $data['base_path'] = $this->config['app']['base_path'] ?? '';
        $data['app_name'] = $this->config['app']['name'] ?? 'MBOLO';
        $data['current_user'] = Auth::user();
        $data['active_etab'] = Auth::getActiveEtablissement();
        $data['active_etab_id'] = Auth::getActiveEtablissementId();
        $data['flashes'] = Session::getAllFlashes();
        $data['csrf_input'] = Csrf::inputField();
        $data['csrf_token'] = Csrf::getToken();

        extract($data);

        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vue introuvable : $viewFile");
        }

        // Capture du contenu de la vue
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Si aucun layout demandé
        if ($layout === '' || $layout === 'none') {
            $this->response->send($content);
            return;
        }

        // Layout
        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout introuvable : $layoutFile");
        }

        ob_start();
        require $layoutFile;
        $html = ob_get_clean();

        $this->response->send($html);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        $db = Database::getInstance();

        foreach ($rules as $field => $fieldRules) {
            $value = trim((string)($data[$field] ?? ''));
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                // required
                if ($rule === 'required' && ($value === '')) {
                    $errors[$field][] = "Le champ est obligatoire.";
                    break;
                }

                if ($value === '' && !in_array('required', $ruleList, true)) {
                    continue;
                }

                // email
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Format d'adresse e-mail invalide.";
                }

                // min:X
                if (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $errors[$field][] = "Doit contenir au moins $min caractères.";
                    }
                }

                // max:X
                if (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field][] = "Ne doit pas dépasser $max caractères.";
                    }
                }

                // in:a,b,c
                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array($value, $allowed, true)) {
                        $errors[$field][] = "Valeur sélectionnée non autorisée.";
                    }
                }

                // unique:table,column[,ignore_id]
                if (str_starts_with($rule, 'unique:')) {
                    $parts = explode(',', substr($rule, 7));
                    $table = $parts[0];
                    $col = $parts[1] ?? $field;
                    $ignoreId = $parts[2] ?? null;

                    $sql = "SELECT COUNT(*) FROM `$table` WHERE `$col` = :val AND deleted_at IS NULL";
                    $p = [':val' => $value];
                    if ($ignoreId !== null && $ignoreId !== '') {
                        $sql .= " AND id != :ignore_id";
                        $p[':ignore_id'] = $ignoreId;
                    }

                    $stmt = $db->prepare($sql);
                    $stmt->execute($p);
                    if ((int)$stmt->fetchColumn() > 0) {
                        $errors[$field][] = "Cette valeur est déjà utilisée.";
                    }
                }
            }
        }

        return $errors;
    }
}
