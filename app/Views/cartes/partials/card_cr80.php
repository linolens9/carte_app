<?php
/**
 * Rendu visuel d'une carte d'identité CR-80 (ISO/IEC 7810 ID-1 : 85.60 × 53.98 mm)
 * Variables attendues :
 * - $card : array (données enrichies de la carte, bénéficiaire, template et établissement)
 * - $interactive : bool (activer le flip 3D au clic/survol)
 * - $mode : 'recto_only' | 'verso_only' | 'both' | 'flip'
 * - $scale : float (défaut 1.0, ex: 0.8 ou 1.2)
 */

use App\Services\BarcodeService;
use App\Services\QrService;

$interactive = $interactive ?? true;
$mode = $mode ?? 'flip';
$scale = $scale ?? 1.0;

$template = [
    'nom' => $card['template_nom'] ?? 'Modèle par défaut',
    'couleur_principale' => $card['couleur_principale'] ?? '#1e3a8a',
    'couleur_secondaire' => $card['couleur_secondaire'] ?? '#3b82f6',
    'config_recto' => is_array($card['config_recto']) ? $card['config_recto'] : (json_decode($card['config_recto'] ?? '{}', true) ?: []),
    'config_verso' => is_array($card['config_verso']) ? $card['config_verso'] : (json_decode($card['config_verso'] ?? '{}', true) ?: []),
];

$b = $card['beneficiaire'] ?? [];
$type = $card['beneficiaire_type'] ?? 'eleve';

// URLs
$appUrl = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
$verifyUrl = $appUrl . '/verifier/' . ($card['jeton_verification'] ?? '');

// Génération Barcode et QR
$barcodeSvg = BarcodeService::generateSvg($card['numero_carte'] ?? 'C-0000-0000', 34, 1, false, '#0f172a');
$qrSvg = QrService::generateSvg($verifyUrl, 82, '#0f172a', '#ffffff');

// Photo
$photoSrc = null;
$rawPhoto = $b['photo'] ?? ($card['beneficiaire_photo'] ?? ($card['photo'] ?? null));

if (!empty($rawPhoto)) {
    if (str_starts_with($rawPhoto, 'data:image/') || str_starts_with($rawPhoto, 'http://') || str_starts_with($rawPhoto, 'https://')) {
        $photoSrc = $rawPhoto;
    } else {
        $cleanRel = ltrim(str_replace('\\', '/', (string)$rawPhoto), '/');
        $photoSrc = asset_url($cleanRel);
    }
}

// Logo de l'établissement ou du template
$logoSrc = null;
$rawLogo = $card['template_logo'] ?? ($card['etablissement_logo'] ?? ($card['logo'] ?? null));

if (empty($rawLogo) && !empty($card['etablissement_id'])) {
    static $etabLogosCache = [];
    $eId = (int)$card['etablissement_id'];
    if (!array_key_exists($eId, $etabLogosCache)) {
        try {
            $stmtLogo = \App\Core\Database::getInstance()->prepare("SELECT logo FROM etablissements WHERE id = ?");
            $stmtLogo->execute([$eId]);
            $etabLogosCache[$eId] = $stmtLogo->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            $etabLogosCache[$eId] = null;
        }
    }
    if (!empty($etabLogosCache[$eId])) {
        $rawLogo = $etabLogosCache[$eId];
    }
}

if (!empty($rawLogo)) {
    if (str_starts_with($rawLogo, 'data:image/') || str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://')) {
        $logoSrc = $rawLogo;
    } else {
        $cleanRel = ltrim(str_replace('\\', '/', (string)$rawLogo), '/');
        $logoSrc = asset_url($cleanRel);
    }
}

// Cachet / Tampon de l'établissement ou du template
$cachetSrc = null;
$rawCachet = $card['etablissement_cachet'] ?? ($card['cachet'] ?? ($card['template_signature'] ?? null));

