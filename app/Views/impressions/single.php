<?php
/**
 * Page d'impression d'une carte unique (Format CR-80)
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="container py-4 text-center">
    <!-- Barre de contrôle à l'écran -->
    <div class="card-custom p-3 mb-4 mx-auto no-print text-start" style="max-width: 720px;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h6 class="fw-bold mb-0">Impression de la carte : <?= htmlspecialchars($card['numero_carte']) ?></h6>
                <div class="small text-muted">Bénéficiaire : <?= htmlspecialchars(($card['beneficiaire']['nom'] ?? '') . ' ' . ($card['beneficiaire']['prenom'] ?? '')) ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm">
                    <a href="?face=both" class="btn <?= ($mode === 'both') ? 'btn-primary' : 'btn-outline-secondary' ?>">Recto + Verso</a>
                    <a href="?face=duplex" class="btn <?= ($mode === 'duplex') ? 'btn-primary' : 'btn-outline-secondary' ?>" title="Page 1 Recto, Page 2 Verso (imprimantes à badges et recto-verso)">Recto-Verso (2 pages)</a>
                    <a href="?face=recto_only" class="btn <?= ($mode === 'recto_only') ? 'btn-primary' : 'btn-outline-secondary' ?>">Recto seul</a>
                    <a href="?face=verso_only" class="btn <?= ($mode === 'verso_only') ? 'btn-primary' : 'btn-outline-secondary' ?>">Verso seul</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Rendu d'impression -->
    <div class="d-flex flex-column align-items-center justify-content-center gap-4 my-3">
        <?php if ($mode === 'duplex'): ?>
            <!-- Page 1 : Recto -->
            <div class="single-card-print-page print-page-break mb-3">
                <div class="no-print text-white-50 small mb-2">--- PAGE 1 (RECTO) ---</div>
                <div class="crop-mark-card d-inline-block">
                    <?php
                    $mode = 'recto_only';
                    $interactive = false;
                    include __DIR__ . '/../cartes/partials/card_cr80.php';
                    ?>
                </div>
            </div>
            <!-- Page 2 : Verso -->
            <div class="single-card-print-page mt-3">
                <div class="no-print text-white-50 small mb-2">--- PAGE 2 (VERSO) ---</div>
                <div class="crop-mark-card d-inline-block">
                    <?php
                    $mode = 'verso_only';
                    $interactive = false;
                    include __DIR__ . '/../cartes/partials/card_cr80.php';
                    ?>
                </div>
            </div>
        <?php elseif ($mode === 'both'): ?>
            <div class="d-flex flex-wrap gap-4 justify-content-center align-items-center">
                <div class="crop-mark-card d-inline-block">
                    <?php
                    $mode = 'recto_only';
                    $interactive = false;
                    include __DIR__ . '/../cartes/partials/card_cr80.php';
                    ?>
                </div>
                <div class="crop-mark-card d-inline-block">
                    <?php
                    $mode = 'verso_only';
                    $interactive = false;
                    include __DIR__ . '/../cartes/partials/card_cr80.php';
                    ?>
                </div>
            </div>
        <?php elseif ($mode === 'recto_only'): ?>
            <div class="crop-mark-card d-inline-block">
                <?php
                $interactive = false;
                include __DIR__ . '/../cartes/partials/card_cr80.php';
                ?>
            </div>
        <?php elseif ($mode === 'verso_only'): ?>
            <div class="crop-mark-card d-inline-block">
                <?php
                $interactive = false;
                include __DIR__ . '/../cartes/partials/card_cr80.php';
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
