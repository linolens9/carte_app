<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etudiants */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var string $search */
/** @var array $etablissements */
/** @var array $filieres */
/** @var array $classes */
/** @var int|null $selectedEtabId */
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion des Étudiants</h2>
        <p class="text-muted small mb-0">Effectifs de l'enseignement supérieur, facultés, filières et cycles LMD</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= $base_path ?>/etudiants/creer" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i>Inscrire un Étudiant
        </a>
        <a href="<?= $base_path ?>/etudiants/importer" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Importer CSV
        </a>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="btnOpenPromoteModal" disabled>
            <i class="bi bi-mortarboard me-1"></i>Réinscrire / Promouvoir le lot (<span id="selectedCount">0</span>)
        </button>
    </div>
</div>

<!-- Barre de recherche & Filtres avancés -->
<div class="card-custom p-4 mb-4">
    <form method="GET" action="<?= $base_path ?>/etudiants" class="row g-3">
        <!-- Recherche textuelle -->
        <div class="<?= count($etablissements) > 1 ? 'col-md-4' : 'col-md-5' ?>">
            <label class="form-label small fw-semibold text-muted">Rechercher</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control bg-light border-start-0" 
                       placeholder="Nom, prénom, matricule, email..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>

        <!-- Filtre Établissement -->
        <?php if (count($etablissements) > 1): ?>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Établissement</label>
            <select name="etablissement_id" class="form-select bg-light" onchange="this.form.submit()">
                <?php if (\App\Core\Auth::hasRole('admin')): ?>
                    <option value="">Tous les établissements</option>
                <?php endif; ?>
                <?php foreach ($etablissements as $etab): ?>
                    <option value="<?= (int)$etab['id'] ?>" <?= (int)$selectedEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etab['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="etablissement_id" value="<?= (int)$selectedEtabId ?>">
        <?php endif; ?>

        <!-- Filtre Filière -->
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Filière / Département</label>
            <select name="filiere_id" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Toutes les filières</option>
                <?php foreach ($filieres as $fil): ?>
                    <option value="<?= (int)$fil['id'] ?>" <?= ((string)($filters['filiere_id'] ?? '') === (string)$fil['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fil['libelle']) ?> (<?= htmlspecialchars($fil['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtre Niveau -->
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Niveau / Cycle</label>
            <select name="niveau" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Tous</option>
                <?php 
                $niveauxList = ['Licence 1 (L1)', 'Licence 2 (L2)', 'Licence 3 (L3)', 'Master 1 (M1)', 'Master 2 (M2)', 'Doctorat'];
                foreach ($niveauxList as $niv): 
                ?>
                    <option value="<?= $niv ?>" <?= ($filters['niveau'] ?? '') === $niv ? 'selected' : '' ?>><?= $niv ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Ligne 2 de filtres -->
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Année Universitaire</label>
            <input type="text" name="annee_scolaire" class="form-control bg-light" 
                   placeholder="Ex: 2026-2027" value="<?= htmlspecialchars($filters['annee_scolaire'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Statut Photo</label>
            <select name="statut_photo" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Toutes</option>
                <option value="avec" <?= ($filters['statut_photo'] ?? '') === 'avec' ? 'selected' : '' ?>>Avec photo</option>
                <option value="sans" <?= ($filters['statut_photo'] ?? '') === 'sans' ? 'selected' : '' ?>>Sans photo</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Statut</label>
            <select name="statut" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="actif" <?= ($filters['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="diplome" <?= ($filters['statut'] ?? '') === 'diplome' ? 'selected' : '' ?>>Diplômé</option>
                <option value="archive" <?= ($filters['statut'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Sexe</label>
            <select name="sexe" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="M" <?= ($filters['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                <option value="F" <?= ($filters['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1">Filtrer</button>
            <a href="<?= $base_path ?>/etudiants" class="btn btn-light border rounded-pill px-3" title="Réinitialiser">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau des Étudiants -->
<div class="card-custom overflow-hidden">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-mortarboard me-2 text-primary"></i>Étudiants inscrits
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2"><?= $total ?> au total</span>
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th style="width: 40px;" class="text-center">
                        <input type="checkbox" class="form-check-input" id="checkAll">
                    </th>
                    <th style="width: 60px;">Photo</th>
                    <th>Matricule</th>
                    <th>Nom & Prénom(s)</th>
                    <th>Filière & Département</th>
                    <th>Niveau / Année</th>
                    <th>Contact</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($etudiants)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-mortarboard fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Aucun étudiant trouvé avec les critères sélectionnés.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($etudiants as $etu): ?>
                        <?php 
                        $hasPhoto = !empty($etu['photo']) && (file_exists(public_path($etu['photo'])) || str_starts_with($etu['photo'], 'uploads/'));
                        $photoSrc = $hasPhoto ? asset_url($etu['photo']) : null;
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input item-checkbox" value="<?= (int)$etu['id'] ?>">
                            </td>
                            <td>
                                <div class="position-relative" style="width: 44px; height: 58px; border-radius: 8px; overflow: hidden; background: #e2e8f0; border: 1px solid #cbd5e1;">
                                    <?php if ($photoSrc): ?>
                                        <img src="<?= $photoSrc ?>" alt="Photo" class="w-100 h-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted" title="Sans photo">
                                            <i class="bi bi-person-fill fs-5"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($etu['matricule']) ?></span>
                            </td>
                            <td>
                                <a href="<?= $base_path ?>/etudiants/<?= (int)$etu['id'] ?>" class="text-dark fw-bold text-decoration-none d-block">
                                    <?= htmlspecialchars($etu['nom'] . ' ' . $etu['prenom']) ?>
                                </a>
                                <span class="text-muted small">
                                    <?= $etu['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?> &bull; 
                                    <?= date('d/m/Y', strtotime($etu['date_naissance'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($etu['filiere_libelle']) ?>">
                                    <?= htmlspecialchars($etu['filiere_libelle']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 220px;">
                                    <?= htmlspecialchars($etu['departement_nom'] ?? $etu['etablissement_nom']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($etu['niveau']) ?></span>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($etu['annee_scolaire']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($etu['email'])): ?>
                                    <div class="small text-truncate" style="max-width: 160px;">
                                        <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($etu['email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($etu['telephone'])): ?>
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($etu['telephone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($etu['statut'] === 'actif'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Inscrit</span>
                                <?php elseif ($etu['statut'] === 'diplome'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Diplômé</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">Archivé</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-2" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/etudiants/<?= (int)$etu['id'] ?>">
                                                <i class="bi bi-eye me-2 text-primary"></i>Fiche & Carte
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/etudiants/<?= (int)$etu['id'] ?>/modifier">
                                                <i class="bi bi-pencil me-2 text-info"></i>Modifier
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/impressions?etudiant_id=<?= (int)$etu['id'] ?>">
                                                <i class="bi bi-printer me-2 text-success"></i>Imprimer la carte
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="<?= $base_path ?>/etudiants/<?= (int)$etu['id'] ?>/supprimer" method="POST"
                                                  onsubmit="return confirm('Supprimer définitivement cet étudiant ?');">
                                                <?= $csrf_input ?>
                                                <button type="submit" class="dropdown-item small text-danger">
                                                    <i class="bi bi-trash me-2"></i>Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small">Page <?= $page ?> sur <?= $totalPages ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Promotion / Réinscription par Lot -->
<div class="modal fade" id="modalBatchPromote" tabindex="-1" aria-labelledby="modalBatchPromoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalBatchPromoteLabel">
                    <i class="bi bi-mortarboard me-2 text-primary"></i>Promotion / Réinscription par lot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="<?= $base_path ?>/etudiants/promouvoir" method="POST" id="formBatchPromote">
                <?= $csrf_input ?>
                <div class="modal-body pt-3">
                    <p class="text-secondary small mb-3">
                        Vous êtes sur le point de faire progresser <strong id="modalSelectedCount">0</strong> étudiant(s) sélectionné(s) vers une nouvelle filière ou cycle universitaire.
                    </p>

                    <!-- IDs cachés -->
                    <div id="hiddenIdsContainer"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Filière cible <span class="text-danger">*</span></label>
                        <select name="target_filiere_id" class="form-select" required>
                            <option value="">-- Choisir la filière --</option>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= (int)$f['id'] ?>">
                                    <?= htmlspecialchars($f['libelle']) ?> (<?= htmlspecialchars($f['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nouveau Niveau <span class="text-danger">*</span></label>
                            <select name="target_niveau" class="form-select" required>
                                <option value="Licence 1 (L1)">Licence 1 (L1)</option>
                                <option value="Licence 2 (L2)">Licence 2 (L2)</option>
                                <option value="Licence 3 (L3)">Licence 3 (L3)</option>
                                <option value="Master 1 (M1)">Master 1 (M1)</option>
                                <option value="Master 2 (M2)">Master 2 (M2)</option>
                                <option value="Doctorat">Doctorat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nouvelle Année <span class="text-danger">*</span></label>
                            <input type="text" name="target_annee" class="form-control" value="2027-2028" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Groupe / TD (optionnel)</label>
                        <select name="target_classe_id" class="form-select">
                            <option value="">-- Aucun groupe spécifique --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i>Appliquer la promotion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const btnOpenPromoteModal = document.getElementById('btnOpenPromoteModal');
    const selectedCount = document.getElementById('selectedCount');
    const modalSelectedCount = document.getElementById('modalSelectedCount');
    const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
    const modalBatchPromote = new bootstrap.Modal(document.getElementById('modalBatchPromote'));

    function updateSelection() {
        const checked = Array.from(checkboxes).filter(c => c.checked);
        const count = checked.length;
        selectedCount.textContent = count;
        modalSelectedCount.textContent = count;
        btnOpenPromoteModal.disabled = count === 0;

        hiddenIdsContainer.innerHTML = '';
        checked.forEach(c => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'etudiant_ids[]';
            input.value = c.value;
            hiddenIdsContainer.appendChild(input);
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(c => c.checked = checkAll.checked);
            updateSelection();
        });
    }

    checkboxes.forEach(c => {
        c.addEventListener('change', function () {
            updateSelection();
            if (checkAll) {
                checkAll.checked = Array.from(checkboxes).every(cb => cb.checked);
            }
        });
    });

    btnOpenPromoteModal.addEventListener('click', function () {
        modalBatchPromote.show();
    });
});
</script>
