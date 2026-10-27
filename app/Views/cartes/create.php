<?php
/**
 * Formulaire de génération unitaire d'une carte d'identité
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $base_path ?>/cartes" class="btn btn-light border rounded-circle">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-1">Générer une carte d'identité</h4>
        <p class="text-secondary mb-0">Création unitaire d'une carte avec attribution automatique de numéro et jeton sécurisé.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <form method="POST" action="<?= $base_path ?>/cartes/generer">
                <?= \App\Core\Csrf::inputField() ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" id="selectEtab" class="form-select" required>
                            <?php foreach ($etablissements as $etab): ?>
                                <option value="<?= $etab['id'] ?>" <?= ($selected_etab_id == $etab['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
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

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type de bénéficiaire <span class="text-danger">*</span></label>
                        <select name="beneficiaire_type" id="selectType" class="form-select" required>
                            <?php if ($showEleves): ?>
                                <option value="eleve" <?= ($beneficiaire_type === 'eleve') ? 'selected' : '' ?>>Élève (Scolaire)</option>
                            <?php endif; ?>
                            <?php if ($showEtudiants): ?>
                                <option value="etudiant" <?= ($beneficiaire_type === 'etudiant') ? 'selected' : '' ?>>Étudiant (Universitaire)</option>
                            <?php endif; ?>
                            <?php if ($showPersonnel): ?>
                                <option value="personnel" <?= ($beneficiaire_type === 'personnel') ? 'selected' : '' ?>>Personnel & Agent (Professionnel)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Bénéficiaire -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Sélectionner le bénéficiaire <span class="text-danger">*</span></label>
                    <select name="beneficiaire_id" id="selectBeneficiaire" class="form-select" required>
                        <option value="">Chargement des bénéficiaires éligibles...</option>
                    </select>
                    <div class="form-text small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Seuls les bénéficiaires actifs sans carte en cours sont affichés.
                    </div>
                </div>

                <!-- Modèle de carte -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Modèle graphique (Template) <span class="text-danger">*</span></label>
                    <select name="template_id" id="selectTemplate" class="form-select" required>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?= $tpl['id'] ?>" data-cible="<?= htmlspecialchars($tpl['cible']) ?>">
                                [<?= ucfirst(htmlspecialchars($tpl['cible'])) ?>] <?= htmlspecialchars($tpl['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date d'expiration -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Date d'expiration</label>
                    <input type="date" name="date_expiration" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                    <div class="form-text small text-muted">Par défaut : 1 an à compter d'aujourd'hui.</div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= $base_path ?>/cartes" class="btn btn-light border px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary-custom px-4" id="btnSubmit">
                        <i class="bi bi-card-heading me-1"></i> Générer la carte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const selectEtab = document.getElementById('selectEtab');
    const selectType = document.getElementById('selectType');
    const selectBenef = document.getElementById('selectBeneficiaire');
    const selectTpl = document.getElementById('selectTemplate');

    function filterTemplatesByCible() {
        const currentType = selectType.value;
        let hasSelected = false;
        Array.from(selectTpl.options).forEach(opt => {
            const cible = opt.getAttribute('data-cible');
            if (cible === currentType) {
                opt.style.display = '';
                if (!hasSelected) {
                    opt.selected = true;
                    hasSelected = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function loadBeneficiaires() {
        const etabId = selectEtab.value;
        const type = selectType.value;

        selectBenef.innerHTML = '<option value="">Chargement en cours...</option>';
        selectBenef.disabled = true;

        fetch(`<?= $base_path ?>/cartes/ajax-beneficiaires?etablissement_id=${etabId}&type=${type}`)
            .then(res => res.json())
            .then(data => {
                selectBenef.innerHTML = '';
                if (data.length === 0) {
                    selectBenef.innerHTML = '<option value="">Aucun bénéficiaire sans carte trouvé</option>';
                    selectBenef.disabled = true;
                } else {
                    selectBenef.disabled = false;
                    selectBenef.innerHTML = '<option value="">-- Choisir un bénéficiaire (' + data.length + ' disponible(s)) --</option>';
                    data.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.nom} ${b.prenom} (${b.matricule}) - ${b.groupe_nom || ''}`;
                        selectBenef.appendChild(opt);
                    });
                }
            })
            .catch(() => {
                selectBenef.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    selectEtab.addEventListener('change', loadBeneficiaires);
    selectType.addEventListener('change', () => {
        filterTemplatesByCible();
        loadBeneficiaires();
    });

    // Initialisation
    filterTemplatesByCible();
    loadBeneficiaires();
</script>
