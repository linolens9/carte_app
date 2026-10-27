<?php
/**
 * Formulaire de création d'un modèle de carte
 */
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $base_path ?>/templates" class="btn btn-light border rounded-circle">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-1">Nouveau modèle de carte</h4>
        <p class="text-secondary mb-0">Définissez l'apparence graphique et les éléments de sécurité au format CR-80.</p>
    </div>
</div>

<form method="POST" action="<?= $base_path ?>/templates/creer">
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
                    <input type="text" name="nom" class="form-control" placeholder="ex: Carte Scolaire Officielle 2026" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Public cible <span class="text-danger">*</span></label>
                        <select name="cible" class="form-select" required>
                            <option value="eleve" <?= ($default_cible === 'eleve') ? 'selected' : '' ?>>Élève (Scolaire)</option>
                            <option value="etudiant" <?= ($default_cible === 'etudiant') ? 'selected' : '' ?>>Étudiant (Universitaire)</option>
                            <option value="personnel" <?= ($default_cible === 'personnel') ? 'selected' : '' ?>>Personnel & Agent</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Orientation <span class="text-danger">*</span></label>
                        <select name="orientation" class="form-select" required>
                            <option value="horizontal" selected>Horizontale (Paysage - 85.6 × 54 mm)</option>
                            <option value="vertical">Verticale (Portrait - 54 × 85.6 mm)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Établissement rattaché</label>
                    <select name="etablissement_id" class="form-select">
                        <option value="">Tous les établissements (Modèle Global)</option>
                        <?php foreach ($etablissements as $etab): ?>
                            <option value="<?= $etab['id'] ?>" <?= ($selected_etab_id == $etab['id']) ? 'selected' : '' ?>>
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
                            <input type="color" name="couleur_principale" class="form-control form-control-color" value="#1e3a8a" id="colorPicker1">
                            <input type="text" class="form-control font-monospace" value="#1e3a8a" id="colorText1" placeholder="#1e3a8a">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Couleur secondaire</label>
                        <div class="input-group">
                            <input type="color" name="couleur_secondaire" class="form-control form-control-color" value="#3b82f6" id="colorPicker2">
                            <input type="text" class="form-control font-monospace" value="#3b82f6" id="colorText2" placeholder="#3b82f6">
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="actif" value="1" id="switchActif" checked>
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
                        <input type="text" name="titre_haut" class="form-control" value="RÉPUBLIQUE GABONAISE">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Devise / Sous-titre</label>
                        <input type="text" name="sous_titre_haut" class="form-control" value="Union - Travail - Justice">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Titre de la carte</label>
                    <input type="text" name="titre_carte" class="form-control" value="CARTE D'IDENTITÉ SCOLAIRE">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sous-titre / Année de référence</label>
                    <input type="text" name="mention_annee" class="form-control" value="Année Scolaire 2025-2026">
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_logo" value="1" id="chkLogo" checked>
                            <label class="form-check-label small" for="chkLogo">Logo établissement</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_armoiries" value="1" id="chkArmoiries" checked>
                            <label class="form-check-label small" for="chkArmoiries">Armoiries nationales</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_photo" value="1" id="chkPhoto" checked>
                            <label class="form-check-label small" for="chkPhoto">Cadre photo 3:4</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_barcode" value="1" id="chkBar" checked>
                            <label class="form-check-label small" for="chkBar">Code-barres Code 128</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_puce" value="1" id="chkPuce" checked>
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
                    <input type="text" name="titre_verso" class="form-control" value="CONDITIONS D'UTILISATION">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Règlement & Instructions</label>
                    <textarea name="texte_reglement" class="form-control" rows="4">Cette carte est strictement personnelle.
En cas de perte, merci de la rapporter immédiatement à l'administration de l'établissement.</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Titre du signataire</label>
                    <input type="text" name="titre_signature" class="form-control" value="Le Chef d'Établissement">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mention de retour / Contact en cas de perte</label>
                    <input type="text" name="mention_retour" class="form-control" value="Contact administratif : Libreville, Gabon">
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_qr" value="1" id="chkQr" checked>
                            <label class="form-check-label small" for="chkQr">QR Code de vérification publique</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="afficher_signature" value="1" id="chkSig" checked>
                            <label class="form-check-label small" for="chkSig">Cachet & Signature officielle</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= $base_path ?>/templates" class="btn btn-light border px-4">Annuler</a>
                <button type="submit" class="btn btn-primary-custom px-4">
                    <i class="bi bi-plus-lg me-1"></i> Créer le modèle
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
