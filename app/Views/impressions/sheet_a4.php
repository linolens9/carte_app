<?php
/**
 * Planche d'impression A4 standardisée (CR-80 avec calage Recto/Verso et traits de coupe)
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
$chunks = array_chunk($cartes, 8); // 8 cartes max par page A4 (2x4)
?>

<div class="container-fluid py-3 no-print">
    <div class="card-custom p-3 mx-auto text-center mb-4" style="max-width: 800px;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="text-start">
                <h6 class="fw-bold mb-0">Planche d'impression A4 - <?= count($cartes) ?> carte(s)</h6>
                <div class="small text-muted">Format A4 Portrait • 8 cartes par feuille • Traits de coupe pour massicot</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm">
                    <a href="?ids=<?= implode(',', array_column($cartes, 'id')) ?>&face=duplex" class="btn <?= ($face === 'duplex') ? 'btn-primary' : 'btn-outline-secondary' ?>">Recto-Verso Calé</a>
                    <a href="?ids=<?= implode(',', array_column($cartes, 'id')) ?>&face=recto" class="btn <?= ($face === 'recto') ? 'btn-primary' : 'btn-outline-secondary' ?>">Recto seul</a>
                    <a href="?ids=<?= implode(',', array_column($cartes, 'id')) ?>&face=verso" class="btn <?= ($face === 'verso') ? 'btn-primary' : 'btn-outline-secondary' ?>">Verso seul</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sheets-container text-center">
    <?php foreach ($chunks as $pageIndex => $pageCards): ?>
        <?php if ($face === 'duplex' || $face === 'recto'): ?>
            <!-- PAGE RECTO -->
            <div class="a4-sheet-wrapper my-4">
                <div class="no-print text-white-50 small mb-2">--- FEUILLE <?= ($pageIndex * 2) + 1 ?> (RECTO) ---</div>
                <div class="a4-print-sheet bg-white p-3 shadow-lg mx-auto" style="width: 210mm; min-height: 297mm;">
                    <?php foreach ($pageCards as $c): ?>
                        <div class="crop-mark-card d-inline-block">
                            <?php
                            $card = $c;
                            $mode = 'recto_only';
                            $interactive = false;
                            include __DIR__ . '/../cartes/partials/card_cr80.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($face === 'duplex' || $face === 'verso'): ?>
            <!-- PAGE VERSO (Avec inversion miroir des colonnes pour calage duplex) -->
            <?php
            // Pour le calage duplex en grille 2 colonnes : [1, 2] devient [2, 1]
            $versoCards = [];
            $pairs = array_chunk($pageCards, 2);
            foreach ($pairs as $pair) {
                if (count($pair) === 2) {
                    $versoCards[] = $pair[1];
                    $versoCards[] = $pair[0];
                } else {
                    // Si carte impaire isolée, la placer en colonne droite
                    $versoCards[] = null;
                    $versoCards[] = $pair[0];
                }
            }
            ?>
            <div class="a4-sheet-wrapper my-4">
                <div class="no-print text-white-50 small mb-2">--- FEUILLE <?= ($pageIndex * 2) + 2 ?> (VERSO - RETOURNEMENT BORD LONG) ---</div>
                <div class="a4-print-sheet bg-white p-3 shadow-lg mx-auto" style="width: 210mm; min-height: 297mm;">
                    <?php foreach ($versoCards as $c): ?>
                        <?php if ($c !== null): ?>
                            <div class="crop-mark-card d-inline-block">
                                <?php
                                $card = $c;
                                $mode = 'verso_only';
                                $interactive = false;
                                include __DIR__ . '/../cartes/partials/card_cr80.php';
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="d-inline-block" style="width: 85.6mm; height: 53.98mm;"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
