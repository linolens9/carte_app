<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etablissements */
/** @var array $classes */
/** @var int|null $activeEtabId */
/** @var array $errors */
/** @var array $old */
?>

<!-- Cropper.js CSS -->
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Inscrire un Élève</h2>
        <p class="text-muted small mb-0">Renseignement de l'état civil, scolarité, tuteur et capture de la photo</p>
    </div>
    <a href="<?= $base_path ?>/eleves" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<form action="<?= $base_path ?>/eleves/creer" method="POST" enctype="multipart/form-data" id="formCreateEleve">
    <?= $csrf_input ?>
    <!-- Champ caché pour la photo Base64 issue du recadrage ou de la webcam -->
    <input type="hidden" name="photo_base64" id="photoBase64Input" value="">

    <div class="row g-4">
        <!-- Colonne gauche : Données d'état civil et scolarité -->
        <div class="col-lg-8">
            <!-- 1. Identité -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person me-2 text-primary"></i>1. État Civil & Identité</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control text-uppercase <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Ex: MBOUMBA" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" placeholder="Ex: Yannick Paul" required>
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Sexe <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-select <?= !empty($errors['sexe']) ? 'is-invalid' : '' ?>" required>
                                <option value="M" <?= ($old['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                                <option value="F" <?= ($old['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" class="form-control <?= !empty($errors['date_naissance']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['date_naissance'] ?? '') ?>" required>
                            <?php if (!empty($errors['date_naissance'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['date_naissance'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lieu de naissance</label>
                            <input type="text" name="lieu_naissance" class="form-control"
                                   value="<?= htmlspecialchars($old['lieu_naissance'] ?? '') ?>" placeholder="Ex: Lambaréné">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nationalité <span class="text-danger">*</span></label>
                            <input type="text" name="nationalite" class="form-control"
                                   value="<?= htmlspecialchars($old['nationalite'] ?? 'Gabonaise') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone de l'élève (optionnel)</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+241 07 ...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Scolarité -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>2. Inscription & Scolarité</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Établissement <span class="text-danger">*</span></label>
                            <?php if (count($etablissements) === 1): ?>
                                <?php $singleEtab = $etablissements[0]; ?>
                                <input type="hidden" name="etablissement_id" id="etabSelect" value="<?= (int)$singleEtab['id'] ?>">
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?> (<?= htmlspecialchars($singleEtab['code']) ?>)" readonly>
                            <?php else: ?>
                                <select name="etablissement_id" id="etabSelect" class="form-select <?= !empty($errors['etablissement_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">Sélectionner un établissement</option>
                                    <?php foreach ($etablissements as $e): ?>
                                        <option value="<?= $e['id'] ?>" <?= (($old['etablissement_id'] ?? $activeEtabId) == $e['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['nom']) ?> (<?= htmlspecialchars($e['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <?php if (!empty($errors['etablissement_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['etablissement_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Classe <span class="text-danger">*</span></label>
                            <select name="classe_id" id="classeSelect" class="form-select <?= !empty($errors['classe_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Sélectionner la classe</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-niveau="<?= htmlspecialchars($c['niveau']) ?>" <?= (($old['classe_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Niveau <span class="text-danger">*</span></label>
                            <input type="text" name="niveau" id="niveauInput" class="form-control <?= !empty($errors['niveau']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['niveau'] ?? 'Secondaire') ?>" placeholder="Ex: Terminale" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Année Scolaire <span class="text-danger">*</span></label>
                            <input type="text" name="annee_scolaire" class="form-control"
                                   value="<?= htmlspecialchars($old['annee_scolaire'] ?? date('Y') . '-' . (date('Y') + 1)) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Matricule (optionnel)</label>
                            <input type="text" name="matricule" class="form-control font-monospace text-uppercase"
                                   value="<?= htmlspecialchars($old['matricule'] ?? '') ?>" placeholder="Auto si vide">
                            <div class="form-text small" style="font-size: 0.72rem;">Généré automatiquement si non renseigné.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Tuteur & Urgence -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-telephone-plus me-2 text-primary"></i>3. Contact d'Urgence & Parent/Tuteur</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom du parent ou tuteur</label>
                            <input type="text" name="tuteur_nom" class="form-control"
                                   value="<?= htmlspecialchars($old['tuteur_nom'] ?? '') ?>" placeholder="Ex: MBOUMBA Albert">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone d'urgence (Tuteur)</label>
                            <input type="text" name="tuteur_telephone" class="form-control"
                                   value="<?= htmlspecialchars($old['tuteur_telephone'] ?? '') ?>" placeholder="+241 07 00 00 00">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Adresse de résidence</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($old['adresse'] ?? '') ?>" placeholder="Quartier, Ville, B.P.">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Photo d'identité (Webcam / Upload / Cropper) -->
        <div class="col-lg-4">
            <div class="card-custom mb-4 sticky-top" style="top: 90px;">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-camera me-2 text-primary"></i>Photo d'Identité</h6>
                </div>
                <div class="p-4 text-center">
                    <!-- Cadre de prévisualisation au format 3:4 -->
                    <div class="mx-auto mb-3 border rounded-3 position-relative overflow-hidden shadow-sm"
                         id="photoPreviewContainer"
                         style="width: 180px; height: 240px; background: #f8fafc; display: flex; align-items: center; justify-content: center;">
                        <img id="photoPreviewImg" src="" alt="Aperçu photo" class="w-100 h-100 object-fit-cover d-none">
                        <div id="photoPlaceholder" class="text-center p-3 text-muted">
                            <i class="bi bi-person-bounding-box fs-1 d-block mb-1 text-secondary"></i>
                            <span class="small" style="font-size: 0.75rem;">Format 3:4 centré requis</span>
                        </div>
                        <span id="badgeCropped" class="badge bg-success position-absolute bottom-0 start-50 translate-middle-x mb-2 d-none">
                            <i class="bi bi-check2 me-1"></i>Prête (600x800)
                        </span>
                    </div>

                    <!-- Boutons d'acquisition -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-2 fw-semibold" id="btnOpenWebcam">
                            <i class="bi bi-camera-video me-1"></i>Capturer via Webcam
                        </button>
                        <label class="btn btn-outline-secondary btn-sm rounded-pill py-2 mb-0 cursor-pointer">
                            <i class="bi bi-upload me-1"></i>Téléverser une image
                            <input type="file" id="fileInputPhoto" class="d-none" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill py-1 d-none" id="btnResetPhoto">
                            <i class="bi bi-trash me-1"></i>Supprimer la photo
                        </button>
                    </div>

                    <?php if (!empty($errors['photo'])): ?>
                        <div class="text-danger small mt-2"><?= htmlspecialchars($errors['photo'][0]) ?></div>
                    <?php endif; ?>

                    <div class="form-text small mt-3" style="font-size: 0.72rem;">
                        Recadrage automatique 600×800 px portrait avec compression JPEG haute qualité.
                    </div>

                    <div class="d-grid mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer l'élève
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Studio de Capture & Recadrage partagé (Webcam & Smartphone USB) -->
<?php require dirname(__DIR__) . '/partials/photo_capture_modal.php'; ?>

<!-- Cropper.js & Studio Camera Smartphone -->
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="<?= $base_path ?>/assets/js/camera-smartphone.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Synchronisation automatique de la classe vers le niveau
    const classeSelect = document.getElementById('classeSelect');
    const niveauInput = document.getElementById('niveauInput');
    if (classeSelect && niveauInput) {
        classeSelect.addEventListener('change', () => {
            const selectedOpt = classeSelect.options[classeSelect.selectedIndex];
            const niv = selectedOpt.getAttribute('data-niveau');
            if (niv) {
                niveauInput.value = niv;
            }
        });
    }
});
</script>
