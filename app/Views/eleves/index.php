<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $eleves */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var string $search */
/** @var array $etablissements */
/** @var array $classes */
/** @var int|null $selectedEtabId */
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion des Élèves</h2>
        <p class="text-muted small mb-0">Effectifs scolaires, fiches individuelles et statut des cartes d'identité</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_path ?>/eleves/importer" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold small">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importation CSV
        </a>
        <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold small" data-bs-toggle="modal" data-bs-target="#modalPromote">
            <i class="bi bi-arrow-up-right-circle me-1"></i>Promotion de classe
        </button>
        <a href="<?= $base_path ?>/eleves/creer" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-person-plus me-1"></i>Inscrire un élève
        </a>
    </div>
</div>

<!-- Filtres avancés -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/eleves" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control bg-light" placeholder="Rechercher nom, prénom, matricule..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <?php if (count($etablissements) > 1): ?>
                <div class="col-md-2">
                    <select name="etablissement_id" class="form-select bg-light" onchange="this.form.submit()">
                        <option value="">Tous les établissements</option>
                        <?php foreach ($etablissements as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($selectedEtabId == $e['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nom']) ?> (<?= htmlspecialchars($e['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif (!empty($etablissements)): ?>
                <input type="hidden" name="etablissement_id" value="<?= (int)$etablissements[0]['id'] ?>">
            <?php endif; ?>
            <div class="col-md-2">
                <select name="classe_id" class="form-select bg-light">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (($filters['classe_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut_photo" class="form-select bg-light">
                    <option value="">Photos : Tous</option>
                    <option value="avec" <?= (($filters['statut_photo'] ?? '') === 'avec') ? 'selected' : '' ?>>Avec photo</option>
                    <option value="sans" <?= (($filters['statut_photo'] ?? '') === 'sans') ? 'selected' : '' ?>>Sans photo</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sexe" class="form-select bg-light">
                    <option value="">Sexe : Tous</option>
                    <option value="M" <?= (($filters['sexe'] ?? '') === 'M') ? 'selected' : '' ?>>Masculin (M)</option>
                    <option value="F" <?= (($filters['sexe'] ?? '') === 'F') ? 'selected' : '' ?>>Féminin (F)</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-dark d-flex align-items-center justify-content-center gap-1" title="Filtrer">
                    <i class="bi bi-filter"></i>
                    <span class="d-md-none fw-semibold">Filtrer</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Table des élèves -->
<div class="card-custom">
    <div class="card-header-custom">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold mb-0">Liste des élèves inscrits</h6>
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= number_format($total) ?> élève(s)</span>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" id="btnSelectAll">
                <i class="bi bi-check2-square me-1"></i>Tout cocher
            </button>
        </div>
    </div>

    <form id="formBulkPromotion" action="<?= $base_path ?>/eleves/promouvoir" method="POST">
        <?= $csrf_input ?>

        <div class="p-0">
            <?php if (empty($eleves)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-backpack text-muted fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold">Aucun élève trouvé</h5>
                    <p class="text-muted small">Ajustez vos filtres de recherche ou inscrivez un nouvel élève.</p>
                    <a href="<?= $base_path ?>/eleves/creer" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                        Inscrire un premier élève
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 60px;">Photo</th>
                                <th>Matricule</th>
                                <th>Identité (Nom & Prénom)</th>
                                <th>Sexe / Âge</th>
                                <th>Classe & Établissement</th>
                                <th>Statut Carte</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves as $el): ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input eleve-checkbox" type="checkbox" name="eleve_ids[]" value="<?= $el['id'] ?>">
                                    </td>
                                    <td>
                                        <?php if (!empty($el['photo'])): ?>
                                            <img src="<?= $base_path ?>/<?= htmlspecialchars($el['photo']) ?>" alt="Photo"
                                                 class="rounded-3 border" style="width: 44px; height: 58px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-muted"
                                                 style="width: 44px; height: 58px;" title="Photo manquante">
                                                <i class="bi bi-person fs-5"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= $base_path ?>/eleves/<?= $el['id'] ?>" class="font-monospace fw-bold text-primary text-decoration-none">
                                            <?= htmlspecialchars($el['matricule']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($el['nom'] . ' ' . $el['prenom']) ?></div>
                                        <small class="text-muted">Né(e) le <?= date('d/m/Y', strtotime($el['date_naissance'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $el['sexe'] === 'M' ? 'badge-subtle-primary' : 'badge-subtle-danger' ?>">
                                            <?= $el['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
                                        </span>
                                        <?php
                                            $age = (new DateTime($el['date_naissance']))->diff(new DateTime())->y;
                                        ?>
                                        <small class="text-muted d-block"><?= $age ?> ans</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-semibold"><?= htmlspecialchars($el['classe_libelle']) ?></span>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($el['etab_nom']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($el['statut_carte'] === 'active'): ?>
                                            <span class="badge badge-subtle-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                        <?php elseif ($el['statut_carte'] === 'validee'): ?>
                                            <span class="badge badge-subtle-primary">Validée</span>
                                        <?php elseif ($el['statut_carte'] === 'expiree'): ?>
                                            <span class="badge badge-subtle-danger">Expirée</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Non émise</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="<?= $base_path ?>/eleves/<?= $el['id'] ?>">
                                                        <i class="bi bi-eye me-2 text-primary"></i>Fiche complète
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="<?= $base_path ?>/eleves/<?= $el['id'] ?>/modifier">
                                                        <i class="bi bi-pencil me-2 text-warning"></i>Modifier
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="<?= $base_path ?>/eleves/<?= $el['id'] ?>/supprimer" method="POST" class="d-inline">
                                                        <?= $csrf_input ?>
                                                        <button type="submit" class="dropdown-item text-danger btn-confirm-delete"
                                                                data-message="Supprimer cet élève ?">
                                                            <i class="bi bi-trash me-2"></i>Supprimer
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="p-3 d-flex justify-content-between align-items-center border-top">
                        <small class="text-muted">Page <?= $page ?> sur <?= $totalPages ?></small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $base_path ?>/eleves?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Modal Promotion en lot -->
        <div class="modal fade" id="modalPromote" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Promotion de classe en lot</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Cette action fera passer tous les élèves cochés vers la classe cible et l'année scolaire indiquée.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Classe de destination <span class="text-danger">*</span></label>
                            <select name="target_classe_id" class="form-select" required>
                                <option value="">Sélectionner la classe supérieure</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nouvelle Année Scolaire <span class="text-danger">*</span></label>
                            <input type="text" name="target_annee" class="form-control" value="<?= date('Y') . '-' . (date('Y') + 1) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Appliquer la promotion</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const btnSelectAll = document.getElementById('btnSelectAll');
    let allSelected = false;
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', () => {
            allSelected = !allSelected;
            document.querySelectorAll('.eleve-checkbox').forEach(cb => cb.checked = allSelected);
            btnSelectAll.innerHTML = allSelected
                ? '<i class="bi bi-dash-square me-1"></i>Tout décocher'
                : '<i class="bi bi-check2-square me-1"></i>Tout cocher';
        });
    }
</script>
