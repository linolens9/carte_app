<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $personnel */
/** @var array $departements */
/** @var array $etablissements */
/** @var array $errors */

$hasPhoto = !empty($personnel['photo']) && (file_exists(public_path($personnel['photo'])) || str_starts_with($personnel['photo'], 'uploads/'));
$photoSrc = $hasPhoto ? asset_url($personnel['photo']) : '';
?>

<!-- Cropper.js CSS -->
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modifier le Collaborateur</h2>
        <p class="text-muted small mb-0">Matricule : <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($personnel['numero_professionnel']) ?></span> &bull; <?= htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-eye me-1"></i>Voir la fiche & badge
        </a>
        <a href="<?= $base_path ?>/personnels" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
    </div>
</div>

<form action="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>/modifier" method="POST" enctype="multipart/form-data" id="formEditPersonnel">
    <?= $csrf_input ?>
    <input type="hidden" name="photo_base64" id="photoBase64Input" value="">

    <div class="row g-4">
        <!-- Colonne gauche : Identité, Affectation & Contact -->
        <div class="col-lg-8">
            <!-- 1. Identité -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>1. Identité du Collaborateur</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control text-uppercase <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($personnel['nom'] ?? '') ?>" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($personnel['prenom'] ?? '') ?>" required>
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Numéro Professionnel / Matricule</label>
                            <input type="text" class="form-control bg-light font-monospace fw-bold"
                                   value="<?= htmlspecialchars($personnel['numero_professionnel'] ?? '') ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Affectation, Poste & Organisation -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>2. Affectation, Poste & Statut</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Structure / Organisation</label>
                            <input type="text" class="form-control bg-light" 
                                   value="<?= htmlspecialchars($personnel['etablissement_nom'] ?? 'Structure') ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Direction / Département</label>
                            <select name="departement_id" class="form-select">
                                <option value="">-- Aucun département spécifique --</option>
                                <?php foreach ($departements as $dept): ?>
                                    <option value="<?= (int)$dept['id'] ?>" <?= ((int)($personnel['departement_id'] ?? 0) === (int)$dept['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Fonction officielle <span class="text-danger">*</span></label>
                            <input type="text" name="fonction" class="form-control <?= !empty($errors['fonction']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($personnel['fonction'] ?? '') ?>" required>
                            <?php if (!empty($errors['fonction'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['fonction'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Service / Pôle d'activité</label>
                            <input type="text" name="service" class="form-control"
                                   value="<?= htmlspecialchars($personnel['service'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date d'embauche</label>
                            <input type="date" name="date_embauche" class="form-control"
                                   value="<?= htmlspecialchars($personnel['date_embauche'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Statut professionnel <span class="text-danger">*</span></label>
                            <select name="statut" class="form-select" required>
                                <option value="en_poste" <?= ($personnel['statut'] ?? '') === 'en_poste' ? 'selected' : '' ?>>En poste (Actif)</option>
                                <option value="suspendu" <?= ($personnel['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu (Disponibilité / Congé long)</option>
                                <option value="parti" <?= ($personnel['statut'] ?? '') === 'parti' ? 'selected' : '' ?>>Parti (Démission / Retraite / Fin de contrat)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Coordonnées & Contact -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-telephone me-2 text-primary"></i>3. Coordonnées & Contact</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Adresse E-mail professionnelle</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($personnel['email'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Numéro de Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($personnel['telephone'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adresse de résidence</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($personnel['adresse'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Photo de badge -->
        <div class="col-lg-4">
            <div class="card-custom sticky-top" style="top: 20px;">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-camera me-2 text-primary"></i>Photo de Badge (3:4)</h6>
                </div>
                <div class="p-4 text-center">
                    <div class="position-relative mx-auto mb-3" style="width: 180px; height: 240px; border-radius: 12px; overflow: hidden; background: #e2e8f0; border: 2px dashed #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                        <img id="previewPhoto" src="<?= $photoSrc ?>" alt="Photo collaborateur" class="w-100 h-100 object-fit-cover <?= $hasPhoto ? '' : 'd-none' ?>">
                        <div id="placeholderPhoto" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted <?= $hasPhoto ? 'd-none' : '' ?>">
                            <i class="bi bi-person-bounding-box fs-1 mb-1"></i>
                            <span class="small">Aucune photo</span>
                        </div>
                        <span id="badgeCropped" class="badge bg-success position-absolute bottom-0 start-50 translate-middle-x mb-2 d-none">
                            <i class="bi bi-check2 me-1"></i>Prête (600x800)
                        </span>
                    </div>

                    <p class="text-muted small mb-3">
                        Format officiel badge professionnel (ratio 3:4, min 600x800px).
                    </p>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success rounded-pill btn-sm fw-semibold" id="btnOpenSmartphone">
                            <i class="bi bi-phone me-1"></i>Filmer via Smartphone (USB)
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill btn-sm" id="btnOpenWebcam">
                            <i class="bi bi-webcam me-1"></i>Prendre via Webcam
                        </button>
                        
                        <label class="btn btn-outline-primary rounded-pill btn-sm mb-0">
                            <i class="bi bi-upload me-1"></i>Choisir un fichier
                            <input type="file" name="photo_file" id="fileInputPhoto" accept="image/jpeg,image/png,image/webp" class="d-none">
                        </label>

                        <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm d-none" id="btnResetPhoto">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser la photo
                        </button>
                    </div>

                    <?php if (!empty($errors['photo'])): ?>
                        <div class="text-danger small mt-2"><?= htmlspecialchars($errors['photo'][0]) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-custom p-4 mt-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer les modifications
                    </button>
                    <a href="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>" class="btn btn-light border py-2 rounded-pill">
                        Annuler
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Studio de Capture & Recadrage partagé (Webcam & Smartphone USB) -->
<?php require dirname(__DIR__) . '/partials/photo_capture_modal.php'; ?>

<!-- Cropper.js Script & Studio Camera Smartphone -->
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="<?= $base_path ?>/assets/js/camera-smartphone.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Réinitialisation personnalisée pour rétablir la photo existante du collaborateur
    const btnResetPhoto = document.getElementById('btnResetPhoto');
    const previewPhoto = document.getElementById('previewPhoto');
    const placeholderPhoto = document.getElementById('placeholderPhoto');
    const photoBase64Input = document.getElementById('photoBase64Input');
    const badgeCropped = document.getElementById('badgeCropped');
    const fileInputPhoto = document.getElementById('fileInputPhoto');

    if (btnResetPhoto) {
        btnResetPhoto.addEventListener('click', function () {
            if (photoBase64Input) photoBase64Input.value = '';
            if (fileInputPhoto) fileInputPhoto.value = '';
            if (badgeCropped) badgeCropped.classList.add('d-none');
            btnResetPhoto.classList.add('d-none');

            <?php if ($hasPhoto): ?>
                if (previewPhoto) {
                    previewPhoto.src = "<?= $photoSrc ?>";
                    previewPhoto.classList.remove('d-none');
                }
                if (placeholderPhoto) placeholderPhoto.classList.add('d-none');
            <?php else: ?>
                if (previewPhoto) {
                    previewPhoto.src = "";
                    previewPhoto.classList.add('d-none');
                }
                if (placeholderPhoto) placeholderPhoto.classList.remove('d-none');
            <?php endif; ?>
        });
    }
});
</script>
