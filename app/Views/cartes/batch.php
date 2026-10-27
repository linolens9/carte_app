<?php
/**
 * Formulaire de génération groupée de cartes d'identité (Par Lot)
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $base_path ?>/cartes" class="btn btn-light border rounded-circle">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-1">Génération de cartes par lot</h4>
        <p class="text-secondary mb-0">Émettez des cartes en masse pour une classe entière, une filière ou un département.</p>
    </div>
</div>

<form method="POST" action="<?= $base_path ?>/cartes/generer-lot" id="batchForm">
    <?= \App\Core\Csrf::inputField() ?>

    <div class="row g-4">
        <!-- Paramètres de sélection -->
        <div class="col-lg-4">
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-funnel me-1 text-primary"></i> 1. Critères de sélection
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Établissement <span class="text-danger">*</span></label>
                    <select name="etablissement_id" id="batchEtab" class="form-select" required>
                        <?php foreach ($etablissements as $etab): ?>
                            <option value="<?= $etab['id'] ?>" <?= ($selected_etab_id == $etab['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etab['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php
                    $currentEtab = $active_etab ?? \App\Core\Auth::getActiveEtablissement();
                    if (!$currentEtab && !\App\Core\Auth::hasRole('admin')) {
                        $accessible = \App\Core\Auth::getAccessibleEtablissements();
                        $currentEtab = $accessible[0] ?? null;
                    }

                    $etabType = $currentEtab['type'] ?? '';
                    $etabCat = $currentEtab['categorie'] ?? '';
                    $isAdminOrGest = \App\Core\Auth::hasRole('admin', 'gestionnaire');

                    $isSchool = in_array($etabType, ['primaire', 'college', 'lycee'], true);
                    $isHigherEd = in_array($etabType, ['universite', 'centre_formation'], true);
                    $isPro = ($etabCat === 'professionnel') || in_array($etabType, ['entreprise', 'administration', 'association', 'ong', 'etablissement_prive'], true);

                    $showEleves = !$isHigherEd && !$isPro;
                    $showEtudiants = !$isSchool && !$isPro && ($isAdminOrGest || $isHigherEd);
                    $showPersonnel = true;
                ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Public cible <span class="text-danger">*</span></label>
                    <select name="beneficiaire_type" id="batchType" class="form-select" required>
                        <?php if ($showEleves): ?>
                            <option value="eleve">Élèves (Scolaire)</option>
                        <?php endif; ?>
                        <?php if ($showEtudiants): ?>
                            <option value="etudiant">Étudiants (Universitaire)</option>
                        <?php endif; ?>
                        <?php if ($showPersonnel): ?>
                            <option value="personnel">Personnel (Professionnel)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold" id="lblGroupe">Classe / Filière / Département</label>
                    <select name="groupe_id" id="batchGroupe" class="form-select">
                        <option value="">Tous les groupes</option>
                    </select>
                </div>

                <button type="button" class="btn btn-outline-primary w-100" id="btnCharger">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualiser la liste
                </button>
            </div>

            <!-- Paramètres de la carte -->
            <div class="card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-sliders me-1 text-primary"></i> 2. Paramètres des cartes
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Modèle graphique (Template) <span class="text-danger">*</span></label>
                    <select name="template_id" id="batchTemplate" class="form-select" required>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?= $tpl['id'] ?>" data-cible="<?= htmlspecialchars($tpl['cible']) ?>">
                                [<?= ucfirst(htmlspecialchars($tpl['cible'])) ?>] <?= htmlspecialchars($tpl['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Date d'expiration</label>
                    <input type="date" name="date_expiration" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>

                <div class="pt-3 border-top">
                    <button type="submit" class="btn btn-success w-100" id="btnGenererLot" disabled>
                        <i class="bi bi-stack me-1"></i> Générer les cartes sélectionnées
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des bénéficiaires éligibles avec cases à cocher -->
        <div class="col-lg-8">
            <div class="card-custom p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <div>
                        <h6 class="fw-bold mb-0">Bénéficiaires éligibles sans carte active</h6>
                        <span class="small text-muted" id="countInfo">0 sélectionné(s)</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleAll">
                            Tout sélectionner
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-hover table-custom align-middle small" id="tableBeneficiaires">
                        <thead>
                            <tr>
                                <th width="35">
                                    <input type="checkbox" class="form-check-input" id="chkAllBenef">
                                </th>
                                <th>Bénéficiaire</th>
                                <th>Matricule</th>
                                <th>Groupe / Affectation</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyBeneficiaires">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Cliquez sur « Actualiser la liste » pour charger les bénéficiaires éligibles.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const batchEtab = document.getElementById('batchEtab');
    const batchType = document.getElementById('batchType');
    const batchGroupe = document.getElementById('batchGroupe');
    const batchTpl = document.getElementById('batchTemplate');
    const btnCharger = document.getElementById('btnCharger');
    const tbody = document.getElementById('tbodyBeneficiaires');
    const chkAll = document.getElementById('chkAllBenef');
    const btnToggleAll = document.getElementById('btnToggleAll');
    const btnSubmit = document.getElementById('btnGenererLot');
    const countInfo = document.getElementById('countInfo');

    function filterTemplates() {
        const currentType = batchType.value;
        let selected = false;
        Array.from(batchTpl.options).forEach(opt => {
            const cible = opt.getAttribute('data-cible');
            if (cible === currentType) {
                opt.style.display = '';
                if (!selected) {
                    opt.selected = true;
                    selected = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function updateCount() {
        const checked = document.querySelectorAll('.b-chk:checked').length;
        countInfo.textContent = `${checked} sélectionné(s)`;
        btnSubmit.disabled = checked === 0;
    }

    function loadBeneficiairesList() {
        const etabId = batchEtab.value;
        const type = batchType.value;
        const grpId = batchGroupe.value;

        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des données...</td></tr>';
        btnSubmit.disabled = true;

        fetch(`<?= $base_path ?>/cartes/ajax-beneficiaires?etablissement_id=${etabId}&type=${type}&groupe_id=${grpId}`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Aucun bénéficiaire sans carte active trouvé pour ces critères.</td></tr>';
                    countInfo.textContent = '0 sélectionné';
                    return;
                }

                data.forEach(b => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><input type="checkbox" name="beneficiaire_ids[]" value="${b.id}" class="form-check-input b-chk" checked></td>
                        <td><strong>${b.nom}</strong> ${b.prenom}</td>
                        <td><span class="font-monospace text-primary">${b.matricule}</span></td>
                        <td>${b.groupe_nom || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.querySelectorAll('.b-chk').forEach(c => c.addEventListener('change', updateCount));
                chkAll.checked = true;
                updateCount();
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Erreur de chargement.</td></tr>';
            });
    }

    chkAll.addEventListener('change', () => {
        document.querySelectorAll('.b-chk').forEach(c => c.checked = chkAll.checked);
        updateCount();
    });

    btnToggleAll.addEventListener('click', () => {
        chkAll.checked = !chkAll.checked;
        document.querySelectorAll('.b-chk').forEach(c => c.checked = chkAll.checked);
        updateCount();
    });

    btnCharger.addEventListener('click', loadBeneficiairesList);
    batchType.addEventListener('change', () => {
        filterTemplates();
        loadBeneficiairesList();
    });
    batchEtab.addEventListener('change', loadBeneficiairesList);

    // Initialisation
    filterTemplates();
    loadBeneficiairesList();
</script>
