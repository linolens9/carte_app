<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etab */
/** @var array $errors */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modifier l'Établissement</h2>
        <p class="text-muted small mb-0"><?= htmlspecialchars($etab['nom']) ?> (<code><?= htmlspecialchars($etab['code']) ?></code>)</p>
    </div>
    <a href="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la fiche
    </a>
</div>

<form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/modifier" method="POST" enctype="multipart/form-data">
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
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($etab['nom']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Code unique <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase" value="<?= htmlspecialchars($etab['code']) ?>" maxlength="20" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" class="form-select" required>
                                <option value="scolaire" <?= $etab['categorie'] === 'scolaire' ? 'selected' : '' ?>>Scolaire & Académique</option>
                                <option value="professionnel" <?= $etab['categorie'] === 'professionnel' ? 'selected' : '' ?>>Professionnel & Entreprise</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Type précis <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="primaire" <?= $etab['type'] === 'primaire' ? 'selected' : '' ?>>École primaire</option>
                                <option value="college" <?= $etab['type'] === 'college' ? 'selected' : '' ?>>Collège</option>
                                <option value="lycee" <?= $etab['type'] === 'lycee' ? 'selected' : '' ?>>Lycée</option>
                                <option value="universite" <?= $etab['type'] === 'universite' ? 'selected' : '' ?>>Université</option>
                                <option value="centre_formation" <?= $etab['type'] === 'centre_formation' ? 'selected' : '' ?>>Centre de formation</option>
                                <option value="entreprise" <?= $etab['type'] === 'entreprise' ? 'selected' : '' ?>>Entreprise</option>
                                <option value="administration" <?= $etab['type'] === 'administration' ? 'selected' : '' ?>>Administration</option>
                                <option value="association" <?= $etab['type'] === 'association' ? 'selected' : '' ?>>Association</option>
                                <option value="ong" <?= $etab['type'] === 'ong' ? 'selected' : '' ?>>ONG</option>
                                <option value="etablissement_prive" <?= $etab['type'] === 'etablissement_prive' ? 'selected' : '' ?>>Établissement privé</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Statut <span class="text-danger">*</span></label>
                            <select name="statut" class="form-select" required>
                                <option value="actif" <?= $etab['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="suspendu" <?= $etab['statut'] === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                                <option value="archive" <?= $etab['statut'] === 'archive' ? 'selected' : '' ?>>Archivé</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Devise ou Slogan</label>
                            <input type="text" name="devise" class="form-control" value="<?= htmlspecialchars($etab['devise'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($etab['telephone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">E-mail de contact</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($etab['email'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($etab['ville'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Pays</label>
                            <input type="text" name="pays" class="form-control" value="<?= htmlspecialchars($etab['pays'] ?? '') ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Adresse physique</label>
                            <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($etab['adresse'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Site Web</label>
                            <input type="url" name="site_web" class="form-control" value="<?= htmlspecialchars($etab['site_web'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Année scolaire / Exercice</label>
                            <input type="text" name="annee_active" class="form-control" value="<?= htmlspecialchars($etab['annee_active'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identité visuelle -->
        <div class="col-lg-4">
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0">2. Identité Visuelle</h6>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Couleur principale</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" name="couleur_principale" class="form-control form-control-color" value="<?= htmlspecialchars($etab['couleur_principale'] ?? '#2563eb') ?>">
                            <span class="text-muted small font-monospace"><?= htmlspecialchars($etab['couleur_principale'] ?? '#2563eb') ?></span>
                        </div>
                    </div>

                    <!-- Logo actuel et remplacement -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Logo</label>
                        <?php if (!empty($etab['logo'])): ?>
                            <div class="mb-2 p-2 border rounded bg-white text-center">
                                <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['logo']) ?>" alt="Logo actuel" style="max-height: 70px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/svg+xml">
                        <div class="form-text small">Laisser vide pour conserver le logo existant.</div>
                    </div>

                    <!-- Cachet actuel et remplacement -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Cachet / Tampon</label>
                        <?php if (!empty($etab['cachet'])): ?>
                            <div class="mb-2 p-2 border rounded bg-white text-center">
                                <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['cachet']) ?>" alt="Cachet actuel" style="max-height: 70px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="cachet" class="form-control" accept="image/png,image/jpeg">
                        <div class="form-text small">Laisser vide pour conserver le cachet existant.</div>
                    </div>

                    <div class="d-grid gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
