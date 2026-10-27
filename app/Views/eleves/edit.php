<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $eleve */
/** @var array $classes */
/** @var array $etablissements */
/** @var array $errors */
?>

<!-- Cropper.js CSS -->
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modifier l'Élève</h2>
        <p class="text-muted small mb-0">Matricule : <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($eleve['matricule']) ?></span> &bull; <?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-eye me-1"></i>Voir la fiche
        </a>
        <a href="<?= $base_path ?>/eleves" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
    </div>
</div>

<form action="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>/modifier" method="POST" enctype="multipart/form-data" id="formEditEleve">
    <?= $csrf_input ?>
    <!-- Champ caché pour la photo Base64 issue du recadrage ou de la webcam -->
    <input type="hidden" name="photo_base64" id="photoBase64Input" value="">

    <div class="row g-4">
        <!-- Colonne gauche : Données d'état civil, scolarité et contact -->
        <div class="col-lg-8">
            <!-- 1. Identité & État Civil -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person me-2 text-primary"></i>1. État Civil & Identité</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control text-uppercase <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($eleve['nom'] ?? '') ?>" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($eleve['prenom'] ?? '') ?>" required>
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sexe <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-select <?= !empty($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                                <option value="M" <?= ($eleve['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                                <option value="F" <?= ($eleve['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" class="form-control <?= !empty($errors['date_naissance']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($eleve['date_naissance'] ?? '') ?>" required>
                            <?php if (!empty($errors['date_naissance'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['date_naissance'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lieu de naissance</label>
                            <input type="text" name="lieu_naissance" class="form-control"
                                   value="<?= htmlspecialchars($eleve['lieu_naissance'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nationalité <span class="text-danger">*</span></label>
                            <input type="text" name="nationalite" class="form-control"
                                   value="<?= htmlspecialchars($eleve['nationalite'] ?? 'Gabonaise') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone élève</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($eleve['telephone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Scolarité & Affectation -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>2. Inscription & Scolarité</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Établissement scolaire</label>
                            <input type="text" class="form-control bg-light" 
                                   value="<?= htmlspecialchars($eleve['etablissement_nom'] ?? 'Établissement actuel') ?>" readonly>
                            <div class="form-text small">L'établissement est rattaché au dossier d'origine.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Matricule</label>
                            <input type="text" class="form-control bg-light font-monospace fw-bold"
                                   value="<?= htmlspecialchars($eleve['matricule'] ?? '') ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Classe d'affectation <span class="text-danger">*</span></label>
                            <select name="classe_id" id="classeSelect" class="form-select <?= !empty($errors['classe_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Choisir la classe --</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= (int)$cl['id'] ?>" 
                                            data-niveau="<?= htmlspecialchars($cl['niveau']) ?>"
                                            data-annee="<?= htmlspecialchars($cl['annee_scolaire'] ?? '') ?>"
                                            <?= (int)($eleve['classe_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cl['libelle']) ?> (<?= htmlspecialchars($cl['niveau']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['classe_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['classe_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Niveau <span class="text-danger">*</span></label>
                            <input type="text" name="niveau" id="niveauInput" class="form-control"
                                   value="<?= htmlspecialchars($eleve['niveau'] ?? 'Secondaire') ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Année scolaire <span class="text-danger">*</span></label>
                            <input type="text" name="annee_scolaire" id="anneeInput" class="form-control"
                                   value="<?= htmlspecialchars($eleve['annee_scolaire'] ?? '2026-2027') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Statut administratif <span class="text-danger">*</span></label>
                            <select name="statut" class="form-select">
                                <option value="actif" <?= ($eleve['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif (Inscrit & fréquentant)</option>
                                <option value="archive" <?= ($eleve['statut'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé (Parti / Transféré / Sorti)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Contact du Tuteur & Urgence -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-telephone-plus me-2 text-primary"></i>3. Tuteur Légal & Contact d'Urgence</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom & prénom du tuteur / parent</label>
                            <input type="text" name="tuteur_nom" class="form-control"
                                   value="<?= htmlspecialchars($eleve['tuteur_nom'] ?? '') ?>" placeholder="Ex: OBAME Martin">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone d'urgence du tuteur</label>
                            <input type="text" name="tuteur_telephone" class="form-control"
                                   value="<?= htmlspecialchars($eleve['tuteur_telephone'] ?? '') ?>" placeholder="+241 07 ...">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adresse de résidence</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($eleve['adresse'] ?? '') ?>" placeholder="Quartier, Rue, Ville">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Photo d'identité (Aperçu + Recadrage + Webcam) -->
        <div class="col-lg-4">
            <div class="card-custom sticky-top" style="top: 20px;">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-camera me-2 text-primary"></i>Photo d'Identité (3:4)</h6>
                </div>
                <div class="p-4 text-center">
                    <!-- Cadre de prévisualisation au ratio officiel 3:4 -->
                    <div class="position-relative mx-auto mb-3" style="width: 180px; height: 240px; border-radius: 12px; overflow: hidden; background: #e2e8f0; border: 2px dashed #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                        <?php 
                        $hasPhoto = !empty($eleve['photo']) && (file_exists(public_path($eleve['photo'])) || str_starts_with($eleve['photo'], 'uploads/'));
                        $photoSrc = $hasPhoto ? asset_url($eleve['photo']) : '';
                        ?>
                        <img id="previewPhoto" src="<?= $photoSrc ?>" 
                             alt="Photo élève" class="w-100 h-100 object-fit-cover <?= $hasPhoto ? '' : 'd-none' ?>">
                        <div id="placeholderPhoto" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted <?= $hasPhoto ? 'd-none' : '' ?>">
                            <i class="bi bi-person-bounding-box fs-1 mb-1"></i>
                            <span class="small">Aucune photo</span>
                        </div>
                        <span id="badgeCropped" class="badge bg-success position-absolute bottom-0 start-50 translate-middle-x mb-2 d-none">
                            <i class="bi bi-check2 me-1"></i>Prête
                        </span>
                    </div>

                    <p class="text-muted small mb-3">
                        Format conforme carte d'identité scolaire (ratio 3:4, min 600x800px, fond neutre).
                    </p>

                    <!-- Boutons d'action pour la photo -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success rounded-pill btn-sm fw-semibold" id="btnOpenSmartphone">
                            <i class="bi bi-phone me-1"></i>Filmer via Smartphone (USB)
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill btn-sm" id="btnOpenWebcam">
                            <i class="bi bi-camera-video me-1"></i>Prendre via Webcam
                        </button>
                        
                        <label class="btn btn-outline-primary rounded-pill btn-sm mb-0">
                            <i class="bi bi-upload me-1"></i>Choisir un fichier
                            <input type="file" name="photo_file" id="fileInputPhoto" accept="image/jpeg,image/png,image/webp" class="d-none">
                        </label>

                        <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm d-none" id="btnResetPhoto">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                        </button>
                    </div>

                    <?php if (!empty($errors['photo'])): ?>
                        <div class="text-danger small mt-2"><?= htmlspecialchars($errors['photo'][0]) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Boutons de soumission -->
            <div class="card-custom p-4 mt-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer les modifications
                    </button>
                    <a href="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>" class="btn btn-light border py-2 rounded-pill">
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
    // 1. Sync Classe -> Niveau & Année scolaire
    const classeSelect = document.getElementById('classeSelect');
    const niveauInput = document.getElementById('niveauInput');
    const anneeInput = document.getElementById('anneeInput');

    if (classeSelect && niveauInput) {
        classeSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.niveau) {
                niveauInput.value = opt.dataset.niveau;
            }
            if (opt && opt.dataset.annee && opt.dataset.annee.trim() !== '') {
                anneeInput.value = opt.dataset.annee;
            }
        });
    }

    // 2. Réinitialisation personnalisée pour réafficher la photo existante
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

