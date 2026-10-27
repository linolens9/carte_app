<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Parametre;
use App\Services\LogService;

class ParametreController extends Controller
{
    private Parametre $model;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->model = new Parametre();
    }

    /**
     * Interface de configuration des paramètres de la plateforme
     */
    public function index(array $params = []): void
    {
        $allParams = $this->model->all(null);

        $this->render('parametres/index', [
            'params' => $allParams,
        ]);
    }

    /**
     * Enregistrement des paramètres mis à jour
     */
    public function update(array $params = []): void
    {
        $rawPost = $this->request->all();
        $userId = Auth::id();

        // Récupérer les paramètres soumis soit via params[cle], soit via champs directs
        if (isset($rawPost['params']) && is_array($rawPost['params'])) {
            $dataToSave = $rawPost['params'];
        } else {
            // Mapping de secours pour les clés aplaties par PHP (qui remplace les points par des underscores)
            $dbParams = $this->model->all(null);
            $keyMap = [];
            foreach (array_keys($dbParams) as $cle) {
                $keyMap[str_replace('.', '_', $cle)] = $cle;
            }

            $dataToSave = [];
            foreach ($rawPost as $k => $v) {
                if (in_array($k, ['_csrf', '_csrf_token', 'tab'], true)) {
                    continue;
                }
                $mappedKey = $keyMap[$k] ?? (str_contains($k, '_') && !isset($dbParams[$k]) ? preg_replace('/^([a-z]+)_/', '$1.', $k) : $k);
                $dataToSave[$mappedKey] = $v;
            }
        }

        $updatedCount = 0;
        foreach ($dataToSave as $key => $val) {
            if (in_array($key, ['_csrf', '_csrf_token', 'tab'], true)) {
                continue;
            }

            // Déterminer le type
            $type = 'texte';
            if (is_numeric($val)) {
                $type = 'nombre';
            } elseif (in_array($val, ['true', 'false', '1', '0'], true) && str_contains($key, 'actif')) {
                $type = 'booleen';
            }

            $this->model->set($key, $val, $type, null, $userId);
            $updatedCount++;
        }

        LogService::log(
            'parametre.maj',
            'parametres',
            "Mise à jour de {$updatedCount} paramètres de configuration",
            null,
            null,
            $userId
        );

        Session::flash('success', "Paramètres enregistrés avec succès.");
        $activeTab = $this->request->post('tab', 'general');
        $this->response->redirect("/parametres?tab={$activeTab}");
    }
}
