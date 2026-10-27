<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etablissements */
/** @var array $filieres */
/** @var array $classes */
/** @var int|null $activeEtabId */
/** @var array $errors */
/** @var array $old */
?>

<!-- Cropper.js CSS -->
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Inscrire un Étudiant</h2>
        <p class="text-muted small mb-0">Enregistrement universitaire, affiliation académique et capture de la photo d'identité</p>
    </div>
    <a href="<?= $base_path ?>/etudiants" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<form action="<?= $base_path ?>/etudiants/creer" method="POST" enctype="multipart/form-data" id="formCreateEtudiant">
    <?= $csrf_input ?>
    <!-- Champ photo Base64 issu du recadrage / webcam -->
    <input type="hidden" name="photo_base64" id="photoBase64Input" value="">

    <div class="row g-4">
        <!-- Colonne gauche : État Civil, Contact & Inscription -->
        <div class="col-lg-8">
            <!-- 1. État Civil & Identité -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>1. État Civil & Identité</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control text-uppercase <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Ex: MOUSSAVOU" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" placeholder="Ex: Aïcha Grâce" required>
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
                            <label class="form-label small fw-semibold">Nationalité <span class="text-danger">*</span></label>
                            <input type="text" name="nationalite" class="form-control"
                                   value="<?= htmlspecialchars($old['nationalite'] ?? 'Gabonaise') ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Parcours Universitaire & Affiliation -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>2. Cursus & Affiliation Académique</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Établissement Universitaire <span class="text-danger">*</span></label>
                            <?php if (count($etablissements) === 1): ?>
                                <?php $singleEtab = $etablissements[0]; ?>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?> (<?= htmlspecialchars($singleEtab['code'] ?? '') ?>)" readonly>
                                <input type="hidden" name="etablissement_id" id="etabSelect" value="<?= (int)$singleEtab['id'] ?>">
                            <?php else: ?>
                                <select name="etablissement_id" id="etabSelect" class="form-select <?= !empty($errors['etablissement_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">-- Choisir l'université / institut --</option>
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
                            <label class="form-label small fw-semibold">Filière / Département d'études <span class="text-danger">*</span></label>
                            <select name="filiere_id" id="filiereSelect" class="form-select <?= !empty($errors['filiere_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Choisir la filière --</option>
                                <?php foreach ($filieres as $fil): ?>
                                    <option value="<?= (int)$fil['id'] ?>" <?= ((string)($old['filiere_id'] ?? '') === (string)$fil['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fil['libelle']) ?> (<?= htmlspecialchars($fil['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['filiere_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['filiere_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Niveau / Cycle LMD <span class="text-danger">*</span></label>
                            <select name="niveau" class="form-select" required>
                                <option value="Licence 1 (L1)" <?= ($old['niveau'] ?? '') === 'Licence 1 (L1)' ? 'selected' : '' ?>>Licence 1 (L1)</option>
                                <option value="Licence 2 (L2)" <?= ($old['niveau'] ?? '') === 'Licence 2 (L2)' ? 'selected' : '' ?>>Licence 2 (L2)</option>
                                <option value="Licence 3 (L3)" <?= ($old['niveau'] ?? '') === 'Licence 3 (L3)' ? 'selected' : '' ?>>Licence 3 (L3)</option>
                                <option value="Master 1 (M1)" <?= ($old['niveau'] ?? '') === 'Master 1 (M1)' ? 'selected' : '' ?>>Master 1 (M1)</option>
                                <option value="Master 2 (M2)" <?= ($old['niveau'] ?? '') === 'Master 2 (M2)' ? 'selected' : '' ?>>Master 2 (M2)</option>
                                <option value="Doctorat" <?= ($old['niveau'] ?? '') === 'Doctorat' ? 'selected' : '' ?>>Doctorat</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Année Universitaire <span class="text-danger">*</span></label>
                            <input type="text" name="annee_scolaire" class="form-control" 
                                   value="<?= htmlspecialchars($old['annee_scolaire'] ?? '2026-2027') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Groupe / TD (optionnel)</label>
                            <select name="classe_id" id="classeSelect" class="form-select">
                                <option value="">-- Aucun groupe spécifique --</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= (int)$cl['id'] ?>" <?= ((string)($old['classe_id'] ?? '') === (string)$cl['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cl['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Matricule Étudiant</label>
                            <input type="text" name="matricule" class="form-control font-monospace text-uppercase"
                                   placeholder="Laisser vide pour génération automatique (Ex: UOB-2026-00001)"
                                   value="<?= htmlspecialchars($old['matricule'] ?? '') ?>">
                            <div class="form-text small">Si non spécifié, le numéro sera généré conformément au code de l'université.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Coordonnées & Contact Étudiant -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-telephone me-2 text-primary"></i>3. Coordonnées Personnelles</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Adresse E-mail</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="etudiant@uob.ga">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Numéro de Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+241 07 ...">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adresse de résidence</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($old['adresse'] ?? '') ?>" placeholder="Cité universitaire, Quartier, Ville">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Photo d'identité (Aperçu + Webcam + Cropper) -->
        <div class="col-lg-4">
            <div class="card-custom sticky-top" style="top: 20px;">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0"><i class="bi bi-camera me-2 text-primary"></i>Photo d'Identité (3:4)</h6>
                </div>
                <div class="p-4 text-center">
                    <div class="position-relative mx-auto mb-3" style="width: 180px; height: 240px; border-radius: 12px; overflow: hidden; background: #e2e8f0; border: 2px dashed #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                        <img id="previewPhoto" src="" alt="Photo étudiant" class="w-100 h-100 object-fit-cover d-none">
                        <div id="placeholderPhoto" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <i class="bi bi-person-bounding-box fs-1 mb-1"></i>
                            <span class="small">Aucune photo</span>
                        </div>
                        <span id="badgeCropped" class="badge bg-success position-absolute bottom-0 start-50 translate-middle-x mb-2 d-none">
                            <i class="bi bi-check2 me-1"></i>Prête (600x800)
                        </span>
                    </div>

                    <p class="text-muted small mb-3">
                        Format officiel carte étudiante : ratio 3:4, min 600x800px, fond neutre sans ombres.
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

            <!-- Boutons Enregistrer -->
            <div class="card-custom p-4 mt-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer l'étudiant
                    </button>
                    <a href="<?= $base_path ?>/etudiants" class="btn btn-light border py-2 rounded-pill">
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
    // 1. Mise à jour dynamique des filières selon l'établissement choisi
    const etabSelect = document.getElementById('etabSelect');
    const filiereSelect = document.getElementById('filiereSelect');
    const classeSelect = document.getElementById('classeSelect');

    if (etabSelect) {
        etabSelect.addEventListener('change', function () {
            const etabId = this.value;
            if (!etabId) return;

            fetch('<?= $base_path ?>/etudiants/ajax-filieres?etablissement_id=' + etabId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        filiereSelect.innerHTML = '<option value="">-- Choisir la filière --</option>';
                        data.filieres.forEach(f => {
                            filiereSelect.innerHTML += `<option value="${f.id}">${f.libelle} (${f.code})</option>`;
                        });

                        classeSelect.innerHTML = '<option value="">-- Aucun groupe spécifique --</option>';
                        data.classes.forEach(c => {
                            classeSelect.innerHTML += `<option value="${c.id}">${c.libelle}</option>`;
                        });
                    }
                })
                .catch(e => console.error(e));
        });
    }
});
</script>

