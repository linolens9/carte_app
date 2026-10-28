<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $items */
/** @var array $counts */
/** @var array $accessibleEtabs */
/** @var int $selectedEtabId */
/** @var int $selectedDeptId */
/** @var array $departements */
/** @var string $search */
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Filières & Séries</h2>
        <p class="text-muted small mb-0">Spécialités d'études, séries générales/techniques et parcours de formation</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddFiliere">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle filière
        </button>
    </div>
</div>

<!-- Navigation transversale des structures -->
<div class="card-custom mb-4 p-2 bg-white">
    <div class="nav nav-pills gap-2 flex-wrap">
        <?php if (\App\Core\Auth::hasRole('admin', 'gestionnaire')): ?>
        <a class="nav-link rounded-pill px-3 py-2 text-muted" href="<?= $base_path ?>/etablissements">
            <i class="bi bi-buildings me-1"></i>Établissements
        </a>
        <?php endif; ?>
        <a class="nav-link rounded-pill px-3 py-2 text-muted" href="<?= $base_path ?>/classes<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-door-open me-1"></i>Classes
        </a>
        <a class="nav-link rounded-pill px-3 py-2 active bg-primary text-white" href="<?= $base_path ?>/filieres<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-diagram-3 me-1"></i>Filières & Séries
        </a>
        <a class="nav-link rounded-pill px-3 py-2 text-muted" href="<?= $base_path ?>/departements<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-building-gear me-1"></i>Départements & Services
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 52px; height: 52px;">
                <i class="bi bi-diagram-3 fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Total Filières / Séries</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 52px; height: 52px;">
                <i class="bi bi-door-open fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Classes rattachées</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total_classes'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 52px; height: 52px;">
                <i class="bi bi-mortarboard fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Étudiants inscrits</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total_etudiants'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/filieres" class="row g-2 align-items-center">
            <?php if (count($accessibleEtabs) > 1): ?>
                <div class="col-md-4">
                    <select name="etablissement_id" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">Tous les établissements accessibles</option>
                        <?php foreach ($accessibleEtabs as $etab): ?>
                            <option value="<?= $etab['id'] ?>" <?= $selectedEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="etablissement_id" value="<?= $selectedEtabId ?>">
            <?php endif; ?>

            <div class="col-md-3">
                <select name="departement_id" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="0">Tous les départements</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $selectedDeptId === (int)$dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-<?= count($accessibleEtabs) > 1 ? '4' : '7' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Rechercher par libellé, code, département..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary w-100" title="Filtrer"><i class="bi bi-funnel"></i></button>
                <?php if (!empty($search) || $selectedDeptId > 0): ?>
                    <a href="<?= $base_path ?>/filieres<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des filières -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 140px;">Code</th>
                    <th>Filière / Série</th>
                    <th>Département d'attache</th>
                    <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                        <th>Établissement</th>
                    <?php endif; ?>
                    <th class="text-center" style="width: 130px;">Classes</th>
                    <th class="text-center" style="width: 130px;">Étudiants</th>
                    <th class="text-end pe-3" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-diagram-3 display-6 d-block mb-2 text-secondary"></i>
                            Aucune filière trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $nbClasses = (int)($item['nb_classes'] ?? 0);
                            $nbEtudiants = (int)($item['nb_etudiants'] ?? 0);
                            $totalDeps = $nbClasses + $nbEtudiants;
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                    <?= htmlspecialchars($item['code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($item['libelle']) ?></div>
                                <?php if (!empty($item['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 350px;">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['departement_nom'])): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($item['departement_nom']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">— Non assigné —</span>
                                <?php endif; ?>
                            </td>
                            <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                                <td>
                                    <span class="small text-secondary"><?= htmlspecialchars($item['etablissement_nom'] ?? 'N/A') ?></span>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <a href="<?= $base_path ?>/classes?filiere_id=<?= $item['id'] ?><?= $selectedEtabId > 0 ? '&etablissement_id=' . $selectedEtabId : '' ?>" class="badge bg-light text-secondary border text-decoration-none">
                                    <i class="bi bi-door-open me-1"></i><?= $nbClasses ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-mortarboard me-1"></i><?= $nbEtudiants ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-edit-filiere"
                                            data-id="<?= $item['id'] ?>"
                                            data-code="<?= htmlspecialchars($item['code']) ?>"
                                            data-libelle="<?= htmlspecialchars($item['libelle']) ?>"
                                            data-etablissement_id="<?= $item['etablissement_id'] ?>"
                                            data-departement_id="<?= $item['departement_id'] ?? '' ?>"
                                            data-description="<?= htmlspecialchars($item['description'] ?? '') ?>"
                                            title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= $base_path ?>/filieres/<?= $item['id'] ?>/supprimer" class="d-inline" onsubmit="return confirmDeleteFiliere(event, <?= $totalDeps ?>, '<?= htmlspecialchars(addslashes($item['libelle'])) ?>')">
                                        <?= \App\Core\Csrf::inputField() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout Filière -->
<div class="modal fade" id="modalAddFiliere" tabindex="-1" aria-labelledby="modalAddFiliereLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $base_path ?>/filieres/creer">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalAddFiliereLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Nouvelle filière ou série
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" class="form-select" required>
                            <?php foreach ($accessibleEtabs as $etab): ?>
                                <option value="<?= $etab['id'] ?>" <?= $selectedEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Code / Sigle <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase" placeholder="EX: GL, D, TC" required maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Intitulé / Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" class="form-control" placeholder="EX: Génie Logiciel, Série D..." required maxlength="150">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Département d'attache <span class="text-muted small">(Optionnel)</span></label>
                        <select name="departement_id" class="form-select">
                            <option value="">-- Aucun département --</option>
                            <?php foreach ($departements as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Objectifs, débouchés, précisions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modification Filière -->
<div class="modal fade" id="modalEditFiliere" tabindex="-1" aria-labelledby="modalEditFiliereLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="formEditFiliere" action="">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEditFiliereLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Modifier la filière
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" id="editFiliereEtablissementId" class="form-select" required>
                            <?php foreach ($accessibleEtabs as $etab): ?>
                                <option value="<?= $etab['id'] ?>">
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Code / Sigle <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="editFiliereCode" class="form-control text-uppercase" required maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Intitulé / Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" id="editFiliereLibelle" class="form-control" required maxlength="150">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Département d'attache <span class="text-muted small">(Optionnel)</span></label>
                        <select name="departement_id" id="editFiliereDepartementId" class="form-select">
                            <option value="">-- Aucun département --</option>
                            <?php foreach ($departements as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="editFiliereDescription" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('modalEditFiliere'));
    const formEdit = document.getElementById('formEditFiliere');

    document.querySelectorAll('.btn-edit-filiere').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            formEdit.action = '<?= $base_path ?>/filieres/' + id + '/modifier';

            document.getElementById('editFiliereCode').value = this.dataset.code || '';
            document.getElementById('editFiliereLibelle').value = this.dataset.libelle || '';
            document.getElementById('editFiliereEtablissementId').value = this.dataset.etablissement_id || '';
            document.getElementById('editFiliereDepartementId').value = this.dataset.departement_id || '';
            document.getElementById('editFiliereDescription').value = this.dataset.description || '';

            editModal.show();
        });
    });
});

function confirmDeleteFiliere(e, depsCount, name) {
    if (depsCount > 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Suppression impossible',
                text: 'La filière "' + name + '" possède des éléments rattachés (' + depsCount + ' classes/étudiants). Veuillez d\'abord réassigner ou supprimer ces dépendances.',
                confirmButtonColor: '#0d6efd'
            });
        } else {
            alert('Impossible de supprimer "' + name + '" : cette filière contient encore ' + depsCount + ' élément(s) rattaché(s).');
        }
        e.preventDefault();
        return false;
    }

    if (typeof Swal !== 'undefined') {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Confirmer la suppression ?',
            text: 'Êtes-vous sûr de vouloir supprimer définitivement la filière "' + name + '" ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    return confirm('Êtes-vous sûr de vouloir supprimer la filière "' + name + '" ?');
}
</script>
