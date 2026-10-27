<?php
/**
 * Page publique de vérification d'une carte d'identité (Scan QR Code)
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="container py-4" style="max-width: 680px;">
    <!-- En-tête officiel République Gabonaise -->
    <div class="text-center mb-4">
        <div class="d-inline-flex gap-1 mb-2">
            <span style="width: 28px; height: 6px; background: #009e49; border-radius: 2px;"></span>
            <span style="width: 28px; height: 6px; background: #fcd116; border-radius: 2px;"></span>
            <span style="width: 28px; height: 6px; background: #0038a8; border-radius: 2px;"></span>
        </div>
        <h5 class="fw-bold text-white mb-0 text-uppercase tracking-wide">RÉPUBLIQUE GABONAISE</h5>
        <div class="small text-white-50">Système National de Vérification des Cartes d'Identité</div>
    </div>

    <?php if ($card): ?>
        <!-- Résultat de la vérification -->
        <div class="card bg-white shadow-lg border-0 rounded-4 overflow-hidden mb-4">
            <!-- Bannière de Statut -->
            <?php if ($isValid): ?>
                <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                    <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center shadow-sm mb-2" style="width: 64px; height: 64px; font-size: 2rem;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4 class="fw-bold mb-1">CARTE AUTHENTIQUE & VALIDE</h4>
                    <p class="mb-0 small text-white-50">Document officiel certifié conforme par l'établissement émetteur.</p>
                </div>
            <?php elseif ($isExpired): ?>
                <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                    <div class="rounded-circle bg-white text-warning d-inline-flex align-items-center justify-content-center shadow-sm mb-2" style="width: 64px; height: 64px; font-size: 2rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h4 class="fw-bold mb-1">CARTE OFFICIELLEMENT EXPIRÉE</h4>
                    <p class="mb-0 small text-white-50">La période de validité de cette carte est arrivée à échéance.</p>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                    <div class="rounded-circle bg-white text-danger d-inline-flex align-items-center justify-content-center shadow-sm mb-2" style="width: 64px; height: 64px; font-size: 2rem;">
                        <i class="bi bi-slash-circle"></i>
                    </div>
                    <h4 class="fw-bold mb-1">CARTE DÉSACTIVÉE / NON VALIDE</h4>
                    <p class="mb-0 small text-white-50">Cette carte a été signalée perdue, volée ou annulée administrativement.</p>
                </div>
            <?php endif; ?>

            <!-- Détails du Bénéficiaire -->
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <?php if (!empty($card['beneficiaire']['photo'])): ?>
                        <img src="<?= htmlspecialchars(asset_url($card['beneficiaire']['photo'])) ?>" alt="Photo" class="rounded-4 object-fit-cover shadow-sm border" style="width: 80px; height: 104px;">
                    <?php else: ?>
                        <div class="rounded-4 bg-light text-secondary border d-flex flex-column align-items-center justify-content-center" style="width: 80px; height: 104px;">
                            <i class="bi bi-person-fill display-5"></i>
                        </div>
                    <?php endif; ?>

                    <div>
                        <span class="badge bg-light text-dark border mb-1">
                            <?= ucfirst(htmlspecialchars($card['beneficiaire_type'])) ?>
                        </span>
                        <h4 class="fw-bold mb-1 text-dark">
                            <?= htmlspecialchars(strtoupper($card['beneficiaire']['nom'] ?? '')) ?> <?= htmlspecialchars($card['beneficiaire']['prenom'] ?? '') ?>
                        </h4>
                        <div class="font-monospace text-primary fw-bold">
                            <?= htmlspecialchars($card['beneficiaire']['matricule'] ?? '') ?>
                        </div>
                        <div class="text-muted small">
                            <?= htmlspecialchars($card['beneficiaire']['groupe_label'] ?? '') ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3 small mb-3">
                    <div class="col-6">
                        <span class="text-muted d-block">Établissement</span>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <?php if (!empty($card['etablissement_logo'])): ?>
                                <img src="<?= htmlspecialchars(asset_url($card['etablissement_logo'])) ?>" alt="Logo" class="rounded border p-1 bg-white" style="max-height: 28px; max-width: 44px; object-fit: contain;">
                            <?php endif; ?>
                            <strong class="text-dark"><?= htmlspecialchars($card['etablissement_nom']) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Numéro de carte</span>
                        <strong class="font-monospace text-primary"><?= htmlspecialchars($card['numero_carte']) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Date d'émission</span>
                        <strong class="text-dark"><?= date('d/m/Y', strtotime($card['date_emission'])) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Date d'expiration</span>
                        <strong class="<?= $isExpired ? 'text-danger' : 'text-dark' ?>"><?= date('d/m/Y', strtotime($card['date_expiration'])) ?></strong>
                    </div>
                </div>

                <!-- Cachet numérique d'intégrité -->
                <div class="p-3 bg-light rounded-3 border text-center small text-muted">
                    <i class="bi bi-fingerprint text-success me-1"></i>
                    Contrôle effectué le <strong><?= date('d/m/Y à H:i:s') ?></strong> • Jeton : <span class="font-monospace text-dark"><?= substr($card['jeton_verification'], 0, 16) ?>...</span>
                </div>
            </div>
        </div>

    <?php elseif ($jeton !== ''): ?>
        <!-- Carte Introuvable / Invalide -->
        <div class="card bg-white shadow-lg border-0 rounded-4 p-5 text-center mb-4">
            <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2.2rem;">
                <i class="bi bi-x-octagon"></i>
            </div>
            <h4 class="fw-bold text-danger mb-2">DOCUMENT NON RÉPERTORIÉ</h4>
            <p class="text-secondary mb-3">
                Aucune carte d'identité ne correspond au code <strong class="font-monospace"><?= htmlspecialchars($jeton) ?></strong> dans le registre central.
            </p>
            <div class="alert alert-warning small text-start border-0">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Attention : Ce document peut être contrefait ou le jeton de sécurité est incorrect.
            </div>
        </div>
    <?php endif; ?>

    <!-- Recherche Manuelle de Carte -->
    <div class="card bg-white shadow border-0 rounded-4 p-4 text-center">
        <h6 class="fw-bold mb-2">Vérifier une autre carte</h6>
        <p class="text-muted small mb-3">Saisissez le numéro de carte (ex: C-2026-LNLM-ELV-00001) ou le jeton figurant au verso.</p>
        <form method="GET" action="<?= $base_path ?>/verifier" class="d-flex gap-2">
            <input type="text" name="jeton" class="form-control" placeholder="Numéro de carte ou jeton..." required>
            <button type="submit" class="btn btn-primary px-4 fw-bold">Vérifier</button>
        </form>
    </div>
</div>
