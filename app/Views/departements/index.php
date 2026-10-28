<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $items */
/** @var array $counts */
/** @var array $accessibleEtabs */
/** @var int $selectedEtabId */
/** @var string $filterType */
/** @var string $search */
/** @var array $parentDepts */
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Départements & Services</h2>
        <p class="text-muted small mb-0">Organisation structurelle, pôles académiques et services administratifs</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddDepartement">
            <i class="bi bi-plus-lg me-1"></i>Nouveau département / service
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
        <a class="nav-link rounded-pill px-3 py-2 text-muted" href="<?= $base_path ?>/filieres<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-diagram-3 me-1"></i>Filières & Séries
        </a>
        <a class="nav-link rounded-pill px-3 py-2 active bg-primary text-white" href="<?= $base_path ?>/departements<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-building-gear me-1"></i>Départements & Services
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 52px; height: 52px;">
                <i class="bi bi-diagram-2 fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Total Structures</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 52px; height: 52px;">
                <i class="bi bi-building fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Départements</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['departements'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 52px; height: 52px;">
                <i class="bi bi-gear-wide-connected fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Services administratifs / techniques</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['services'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres de recherche -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/departements" class="row g-2 align-items-center">
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
                <select name="type" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="" <?= empty($filterType) ? 'selected' : '' ?>>Tous les types (Dépt & Services)</option>
                    <option value="departement" <?= $filterType === 'departement' ? 'selected' : '' ?>>Départements uniquement</option>
                    <option value="service" <?= $filterType === 'service' ? 'selected' : '' ?>>Services uniquement</option>
                </select>
            </div>

            <div class="col-md-<?= count($accessibleEtabs) > 1 ? '4' : '7' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Rechercher par libellé, code..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary w-100" title="Filtrer"><i class="bi bi-funnel"></i></button>
                <?php if (!empty($search) || !empty($filterType)): ?>
                    <a href="<?= $base_path ?>/departements<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des départements & services -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 130px;">Code</th>
                    <th>Nom & Désignation</th>
                    <th style="width: 140px;">Type</th>
                    <th>Rattachement</th>
                    <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                        <th>Établissement</th>
                    <?php endif; ?>
                    <th class="text-center" style="width: 180px;">Éléments liés</th>
                    <th class="text-end pe-3" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-diagram-2 display-6 d-block mb-2 text-secondary"></i>
                            Aucun département ou service trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $isService = ($item['type'] ?? 'departement') === 'service';
                            $nbPers = (int)($item['nb_personnels'] ?? 0);
                            $nbFil = (int)($item['nb_filieres'] ?? 0);
                            $nbSousServices = (int)($item['nb_sous_services'] ?? 0);
                            $totalDeps = $nbPers + $nbFil + $nbSousServices;
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                    <?= htmlspecialchars($item['code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($item['nom']) ?></div>
                                <?php if (!empty($item['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isService): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-gear-wide me-1"></i>Service
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-building me-1"></i>Département
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['parent_nom'])): ?>
                                    <span class="text-muted small">
                                        <i class="bi bi-arrow-return-right me-1 text-primary"></i>
                                        <?= htmlspecialchars($item['parent_nom']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                                <td>
                                    <span class="small text-secondary"><?= htmlspecialchars($item['etablissement_nom'] ?? 'N/A') ?></span>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <span class="badge bg-light text-secondary border" title="Personnels rattachés">
                                        <i class="bi bi-people me-1"></i><?= $nbPers ?>
                                    </span>
                                    <?php if (!$isService): ?>
                                        <span class="badge bg-light text-secondary border" title="Filières associées">
                                            <i class="bi bi-diagram-3 me-1"></i><?= $nbFil ?>
                                        </span>
                                        <?php if ($nbSousServices > 0): ?>
                                            <span class="badge bg-light text-secondary border" title="Sous-services">
                                                <i class="bi bi-diagram-2 me-1"></i><?= $nbSousServices ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-edit-dept"
                                            data-id="<?= $item['id'] ?>"
                                            data-code="<?= htmlspecialchars($item['code']) ?>"
                                            data-nom="<?= htmlspecialchars($item['nom']) ?>"
                                            data-type="<?= htmlspecialchars($item['type'] ?? 'departement') ?>"
                                            data-etablissement_id="<?= $item['etablissement_id'] ?>"
                                            data-parent_id="<?= $item['parent_id'] ?? '' ?>"
                                            data-description="<?= htmlspecialchars($item['description'] ?? '') ?>"
                                            title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= $base_path ?>/departements/<?= $item['id'] ?>/supprimer" class="d-inline" onsubmit="return confirmDelete(event, <?= $totalDeps ?>, '<?= htmlspecialchars(addslashes($item['nom'])) ?>')">
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

<!-- Modal Ajout Département / Service -->
<div class="modal fade" id="modalAddDepartement" tabindex="-1" aria-labelledby="modalAddDepartementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $base_path ?>/departements/creer">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalAddDepartementLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Nouveau département / service
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" class="form-select" required id="addEtabSelect">
                            <?php foreach ($accessibleEtabs as $etab): ?>
                                <option value="<?= $etab['id'] ?>" <?= $selectedEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type de structure <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" id="addTypeSelect" required>
                            <option value="departement">Département (Pôle pédagogique ou d'enseignement)</option>
                            <option value="service">Service (Direction, secrétariat, comptabilité, technique)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control text-uppercase" placeholder="EX: INFO, RH" required maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nom / Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="EX: Informatique & Réseaux" required maxlength="150">
                        </div>
                    </div>

                    <div class="mb-3" id="addParentDeptContainer">
                        <label class="form-label fw-semibold">Département de rattachement <span class="text-muted small">(Optionnel)</span></label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Aucun (Structure autonome) --</option>
                            <?php foreach ($parentDepts as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nom']) ?> (<?= htmlspecialchars($p['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Utile si ce service est subordonné à un département spécifique.</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description / Précisions</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Localisation, attributions, missions..."></textarea>
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

<!-- Modal Modification Département / Service -->
<div class="modal fade" id="modalEditDepartement" tabindex="-1" aria-labelledby="modalEditDepartementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="formEditDepartement" action="">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEditDepartementLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Modifier la structure
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" id="editEtablissementId" class="form-select" required>
                            <?php foreach ($accessibleEtabs as $etab): ?>
                                <option value="<?= $etab['id'] ?>">
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type de structure <span class="text-danger">*</span></label>
                        <select name="type" id="editType" class="form-select" required>
                            <option value="departement">Département</option>
                            <option value="service">Service</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="editCode" class="form-control text-uppercase" required maxlength="50">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nom / Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="editNom" class="form-control" required maxlength="150">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Département parent <span class="text-muted small">(Optionnel)</span></label>
                        <select name="parent_id" id="editParentId" class="form-select">
                            <option value="">-- Aucun (Structure autonome) --</option>
                            <?php foreach ($parentDepts as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nom']) ?> (<?= htmlspecialchars($p['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
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
    // Remplissage dynamique du modal d'édition
    const editModal = new bootstrap.Modal(document.getElementById('modalEditDepartement'));
    const formEdit = document.getElementById('formEditDepartement');

    document.querySelectorAll('.btn-edit-dept').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            formEdit.action = '<?= $base_path ?>/departements/' + id + '/modifier';

            document.getElementById('editCode').value = this.dataset.code || '';
            document.getElementById('editNom').value = this.dataset.nom || '';
            document.getElementById('editType').value = this.dataset.type || 'departement';
            document.getElementById('editEtablissementId').value = this.dataset.etablissement_id || '';
            document.getElementById('editParentId').value = this.dataset.parent_id || '';
            document.getElementById('editDescription').value = this.dataset.description || '';

            editModal.show();
        });
    });
});

function confirmDelete(e, depsCount, name) {
    if (depsCount > 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Suppression impossible',
                text: 'La structure "' + name + '" possède des éléments rattachés (' + depsCount + ' dépendances). Vous devez réassigner ou supprimer ces éléments au préalable.',
                confirmButtonColor: '#0d6efd'
            });
        } else {
            alert('Impossible de supprimer "' + name + '" : cette structure contient encore ' + depsCount + ' élément(s) rattaché(s).');
        }
        e.preventDefault();
        return false;
    }

    if (typeof Swal !== 'undefined') {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Confirmer la suppression ?',
            text: 'Êtes-vous sûr de vouloir supprimer définitivement la structure "' + name + '" ?',
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

    return confirm('Êtes-vous sûr de vouloir supprimer la structure "' + name + '" ?');
}
</script>