if (empty($rawCachet) && !empty($card['etablissement_id'])) {
    static $etabCachetsCache = [];
    $eId = (int)$card['etablissement_id'];
    if (!array_key_exists($eId, $etabCachetsCache)) {
        try {
            $stmtCachet = \App\Core\Database::getInstance()->prepare("SELECT cachet FROM etablissements WHERE id = ?");
            $stmtCachet->execute([$eId]);
            $etabCachetsCache[$eId] = $stmtCachet->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            $etabCachetsCache[$eId] = null;
        }
    }
    if (!empty($etabCachetsCache[$eId])) {
        $rawCachet = $etabCachetsCache[$eId];
    }
}

if (!empty($rawCachet)) {
    if (str_starts_with($rawCachet, 'data:image/') || str_starts_with($rawCachet, 'http://') || str_starts_with($rawCachet, 'https://')) {
        $cachetSrc = $rawCachet;
    } else {
        $cleanRel = ltrim(str_replace('\\', '/', (string)$rawCachet), '/');
        $cachetSrc = asset_url($cleanRel);
    }
}

$etabNom = $card['etablissement_nom'] ?? ($card['etab_nom'] ?? null);
if (empty($etabNom) && !empty($card['etablissement_id'])) {
    static $etabNomsCache = [];
    $eId = (int)$card['etablissement_id'];
    if (!array_key_exists($eId, $etabNomsCache)) {
        try {
            $stmtNom = \App\Core\Database::getInstance()->prepare("SELECT nom FROM etablissements WHERE id = ?");
            $stmtNom->execute([$eId]);
            $etabNomsCache[$eId] = $stmtNom->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            $etabNomsCache[$eId] = null;
        }
    }
    if (!empty($etabNomsCache[$eId])) {
        $etabNom = $etabNomsCache[$eId];
    }
}
$etabNom = $etabNom ?: 'Établissement Officiel';
$etabCode = $card['etablissement_code'] ?? ($card['etab_code'] ?? 'GA');

$rectoCfg = $template['config_recto'];
$versoCfg = $template['config_verso'];

$c1 = $template['couleur_principale'];
$c2 = $template['couleur_secondaire'];
?>

<div class="cr80-wrapper cr80-mode-<?= htmlspecialchars($mode) ?>" style="--c-primary: <?= htmlspecialchars($c1) ?>; --c-secondary: <?= htmlspecialchars($c2) ?>;">
    <?php if ($mode === 'flip'): ?>
        <div class="cr80-card-3d" id="cardFlipContainer-<?= $card['id'] ?? 'preview' ?>">
            <div class="cr80-card-inner">
                <!-- RECTO -->
                <div class="cr80-face cr80-recto">
                    <?php include __DIR__ . '/_recto_content.php'; ?>
                </div>

                <!-- VERSO -->
                <div class="cr80-face cr80-verso">
                    <?php include __DIR__ . '/_verso_content.php'; ?>
                </div>
            </div>
        </div>

        <?php if ($interactive): ?>
            <div class="text-center mt-2 no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 btn-flip-trigger" data-target="#cardFlipContainer-<?= $card['id'] ?? 'preview' ?>">
                    <i class="bi bi-arrow-repeat me-1"></i> Retourner la carte (Verso)
                </button>
            </div>
        <?php endif; ?>

    <?php elseif ($mode === 'both'): ?>
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <div class="cr80-face cr80-recto cr80-flat">
                <?php include __DIR__ . '/_recto_content.php'; ?>
            </div>
            <div class="cr80-face cr80-verso cr80-flat" style="transform: none !important; -webkit-transform: none !important; backface-visibility: visible !important;">
                <?php include __DIR__ . '/_verso_content.php'; ?>
            </div>
        </div>
    <?php elseif ($mode === 'recto_only'): ?>
        <div class="cr80-face cr80-recto cr80-flat">
            <?php include __DIR__ . '/_recto_content.php'; ?>
        </div>
    <?php elseif ($mode === 'verso_only'): ?>
        <div class="cr80-face cr80-verso cr80-flat" style="transform: none !important; -webkit-transform: none !important; backface-visibility: visible !important;">
            <?php include __DIR__ . '/_verso_content.php'; ?>
        </div>
    <?php endif; ?>
</div>
