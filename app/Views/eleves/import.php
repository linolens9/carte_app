<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etablissements */
/** @var array $classes */
/** @var int|null $activeEtabId */

$importErrors = \App\Core\Session::get('import_errors') ?? [];
if (!empty($importErrors)) {
    \App\Core\Session::remove('import_errors');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Importer des Élèves (CSV)</h2>
        <p class="text-muted small mb-0">Importation massive d'effectifs scolaires à partir d'un fichier tableur ou SIGE</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_path ?>/eleves/modele-csv" class="btn btn-outline-success rounded-pill px-3">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Télécharger le modèle CSV
        </a>
        <a href="<?= $base_path ?>/eleves" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
    </div>
</div>

<?php if (!empty($importErrors)): ?>
    <div class="card-custom mb-4 border-warning">
        <div class="card-header-custom bg-warning-subtle text-dark d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Rapport des lignes non importées ou en erreur (<?= count($importErrors) ?>)</h6>
        </div>
        <div class="p-3">
            <div class="alert alert-warning small mb-2">
                Certaines lignes ont été ignorées en raison de données manquantes ou de doublons. Les autres élèves valides ont bien été enregistrés.
            </div>
            <div class="overflow-auto" style="max-height: 200px;">
                <ul class="list-group list-group-flush small">
                    <?php foreach ($importErrors as $err): ?>
                        <li class="list-group-item py-1 bg-transparent text-danger">
                            <i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($err) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Colonne GAUCHE : Formulaire d'importation -->
    <div class="col-lg-7">
        <div class="card-custom">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Fichier source et établissement</h6>
            </div>
            <div class="p-4">
                <form action="<?= $base_path ?>/eleves/importer" method="POST" enctype="multipart/form-data" id="formImportEleves">
                    <?= $csrf_input ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Établissement scolaire de destination <span class="text-danger">*</span></label>
                        <?php if (count($etablissements) === 1): ?>
                            <?php $singleEtab = $etablissements[0]; ?>
                            <input type="hidden" name="etablissement_id" value="<?= (int)$singleEtab['id'] ?>">
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?><?= !empty($singleEtab['code']) ? ' (' . htmlspecialchars($singleEtab['code']) . ')' : '' ?>" readonly>
                        <?php else: ?>
                            <select name="etablissement_id" class="form-select" required>
                                <?php foreach ($etablissements as $etab): ?>
                                    <option value="<?= (int)$etab['id'] ?>" <?= (int)$activeEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <div class="form-text small">Les classes non existantes spécifiées dans le fichier seront créées automatiquement.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Fichier CSV <span class="text-danger">*</span></label>
                        
                        <!-- Zone Drag & Drop -->
                        <div id="dropZone" class="border border-2 border-dashed rounded-3 p-4 text-center bg-light cursor-pointer position-relative" 
                             style="border-color: #cbd5e1 !important; transition: all 0.2s ease;">
                            <input type="file" name="csv_file" id="csvFileInput" accept=".csv,text/csv" 
                                   class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" required>
                            
                            <div id="dropZonePrompt">
                                <i class="bi bi-file-earmark-spreadsheet fs-1 text-primary mb-2"></i>
                                <h6 class="fw-semibold text-dark mb-1">Glissez-déposez votre fichier CSV ici</h6>
                                <p class="text-muted small mb-2">ou cliquez pour parcourir vos dossiers</p>
                                <span class="badge bg-light text-secondary border font-monospace">Encodage UTF-8 &bull; Séparateur ";" ou ","</span>
                            </div>

                            <div id="fileSelectedInfo" class="d-none">
                                <i class="bi bi-file-earmark-check-fill fs-1 text-success mb-2"></i>
                                <h6 class="fw-bold text-dark mb-1" id="fileNameDisplay">fichier.csv</h6>
                                <p class="text-muted small mb-0" id="fileSizeDisplay">0 Ko</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="<?= $base_path ?>/eleves/modele-csv" class="btn btn-link text-decoration-none p-0 small">
                            <i class="bi bi-download me-1"></i>Télécharger le gabarit d'exemple
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSubmitImport">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Démarrer l'importation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Colonne DROITE : Guide & Spécifications du format CSV -->
    <div class="col-lg-5">
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Spécifications du format CSV</h6>
            </div>
            <div class="p-4 small">
                <p class="text-secondary mb-3">
                    Pour garantir l'intégrité de l'importation, votre fichier doit respecter la structure suivante :
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Colonne</th>
                                <th>Obligatoire</th>
                                <th>Exemple</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-monospace">matricule</td>
                                <td><span class="badge bg-secondary">Optionnel</span></td>
                                <td>Auto si vide</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">nom</td>
                                <td><span class="badge bg-danger">Oui</span></td>
                                <td>MBOUMBA</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">prenom</td>
                                <td><span class="badge bg-danger">Oui</span></td>
                                <td>Paul Arnaud</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">sexe</td>
                                <td><span class="badge bg-secondary">M / F</span></td>
                                <td>M</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">date_naissance</td>
                                <td><span class="badge bg-danger">Oui</span></td>
                                <td>2008-05-14</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">lieu_naissance</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>Libreville</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">nationalite</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>Gabonaise</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">classe</td>
                                <td><span class="badge bg-danger">Oui</span></td>
                                <td>Terminale D1</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">niveau</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>Secondaire</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">annee_scolaire</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>2026-2027</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">tuteur_nom</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>MBOUMBA Pierre</td>
                            </tr>
                            <tr>
                                <td class="font-monospace">tuteur_telephone</td>
                                <td><span class="badge bg-secondary">Non</span></td>
                                <td>+241 07 12 34 56</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0">
                    <i class="bi bi-lightbulb me-1"></i>
                    <strong>Astuce :</strong> Les photos d'identité des élèves importés pourront être ajoutées individuellement ensuite par capture webcam ou upload dans la fiche élève.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csvFileInput = document.getElementById('csvFileInput');
    const dropZone = document.getElementById('dropZone');
    const dropZonePrompt = document.getElementById('dropZonePrompt');
    const fileSelectedInfo = document.getElementById('fileSelectedInfo');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileSizeDisplay = document.getElementById('fileSizeDisplay');

    csvFileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            fileNameDisplay.textContent = file.name;
            fileSizeDisplay.textContent = (file.size / 1024).toFixed(1) + ' Ko';

            dropZonePrompt.classList.add('d-none');
            fileSelectedInfo.classList.remove('d-none');
            dropZone.style.borderColor = '#10b981';
            dropZone.style.backgroundColor = '#f0fdf4';
        }
    });

    ['dragover', 'dragenter'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#3b82f6';
            dropZone.style.backgroundColor = '#eff6ff';
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            if (!csvFileInput.files.length) {
                dropZone.style.borderColor = '#cbd5e1';
                dropZone.style.backgroundColor = '#f8fafc';
            }
        });
    });
});
</script>
