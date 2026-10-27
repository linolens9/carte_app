<?php
/**
 * Tableau de bord du Module Impression & Lots
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Impression & Lots</h4>
        <p class="text-secondary mb-0">Imprimez des cartes uniques ou générez des planches A4 prêtes pour le massicot.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Cartes en attente d'impression -->
    <div class="col-lg-7">
        <div class="card-custom p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-hourglass-split text-warning me-1"></i> Cartes Validées (Prêtes pour impression)
                    </h6>
                    <span class="small text-muted"><?= count($cartesEnAttente) ?> carte(s) en attente</span>
                </div>
                <div>
                    <?php if (!empty($cartesEnAttente)): ?>
                        <button type="button" class="btn btn-sm btn-primary-custom" id="btnPrintWaiting">
                            <i class="bi bi-printer me-1"></i> Imprimer en planche A4
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <form id="waitingCardsForm" method="POST" action="<?= $base_path ?>/impressions/planche-a4">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-custom align-middle small">
                        <thead>
                            <tr>
                                <th width="35">
                                    <input type="checkbox" class="form-check-input" id="checkWaitingAll">
                                </th>
                                <th>Bénéficiaire</th>
                                <th>N° Carte</th>
                                <th>Type</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cartesEnAttente)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-circle text-success fs-3 d-block mb-1"></i>
                                        Toutes les cartes validées ont été imprimées !
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cartesEnAttente as $c): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="cartes_ids[]" value="<?= $c['id'] ?>" class="form-check-input w-chk" checked>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($c['beneficiaire_nom_complet'] ?? '') ?></strong>
                                            <div class="text-muted font-monospace small"><?= htmlspecialchars($c['beneficiaire_matricule'] ?? '') ?></div>
                                        </td>
                                        <td class="font-monospace text-primary fw-bold">
                                            <?= htmlspecialchars($c['numero_carte']) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $c['beneficiaire_type'] === 'eleve' ? 'bg-primary' : ($c['beneficiaire_type'] === 'etudiant' ? 'bg-success' : 'bg-warning text-dark') ?>">
                                                <?= ucfirst(htmlspecialchars($c['beneficiaire_type'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= $base_path ?>/impressions/carte/<?= $c['id'] ?>" class="btn btn-sm btn-light border" title="Imprimer carte unique">
                                                <i class="bi bi-printer text-primary"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- Guide & Spécifications Planches -->
    <div class="col-lg-5">
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-aspect-ratio text-primary me-1"></i> Spécifications Techniques d'Impression
            </h6>
            <div class="d-flex flex-column gap-3 small">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="fw-bold text-dark mb-1">
                        <i class="bi bi-credit-card-2-front text-primary me-1"></i> Imprimantes à badges PVC (Direct CR-80)
                    </div>
                    <div class="text-muted">
                        Compatible imprimantes Evolis (Primacy, Zenius), Zebra (ZC300), Magicard et Fargo. Calibré à 100% sans marges.
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="fw-bold text-dark mb-1">
                        <i class="bi bi-file-earmark-break text-success me-1"></i> Planches A4 pour massicot (8 à 10 cartes)
                    </div>
                    <div class="text-muted">
                        Grille 2×4 (8 cartes) avec traits de coupe aux 4 coins. Retournement bord long pour calage recto/verso parfait.
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="fw-bold text-dark mb-1">
                        <i class="bi bi-qr-code text-info me-1"></i> Résolution Vectorielle & Codes
                    </div>
                    <div class="text-muted">
                        Code 128 et QR Code en SVG vectoriel natif, garantissant une netteté absolue à 300 et 600 DPI.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des impressions récentes -->
    <div class="col-12">
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-clock-history text-primary me-1"></i> Dernières Impressions Réalisées
            </h6>
            <div class="table-responsive">
                <table class="table table-hover table-custom small align-middle">
                    <thead>
                        <tr>
                            <th>Date & Heure</th>
                            <th>N° Carte</th>
                            <th>Établissement</th>
                            <th>Opérateur</th>
                            <th>Type</th>
                            <th>Exemplaires</th>
                            <th>Nature</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentImpressions)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Aucune impression enregistrée dans l'historique.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentImpressions as $imp): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($imp['imprime_le'])) ?></td>
                                    <td>
                                        <a href="<?= $base_path ?>/cartes/<?= $imp['carte_id'] ?>" class="font-monospace fw-bold text-decoration-none">
                                            <?= htmlspecialchars($imp['numero_carte']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($imp['etablissement_nom']) ?></td>
                                    <td><?= htmlspecialchars(($imp['user_prenom'] ?? '') . ' ' . ($imp['user_nom'] ?? '')) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst(htmlspecialchars($imp['type'])) ?></span></td>
                                    <td class="fw-bold"><?= (int)$imp['nb_exemplaires'] ?></td>
                                    <td>
                                        <span class="badge <?= $imp['est_reimpression'] ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= $imp['est_reimpression'] ? 'Réimpression' : 'Initiale' ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($imp['motif'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const checkAll = document.getElementById('checkWaitingAll');
    const chks = document.querySelectorAll('.w-chk');
    const btnPrintWaiting = document.getElementById('btnPrintWaiting');
    const formWaiting = document.getElementById('waitingCardsForm');

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            chks.forEach(c => c.checked = checkAll.checked);
        });
    }

    if (btnPrintWaiting) {
        btnPrintWaiting.addEventListener('click', () => {
            formWaiting.submit();
        });
    }
</script>
