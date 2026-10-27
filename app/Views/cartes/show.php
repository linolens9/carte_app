<?php
/**
 * Vue détaillée d'une carte d'identité : Rendu 3D, Cycle de vie, Historique
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
$b = $card['beneficiaire'] ?? [];
$st = $card['statut'];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= $base_path ?>/cartes" class="btn btn-light border rounded-circle">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0"><?= htmlspecialchars($card['numero_carte']) ?></h4>
                <?php
                $badgeClass = match ($st) {
                    'brouillon' => 'bg-secondary',
                    'validee' => 'bg-warning text-dark',
                    'imprimee' => 'bg-info text-dark',
                    'active' => 'bg-success',
                    'expiree' => 'bg-warning',
                    'desactivee' => 'bg-danger',
                    'archivee' => 'bg-dark',
                    default => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $badgeClass ?> px-3 py-1 fs-6">
                    <?= ucfirst(htmlspecialchars($st)) ?>
                </span>
            </div>
            <p class="text-secondary mb-0 small">
                Émise le <?= date('d/m/Y', strtotime($card['date_emission'])) ?> • Expire le <?= date('d/m/Y', strtotime($card['date_expiration'])) ?>
            </p>
        </div>
    </div>

    <!-- Actions du cycle de vie -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= $base_path ?>/impressions/carte/<?= $card['id'] ?>" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-printer"></i>
            <span>Imprimer la carte</span>
        </a>

        <a href="<?= $base_path ?>/verifier/<?= $card['jeton_verification'] ?>" target="_blank" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-qr-code-scan"></i>
            <span>Vérification QR</span>
        </a>

        <?php if ($st === 'brouillon'): ?>
            <a href="<?= $base_path ?>/cartes/<?= $card['id'] ?>/valider" class="btn btn-success d-flex align-items-center gap-2">
                <i class="bi bi-check2-circle"></i>
                <span>Valider la carte</span>
            </a>
        <?php endif; ?>

        <?php if ($st !== 'active'): ?>
            <a href="<?= $base_path ?>/cartes/<?= $card['id'] ?>/activer" class="btn btn-outline-success d-flex align-items-center gap-2">
                <i class="bi bi-hand-thumbs-up"></i>
                <span>Remise / Activer</span>
            </a>
        <?php endif; ?>

        <a href="<?= $base_path ?>/cartes/<?= $card['id'] ?>/renouveler" class="btn btn-outline-warning text-dark d-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat"></i>
            <span>Renouveler</span>
        </a>

        <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalDesactiver">
            <i class="bi bi-slash-circle"></i>
            <span>Désactiver</span>
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne Gauche : Rendu 3D CR-80 -->
    <div class="col-lg-5">
        <div class="card-custom p-4 text-center mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-light text-dark border">Format CR-80 Standardisé</span>
                <span class="small text-muted">Cliquez pour retourner</span>
            </div>

            <div class="py-4 bg-light rounded-4 border d-flex flex-column align-items-center justify-content-center">
                <?php
                $interactive = true;
                $mode = 'flip';
                include __DIR__ . '/partials/card_cr80.php';
                ?>
            </div>

            <div class="d-flex justify-content-around mt-3 pt-3 border-top text-muted small">
                <div>
                    <i class="bi bi-fingerprint text-primary me-1"></i>
                    Format 85.6 × 53.98 mm
                </div>
                <div>
                    <i class="bi bi-upc-scan text-info me-1"></i>
                    Code 128 Vectoriel
                </div>
                <div>
                    <i class="bi bi-qr-code text-success me-1"></i>
                    QR Sécurisé
                </div>
            </div>
        </div>

        <!-- Informations Sécurité & Jeton -->
        <div class="card-custom p-3 mb-4">
            <h6 class="fw-bold mb-2 small text-uppercase text-secondary">Jeton de vérification unique</h6>
            <div class="p-2 bg-light rounded font-monospace small text-break border text-primary fw-bold">
                <?= htmlspecialchars($card['jeton_verification']) ?>
            </div>
            <div class="small text-muted mt-2">
                <i class="bi bi-shield-lock me-1 text-success"></i> Ce jeton cryptographique certifie l'authenticité de la carte lors du scan public.
            </div>
        </div>
    </div>

    <!-- Colonne Droite : Infos Bénéficiaire, Détails Techniques & Historique -->
    <div class="col-lg-7">
        <!-- Carte Profil Bénéficiaire -->
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-person-badge me-1 text-primary"></i> Bénéficiaire Titulaire
            </h6>
            <div class="row g-3">
                <div class="col-sm-6">
                    <span class="text-muted small d-block">Nom & Prénom</span>
                    <strong class="fs-6"><?= htmlspecialchars(($b['nom'] ?? '') . ' ' . ($b['prenom'] ?? '')) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small d-block"><?= htmlspecialchars($b['matricule_label'] ?? 'Matricule') ?></span>
                    <strong class="font-monospace text-primary"><?= htmlspecialchars($b['matricule'] ?? 'N/A') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small d-block">Groupe / Affectation</span>
                    <strong><?= htmlspecialchars($b['groupe_label'] ?? 'N/A') ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small d-block">Établissement</span>
                    <strong><?= htmlspecialchars($card['etablissement_nom']) ?> (<?= htmlspecialchars($card['etablissement_code']) ?>)</strong>
                </div>
                <?php if (!empty($b['date_naissance'])): ?>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Date de naissance</span>
                        <strong><?= date('d/m/Y', strtotime($b['date_naissance'])) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="col-sm-6">
                    <span class="text-muted small d-block">Modèle graphique appliqué</span>
                    <a href="<?= $base_path ?>/templates/<?= $card['template_id'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($card['template_nom']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Historique des Impressions -->
        <?php if (!empty($impressions)): ?>
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-printer me-1 text-primary"></i> Historique des Impressions (<?= count($impressions) ?>)
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-custom small">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Opérateur</th>
                                <th>Type</th>
                                <th>Exemplaires</th>
                                <th>Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($impressions as $imp): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($imp['imprime_le'])) ?></td>
                                    <td><?= htmlspecialchars(($imp['user_prenom'] ?? '') . ' ' . ($imp['user_nom'] ?? '')) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst(htmlspecialchars($imp['type'])) ?></span></td>
                                    <td><?= (int)$imp['nb_exemplaires'] ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($imp['motif'] ?? ($imp['est_reimpression'] ? 'Réimpression' : 'Initiale')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Journal du cycle de vie (Historique) -->
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-clock-history me-1 text-primary"></i> Journal du Cycle de Vie
            </h6>
            <?php if (empty($historique)): ?>
                <p class="text-muted small mb-0">Aucun historique enregistré pour le moment.</p>
            <?php else: ?>
                <div class="timeline small">
                    <?php foreach ($historique as $h): ?>
                        <div class="d-flex gap-3 mb-3 border-bottom pb-2">
                            <div class="text-muted" style="min-width: 110px;">
                                <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                            </div>
                            <div class="flex-grow-1">
                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($h['evenement']) ?></span>
                                <span class="text-dark fw-semibold"><?= htmlspecialchars($h['commentaire'] ?? '') ?></span>
                                <?php if ($h['user_nom']): ?>
                                    <span class="text-muted">par <?= htmlspecialchars($h['user_prenom'] . ' ' . $h['user_nom']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Désactiver -->
<div class="modal fade" id="modalDesactiver" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= $base_path ?>/cartes/<?= $card['id'] ?>/desactiver">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Désactiver la carte</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Motif de la désactivation</label>
                        <select name="motif" class="form-select" required>
                            <option value="Perte signalée par le titulaire">Perte signalée</option>
                            <option value="Vol de la carte">Vol de la carte</option>
                            <option value="Détérioration physique de la carte">Détérioration physique</option>
                            <option value="Départ / Exclusion / Fin de contrat">Départ / Fin de contrat</option>
                            <option value="Erreur de saisie / Annulation">Erreur administrative</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Confirmer la désactivation</button>
                </div>
            </form>
        </div>
    </div>
</div>
