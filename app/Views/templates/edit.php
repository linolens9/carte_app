<?php
/**
 * Formulaire de modification d'un modèle de carte
 */
$r = $template['config_recto'] ?? [];
$v = $template['config_verso'] ?? [];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $base_path ?>/templates/<?= $template['id'] ?>" class="btn btn-light border rounded-circle">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-1">Modifier le modèle : <?= htmlspecialchars($template['nom']) ?></h4>
        <p class="text-secondary mb-0">Mettez à jour la charte graphique et la disposition des éléments.</p>
    </div>
</div>

<form method="POST" action="<?= $base_path ?>/templates/<?= $template['id'] ?>/modifier">
    <?= \App\Core\Csrf::inputField() ?>

    <div class="row g-4">
        <!-- Informations Générales & Couleurs -->
        <div class="col-lg-6">
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-gear me-1 text-primary"></i> Généralités
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom du modèle <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($template['nom']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Public cible <span class="text-danger">*</span></label>
                        <select name="cible" class="form-select" required>
                            <option value="eleve" <?= ($template['cible'] === 'eleve') ? 'selected' : '' ?>>Élève (Scolaire)</option>
                            <option value="etudiant" <?= ($template['cible'] === 'etudiant') ? 'selected' : '' ?>>Étudiant (Universitaire)</option>
                            <option value="personnel" <?= ($template['cible'] === 'personnel') ? 'selected' : '' ?>>Personnel & Agent</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Orientation <span class="text-danger">*</span></label>
                        <select name="orientation" class="form-select" required>
                            <option value="horizontal" <?= (($template['orientation'] ?? 'horizontal') === 'horizontal') ? 'selected' : '' ?>>Horizontale (Paysage - 85.6 × 54 mm)</option>
                            <option value="vertical" <?= (($template['orientation'] ?? '') === 'vertical') ? 'selected' : '' ?>>Verticale (Portrait - 54 × 85.6 mm)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Établissement rattaché</label>
                    <select name="etablissement_id" class="form-select">
                        <option value="">Tous les établissements (Modèle Global)</option>
                        <?php foreach ($etablissements as $etab): ?>
                            <option value="<?= $etab['id'] ?>" <?= ($template['etablissement_id'] == $etab['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">Laissez vide si ce modèle s'applique à tous les établissements.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Couleur principale</label>
                        <div class="input-group">
                            <input type="color" name="couleur_principale" class="form-control form-control-color" value="<?= htmlspecialchars($template['couleur_principale'] ?? '#1e3a8a') ?>" id="colorPicker1">
                            <input type="text" class="form-control font-monospace" value="<?= htmlspecialchars($template['couleur_principale'] ?? '#1e3a8a') ?>" id="colorText1" placeholder="#1e3a8a">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Couleur secondaire</label>
                        <div class="input-group">
                            <input type="color" name="couleur_secondaire" class="form-control form-control-color" value="<?= htmlspecialchars($template['couleur_secondaire'] ?? '#3b82f6') ?>" id="colorPicker2">
                            <input type="text" class="form-control font-monospace" value="<?= htmlspecialchars($template['couleur_secondaire'] ?? '#3b82f6') ?>" id="colorText2" placeholder="#3b82f6">
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="actif" value="1" id="switchActif" <?= !empty($template['actif']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="switchActif">Modèle actif et sélectionnable lors de la génération</label>
                </div>
            </div>

            <!-- Configuration Recto -->
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-card-heading me-1 text-primary"></i> Éléments du Recto
                </h6>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">En-tête officiel</label>
                        <input type="text" name="titre_haut" class="form-control" value="<?= htmlspecialchars($r['titre_haut'] ?? 'RÉPUBLIQUE GABONAISE') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Devise / Sous-titre</label>
                        <input type="text" name="sous_titre_haut" class="form-control" value="<?= htmlspecialchars($r['sous_titre_haut'] ?? 'Union - Travail - Justice') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Titre de la carte</label>
                    <input type="text" name="titre_carte" class="form-control" value="<?= htmlspecialchars($r['titre_carte'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sous-titre / Année de référence</label>
                    <input type="text" name="mention_annee" class="form-control" value="<?= htmlspecialchars($r['mention_annee'] ?? '') ?>">
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_logo" value="1" id="chkLogo" <?= !empty($r['afficher_logo']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkLogo">Logo établissement</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_armoiries" value="1" id="chkArmoiries" <?= !empty($r['afficher_armoiries']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkArmoiries">Armoiries nationales</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_photo" value="1" id="chkPhoto" <?= !empty($r['afficher_photo']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkPhoto">Cadre photo 3:4</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_barcode" value="1" id="chkBar" <?= !empty($r['afficher_barcode']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkBar">Code-barres Code 128</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_puce" value="1" id="chkPuce" <?= !empty($r['afficher_puce']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkPuce">Puce sécurisée EMV</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Verso -->
        <div class="col-lg-6">
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-qr-code me-1 text-primary"></i> Éléments du Verso
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Titre verso</label>
                    <input type="text" name="titre_verso" class="form-control" value="<?= htmlspecialchars($v['titre'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Règlement & Instructions</label>
                    <textarea name="texte_reglement" class="form-control" rows="4"><?= htmlspecialchars($v['texte_reglement'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Titre du signataire</label>
                    <input type="text" name="titre_signature" class="form-control" value="<?= htmlspecialchars($v['titre_signature'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mention de retour / Contact en cas de perte</label>
                    <input type="text" name="mention_retour" class="form-control" value="<?= htmlspecialchars($v['mention_retour'] ?? '') ?>">
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_qr" value="1" id="chkQr" <?= !empty($v['afficher_qr']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkQr">QR Code de vérification publique</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_signature" value="1" id="chkSig" <?= !empty($v['afficher_signature']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="chkSig">Cachet & Signature officielle</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= $base_path ?>/templates/<?= $template['id'] ?>" class="btn btn-light border px-4">Annuler</a>
                <button type="submit" class="btn btn-primary-custom px-4">
                    <i class="bi bi-check-lg me-1"></i> Mettre à jour
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    const cp1 = document.getElementById('colorPicker1');
    const ct1 = document.getElementById('colorText1');
    const cp2 = document.getElementById('colorPicker2');
    const ct2 = document.getElementById('colorText2');

    if (cp1 && ct1) {
        cp1.addEventListener('input', (e) => { ct1.value = e.target.value; });
        ct1.addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value.trim())) {
                cp1.value = e.target.value.trim();
            }
        });
    }

    if (cp2 && ct2) {
        cp2.addEventListener('input', (e) => { ct2.value = e.target.value; });
        ct2.addEventListener('input', (e) => {
            if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value.trim())) {
                cp2.value = e.target.value.trim();
            }
        });
    }
</script>
