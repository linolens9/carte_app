<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Carte;

class VerificationController extends Controller
{
    private Carte $carteModel;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->carteModel = new Carte();
    }

    /**
     * Page publique de vérification par QR Code ou Jeton
     */
    public function verify(array $params = []): void
    {
        $jeton = trim((string)($params['jeton'] ?? $this->request->query('jeton') ?? ''));
        $carte = null;
        $isValid = false;
        $isExpired = false;
        $isDeactivated = false;

        if ($jeton !== '') {
            $carte = $this->carteModel->findByJeton($jeton);

            if (!$carte) {
                // Tenter par numéro de carte si saisi manuellement
                $carte = $this->carteModel->findByNumero($jeton);
            }

            if ($carte) {
                $today = date('Y-m-d');
                $isExpired = ($carte['date_expiration'] < $today);
                $isDeactivated = in_array($carte['statut'], ['desactivee', 'archivee']);
                $isValid = !$isExpired && !$isDeactivated && in_array($carte['statut'], ['active', 'imprimee', 'validee']);
            }
        }

        $this->render('verification/index', [
            'title' => 'Vérification Officielle de Carte d\'Identité',
            'jeton' => $jeton,
            'card' => $carte,
            'isValid' => $isValid,
            'isExpired' => $isExpired,
            'isDeactivated' => $isDeactivated
        ], 'blank');
    }
}
