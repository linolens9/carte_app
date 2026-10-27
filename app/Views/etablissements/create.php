<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $errors */
/** @var array $old */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Créer un Établissement</h2>
        <p class="text-muted small mb-0">Renseignez les détails de la structure, son identité visuelle et son type</p>
    </div>
    <a href="<?= $base_path ?>/etablissements" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<form action="<?= $base_path ?>/etablissements/creer" method="POST" enctype="multipart/form-data">
    <?= $csrf_input ?>

    <div class="row g-4">
        <!-- Informations générales -->
        <div class="col-lg-8">
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0">1. Informations Générales</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nom officiel de l'établissement <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Ex: Lycée National Léon Mba" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Code unique <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase <?= !empty($errors['code']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['code'] ?? '') ?>" placeholder="Ex: LNLM" maxlength="20" required>
                            <div class="form-text small">Utilisé pour la numérotation des cartes (ex: LNLM-2026-00001).</div>
                            <?php if (!empty($errors['code'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['code'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" id="categorieSelect" class="form-select" required>
                                <option value="scolaire" <?= ($old['categorie'] ?? '') === 'scolaire' ? 'selected' : '' ?>>Scolaire & Académique</option>
                                <option value="professionnel" <?= ($old['categorie'] ?? '') === 'professionnel' ? 'selected' : '' ?>>Professionnel & Entreprise</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Type précis <span class="text-danger">*</span></label>
                            <select name="type" id="typeSelect" class="form-select" required>
                                <optgroup label="Scolaire" id="optgroupScolaire">
                                    <option value="primaire">École primaire</option>
                                    <option value="college">Collège</option>
                                    <option value="lycee" selected>Lycée</option>
                                    <option value="universite">Université</option>
                                    <option value="centre_formation">Centre de formation</option>
                                </optgroup>
                                <optgroup label="Professionnel" id="optgroupPro">
                                    <option value="entreprise">Entreprise</option>
                                    <option value="administration">Administration / Ministère</option>
                                    <option value="association">Association</option>
                                    <option value="ong">ONG</option>
                                    <option value="etablissement_prive">Établissement privé</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Devise ou Slogan</label>
                            <input type="text" name="devise" class="form-control"
                                   value="<?= htmlspecialchars($old['devise'] ?? '') ?>" placeholder="Ex: Travail - Justice - Solidarité">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+241 01 23 45 67">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">E-mail de contact</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="contact@etablissement.ga">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Ville</label>
                            <input type="text" name="ville" class="form-control"
                                   value="<?= htmlspecialchars($old['ville'] ?? '') ?>" placeholder="Libreville">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Pays</label>
                            <input type="text" name="pays" class="form-control"
                                   value="<?= htmlspecialchars($old['pays'] ?? 'Gabon') ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Adresse physique</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($old['adresse'] ?? '') ?>" placeholder="Quartier, Rue, B.P.">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Site Web</label>
                            <input type="url" name="site_web" class="form-control"
                                   value="<?= htmlspecialchars($old['site_web'] ?? '') ?>" placeholder="https://www.etablissement.ga">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Année scolaire / Exercice en cours</label>
                            <input type="text" name="annee_active" class="form-control"
                                   value="<?= htmlspecialchars($old['annee_active'] ?? date('Y') . '-' . (date('Y') + 1)) ?>" placeholder="2026-2027">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identité visuelle & Paramètres -->
        <div class="col-lg-4">
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0">2. Identité Visuelle</h6>
                </div>
                <div class="p-4">
                    <!-- Couleur principale -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Couleur principale de la charte</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" name="couleur_principale" id="colorPicker"
                                   class="form-control form-control-color" value="<?= htmlspecialchars($old['couleur_principale'] ?? '#2563eb') ?>">
                            <span class="text-muted small font-monospace" id="colorHex"><?= htmlspecialchars($old['couleur_principale'] ?? '#2563eb') ?></span>
                        </div>
                        <div class="form-text small">Sert de couleur d'accentuation sur les cartes.</div>
                    </div>

                    <!-- Logo -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Logo officiel (PNG, SVG, JPG)</label>
                        <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/svg+xml">
                        <div class="form-text small">Fond transparent recommandé, max. 2 Mo.</div>
                    </div>

                    <!-- Cachet / Signature -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Cachet / Signature de direction</label>
                        <input type="file" name="cachet" class="form-control" accept="image/png,image/jpeg">
                        <div class="form-text small">Image détourée du tampon ou signature officielle.</div>
                    </div>

                    <div class="d-grid gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer l'établissement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const colorPicker = document.getElementById('colorPicker');
    const colorHex = document.getElementById('colorHex');
    if (colorPicker && colorHex) {
        colorPicker.addEventListener('input', (e) => {
            colorHex.textContent = e.target.value;
        });
    }
</script>
