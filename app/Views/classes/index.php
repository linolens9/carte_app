<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $items */
/** @var array $counts */
/** @var array $accessibleEtabs */
/** @var int $selectedEtabId */
/** @var int $selectedFiliereId */
/** @var array $filieres */
/** @var string $niveau */
/** @var string $annee */
/** @var array $niveauxList */
/** @var array $anneesList */
/** @var string $search */

$defaultAnnee = date('n') >= 8 ? date('Y') . '-' . (date('Y') + 1) : (date('Y') - 1) . '-' . date('Y');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion des Classes</h2>
        <p class="text-muted small mb-0">Divisions pédagogiques, promotions académiques et niveaux d'enseignement</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddClasse">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle classe
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
        <a class="nav-link rounded-pill px-3 py-2 active bg-primary text-white" href="<?= $base_path ?>/classes<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
            <i class="bi bi-door-open me-1"></i>Classes
        </a>
        <a class="nav-link rounded-pill px-3 py-2 text-muted" href="<?= $base_path ?>/filieres<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>">
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
                <i class="bi bi-door-open fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Total Classes répertoriées</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 52px; height: 52px;">
                <i class="bi bi-backpack fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Élèves inscrits</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($counts['total_eleves'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card-custom p-3 bg-white h-100 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 52px; height: 52px;">
                <i class="bi bi-person-check fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Moyenne par classe</div>
                <div class="fs-4 fw-bold text-dark">
                    <?= !empty($counts['total']) ? round((int)$counts['total_eleves'] / (int)$counts['total'], 1) : 0 ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/classes" class="row g-2 align-items-center">
            <?php if (count($accessibleEtabs) > 1): ?>
                <div class="col-md-3">
                    <select name="etablissement_id" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">Tous les établissements</option>
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

            <?php if (!empty($filieres)): ?>
                <div class="col-md-3">
                    <select name="filiere_id" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="0">Toutes les filières</option>
                        <?php foreach ($filieres as $fil): ?>
                            <option value="<?= $fil['id'] ?>" <?= $selectedFiliereId === (int)$fil['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fil['libelle']) ?> (<?= htmlspecialchars($fil['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <select name="annee" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="">Toutes les années</option>
                    <?php foreach ($anneesList as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= $annee === $a ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-<?= (count($accessibleEtabs) > 1 && !empty($filieres)) ? '3' : '4' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Rechercher par libellé..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary w-100" title="Filtrer"><i class="bi bi-funnel"></i></button>
                <?php if (!empty($search) || $selectedFiliereId > 0 || !empty($annee) || !empty($niveau)): ?>
                    <a href="<?= $base_path ?>/classes<?= $selectedEtabId > 0 ? '?etablissement_id=' . $selectedEtabId : '' ?>" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des classes -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Classe / Division</th>
                    <th style="width: 140px;">Niveau</th>
                    <th>Filière / Spécialité</th>
                    <th style="width: 140px;">Année scolaire</th>
                    <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                        <th>Établissement</th>
                    <?php endif; ?>
                    <th class="text-center" style="width: 150px;">Effectif</th>
                    <th class="text-end pe-3" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-door-open display-6 d-block mb-2 text-secondary"></i>
                            Aucune classe trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $nbEleves = (int)($item['nb_eleves'] ?? 0);
                            $effMax = !empty($item['effectif_max']) ? (int)$item['effectif_max'] : null;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['libelle']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <?= htmlspecialchars($item['niveau'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($item['filiere_libelle'])): ?>
                                    <span class="text-dark small">
                                        <i class="bi bi-diagram-3 me-1 text-primary"></i>
                                        <?= htmlspecialchars($item['filiere_libelle']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Tronc commun / Standard</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                    <?= htmlspecialchars($item['annee_scolaire']) ?>
                                </span>
                            </td>
                            <?php if (count($accessibleEtabs) > 1 && $selectedEtabId === 0): ?>
                                <td>
                                    <span class="small text-secondary"><?= htmlspecialchars($item['etablissement_nom'] ?? 'N/A') ?></span>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <a href="<?= $base_path ?>/eleves?classe_id=<?= $item['id'] ?><?= $selectedEtabId > 0 ? '&etablissement_id=' . $selectedEtabId : '' ?>" class="badge bg-light text-secondary border text-decoration-none px-3 py-2" title="Voir les élèves inscrits">
                                    <i class="bi bi-backpack me-1"></i>
                                    <strong><?= $nbEleves ?></strong>
                                    <?php if ($effMax): ?>
                                        <span class="text-muted">/ <?= $effMax ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-edit-classe"
                                            data-id="<?= $item['id'] ?>"
                                            data-libelle="<?= htmlspecialchars($item['libelle']) ?>"
                                            data-niveau="<?= htmlspecialchars($item['niveau'] ?? '') ?>"
                                            data-etablissement_id="<?= $item['etablissement_id'] ?>"
                                            data-filiere_id="<?= $item['filiere_id'] ?? '' ?>"
                                            data-annee_scolaire="<?= htmlspecialchars($item['annee_scolaire']) ?>"
                                            data-effectif_max="<?= $item['effectif_max'] ?? '' ?>"
                                            title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= $base_path ?>/classes/<?= $item['id'] ?>/supprimer" class="d-inline" onsubmit="return confirmDeleteClasse(event, <?= $nbEleves ?>, '<?= htmlspecialchars(addslashes($item['libelle'])) ?>')">
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

<!-- Modal Ajout Classe -->
<div class="modal fade" id="modalAddClasse" tabindex="-1" aria-labelledby="modalAddClasseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $base_path ?>/classes/creer">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalAddClasseLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Nouvelle classe / promotion
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
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Libellé de la classe <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" class="form-control" placeholder="EX: 6ème A, Tle S2, L1..." required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Niveau <span class="text-danger">*</span></label>
                            <input type="text" name="niveau" class="form-control" placeholder="EX: 6ème, L1, Master 1" required maxlength="50">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Filière / Série <span class="text-muted small">(Optionnel)</span></label>
                        <select name="filiere_id" class="form-select">
                            <option value="">-- Tronc commun / Aucune filière --</option>
                            <?php foreach ($filieres as $fil): ?>
                                <option value="<?= $fil['id'] ?>" <?= $selectedFiliereId === (int)$fil['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($fil['libelle']) ?> (<?= htmlspecialchars($fil['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Année scolaire <span class="text-danger">*</span></label>
                            <input type="text" name="annee_scolaire" class="form-control" value="<?= htmlspecialchars($defaultAnnee) ?>" placeholder="EX: 2024-2025" required maxlength="20">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Effectif max</label>
                            <input type="number" name="effectif_max" class="form-control" placeholder="EX: 45" min="1" max="1000">
                        </div>
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

<!-- Modal Modification Classe -->
<div class="modal fade" id="modalEditClasse" tabindex="-1" aria-labelledby="modalEditClasseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="formEditClasse" action="">
                <?= \App\Core\Csrf::inputField() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEditClasseLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Modifier la classe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Établissement <span class="text-danger">*</span></label>
                        <select name="etablissement_id" id="editClasseEtablissementId" class="form-select" required>
                            <?php foreach ($accessibleEtabs as $etab): ?>
                                <option value="<?= $etab['id'] ?>">
                                    <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Libellé de la classe <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" id="editClasseLibelle" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Niveau <span class="text-danger">*</span></label>
                            <input type="text" name="niveau" id="editClasseNiveau" class="form-control" required maxlength="50">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Filière / Série <span class="text-muted small">(Optionnel)</span></label>
                        <select name="filiere_id" id="editClasseFiliereId" class="form-select">
                            <option value="">-- Tronc commun / Aucune filière --</option>
                            <?php foreach ($filieres as $fil): ?>
                                <option value="<?= $fil['id'] ?>">
                                    <?= htmlspecialchars($fil['libelle']) ?> (<?= htmlspecialchars($fil['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Année scolaire <span class="text-danger">*</span></label>
                            <input type="text" name="annee_scolaire" id="editClasseAnnee" class="form-control" required maxlength="20">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Effectif max</label>
                            <input type="number" name="effectif_max" id="editClasseEffectifMax" class="form-control" min="1" max="1000">
                        </div>
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
    const editModal = new bootstrap.Modal(document.getElementById('modalEditClasse'));
    const formEdit = document.getElementById('formEditClasse');

    document.querySelectorAll('.btn-edit-classe').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            formEdit.action = '<?= $base_path ?>/classes/' + id + '/modifier';

            document.getElementById('editClasseLibelle').value = this.dataset.libelle || '';
            document.getElementById('editClasseNiveau').value = this.dataset.niveau || '';
            document.getElementById('editClasseEtablissementId').value = this.dataset.etablissement_id || '';
            document.getElementById('editClasseFiliereId').value = this.dataset.filiere_id || '';
            document.getElementById('editClasseAnnee').value = this.dataset.annee_scolaire || '';
            document.getElementById('editClasseEffectifMax').value = this.dataset.effectif_max || '';

            editModal.show();
        });
    });
});

function confirmDeleteClasse(e, elevesCount, name) {
    if (elevesCount > 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Suppression impossible',
                text: 'La classe "' + name + '" compte encore ' + elevesCount + ' élève(s) inscrit(s). Vous devez réassigner ou retirer ces élèves avant de pouvoir supprimer cette classe.',
                confirmButtonColor: '#0d6efd'
            });
        } else {
            alert('Impossible de supprimer "' + name + '" : cette classe contient encore ' + elevesCount + ' élève(s).');
        }
        e.preventDefault();
        return false;
    }

    if (typeof Swal !== 'undefined') {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Confirmer la suppression ?',
            text: 'Êtes-vous sûr de vouloir supprimer définitivement la classe "' + name + '" ?',
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

    return confirm('Êtes-vous sûr de vouloir supprimer la classe "' + name + '" ?');
}
</script>
