<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etablissements */
/** @var array $departements */
/** @var int|null $activeEtabId */
/** @var array $errors */
/** @var array $old */
?>

<!-- Cropper.js CSS -->
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Ajouter un Collaborateur</h2>
        <p class="text-muted small mb-0">Enregistrement d'un agent, salarié ou cadre au registre du personnel</p>
    </div>
    <a href="<?= $base_path ?>/personnels" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<form action="<?= $base_path ?>/personnels/creer" method="POST" enctype="multipart/form-data" id="formCreatePersonnel">
    <?= $csrf_input ?>
    <!-- Champ photo Base64 issu du recadrage / webcam -->
    <input type="hidden" name="photo_base64" id="photoBase64Input" value="">

    <div class="row g-4">
        <!-- Colonne gauche : Identité, Affectation & Contact -->
        <div class="col-lg-8">
            <!-- 1. Identité de l'agent -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>1. Identité du Collaborateur</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control text-uppercase <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Ex: BILOGHE" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" placeholder="Ex: Serge Roland" required>
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Numéro Professionnel / Matricule Interne</label>
                            <input type="text" name="numero_professionnel" class="form-control font-monospace text-uppercase"
                                   placeholder="Laisser vide pour génération automatique (Ex: ANINF-2026-00001)"
                                   value="<?= htmlspecialchars($old['numero_professionnel'] ?? '') ?>">
                            <div class="form-text small">Attribution automatique selon la charte d'immatriculation de la structure si non précisé.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Affectation, Poste & Organisation -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>2. Affectation, Poste & Fonction</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Structure / Organisation <span class="text-danger">*</span></label>
                            <?php if (count($etablissements) === 1): ?>
                                <?php $singleEtab = $etablissements[0]; ?>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?> (<?= htmlspecialchars($singleEtab['code'] ?? '') ?>)" readonly>
                                <input type="hidden" name="etablissement_id" id="etabSelect" value="<?= (int)$singleEtab['id'] ?>">
                            <?php else: ?>
                                <select name="etablissement_id" id="etabSelect" class="form-select <?= !empty($errors['etablissement_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">-- Choisir la structure --</option>
                                    <?php foreach ($etablissements as $etab): ?>
                                        <option value="<?= (int)$etab['id'] ?>" <?= ((int)($old['etablissement_id'] ?? $activeEtabId) === (int)$etab['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <?php if (!empty($errors['etablissement_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['etablissement_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Direction / Département</label>
                            <select name="departement_id" id="departementSelect" class="form-select">
                                <option value="">-- Choisir la direction / département --</option>
                                <?php foreach ($departements as $dept): ?>
                                    <option value="<?= (int)$dept['id'] ?>" <?= ((string)($old['departement_id'] ?? '') === (string)$dept['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Fonction officielle <span class="text-danger">*</span></label>
                            <input type="text" name="fonction" class="form-control <?= !empty($errors['fonction']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['fonction'] ?? '') ?>" placeholder="Ex: Ingénieur Systèmes & Réseaux" required>
                            <?php if (!empty($errors['fonction'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['fonction'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Service / Unité d'affectation</label>
                            <input type="text" name="service" class="form-control"
                                   value="<?= htmlspecialchars($old['service'] ?? '') ?>" placeholder="Ex: Pôle Cloud & Virtualisation">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date d'embauche / Prise de service</label>
                            <input type="date" name="date_embauche" class="form-control"
                                   value="<?= htmlspecialchars($old['date_embauche'] ?? '') ?>">
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
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="collaborateur@organisation.ga">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Numéro de Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+241 07 ...">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adresse de résidence</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($old['adresse'] ?? '') ?>" placeholder="Quartier, Boulevard, Libreville">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Photo d'identité professionnelle (3:4) -->
        <div class="col-lg-4">
            <div class="card-custom sticky-top" style="top: 20px;">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-camera me-2 text-primary"></i>Photo de Badge (3:4)</h6>
                </div>
                <div class="p-4 text-center">
                    <div class="position-relative mx-auto mb-3" style="width: 180px; height: 240px; border-radius: 12px; overflow: hidden; background: #e2e8f0; border: 2px dashed #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                        <img id="previewPhoto" src="" alt="Photo collaborateur" class="w-100 h-100 object-fit-cover d-none">
                        <div id="placeholderPhoto" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <i class="bi bi-person-bounding-box fs-1 mb-1"></i>
                            <span class="small">Aucune photo</span>
                        </div>
                        <span id="badgeCropped" class="badge bg-success position-absolute bottom-0 start-50 translate-middle-x mb-2 d-none">
                            <i class="bi bi-check2 me-1"></i>Prête (600x800)
                        </span>
                    </div>

                    <p class="text-muted small mb-3">
                        Format badge professionnel d'entreprise : ratio 3:4, min 600x800px, tenue professionnelle, fond neutre.
                    </p>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary rounded-pill btn-sm py-2 fw-semibold" id="btnOpenWebcam">
                            <i class="bi bi-camera-video me-1"></i>Prendre via Webcam
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

            <!-- Boutons Enregistrer -->
            <div class="card-custom p-4 mt-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer le collaborateur
                    </button>
                    <a href="<?= $base_path ?>/personnels" class="btn btn-light border py-2 rounded-pill">
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
    // 1. Cascading AJAX Établissement -> Départements
    const etabSelect = document.getElementById('etabSelect');
    const departementSelect = document.getElementById('departementSelect');

    if (etabSelect) {
        etabSelect.addEventListener('change', function () {
            const etabId = this.value;
            if (!etabId) return;

            fetch('<?= $base_path ?>/personnels/ajax-departements?etablissement_id=' + etabId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        departementSelect.innerHTML = '<option value="">-- Choisir la direction / département --</option>';
                        data.departements.forEach(d => {
                            departementSelect.innerHTML += `<option value="${d.id}">${d.nom} (${d.code})</option>`;
                        });
                    }
                })
                .catch(e => console.error(e));
        });
    }
});
</script>

