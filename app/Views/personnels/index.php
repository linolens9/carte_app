<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $personnels */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var string $search */
/** @var array $etablissements */
/** @var array $departements */
/** @var int|null $selectedEtabId */
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion du Personnel</h2>
        <p class="text-muted small mb-0">Agents, salariés, collaborateurs d'entreprises et administrations publiques</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= $base_path ?>/personnels/creer" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i>Ajouter un Collaborateur
        </a>
        <a href="<?= $base_path ?>/personnels/importer" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Importer CSV
        </a>
    </div>
</div>

<!-- Filtres et Recherche -->
<div class="card-custom p-4 mb-4">
    <form method="GET" action="<?= $base_path ?>/personnels" class="row g-3">
        <div class="<?= count($etablissements) > 1 ? 'col-md-4' : 'col-md-5' ?>">
            <label class="form-label small fw-semibold text-muted">Rechercher</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control bg-light border-start-0" 
                       placeholder="Nom, prénom, matricule, fonction, service..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>

        <?php if (count($etablissements) > 1): ?>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Organisation / Entreprise</label>
            <select name="etablissement_id" class="form-select bg-light" onchange="this.form.submit()">
                <?php if (\App\Core\Auth::hasRole('admin')): ?>
                    <option value="">Toutes les organisations</option>
                <?php endif; ?>
                <?php foreach ($etablissements as $etab): ?>
                    <option value="<?= (int)$etab['id'] ?>" <?= (int)$selectedEtabId === (int)$etab['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etab['nom']) ?> (<?= htmlspecialchars($etab['code'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="etablissement_id" value="<?= (int)$selectedEtabId ?>">
        <?php endif; ?>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Département / Direction</label>
            <select name="departement_id" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Tous les départements</option>
                <?php foreach ($departements as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>" <?= ((string)($filters['departement_id'] ?? '') === (string)$dept['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['nom']) ?> (<?= htmlspecialchars($dept['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Statut professionnel</label>
            <select name="statut" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="en_poste" <?= ($filters['statut'] ?? '') === 'en_poste' ? 'selected' : '' ?>>En poste</option>
                <option value="suspendu" <?= ($filters['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                <option value="parti" <?= ($filters['statut'] ?? '') === 'parti' ? 'selected' : '' ?>>Parti / Ancien</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Statut Photo</label>
            <select name="statut_photo" class="form-select bg-light" onchange="this.form.submit()">
                <option value="">Toutes</option>
                <option value="avec" <?= ($filters['statut_photo'] ?? '') === 'avec' ? 'selected' : '' ?>>Avec photo</option>
                <option value="sans" <?= ($filters['statut_photo'] ?? '') === 'sans' ? 'selected' : '' ?>>Sans photo</option>
            </select>
        </div>

        <div class="col-md-9 d-flex align-items-end gap-2 justify-content-end">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Filtrer</button>
            <a href="<?= $base_path ?>/personnels" class="btn btn-light border rounded-pill px-3" title="Réinitialiser">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau du personnel -->
<div class="card-custom overflow-hidden">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-person-badge me-2 text-primary"></i>Personnel enregistré
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2"><?= $total ?> au total</span>
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th style="width: 60px;">Photo</th>
                    <th>N° Professionnel</th>
                    <th>Nom & Prénom(s)</th>
                    <th>Fonction & Service</th>
                    <th>Structure & Département</th>
                    <th>Contact</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personnels)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-person-badge fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Aucun collaborateur trouvé pour ces critères.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($personnels as $pers): ?>
                        <?php 
                        $hasPhoto = !empty($pers['photo']) && (file_exists(public_path($pers['photo'])) || str_starts_with($pers['photo'], 'uploads/'));
                        $photoSrc = $hasPhoto ? asset_url($pers['photo']) : null;
                        ?>
                        <tr>
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
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($pers['numero_professionnel']) ?></span>
                            </td>
                            <td>
                                <a href="<?= $base_path ?>/personnels/<?= (int)$pers['id'] ?>" class="text-dark fw-bold text-decoration-none d-block">
                                    <?= htmlspecialchars($pers['nom'] . ' ' . $pers['prenom']) ?>
                                </a>
                                <?php if (!empty($pers['date_embauche'])): ?>
                                    <span class="text-muted small">Depuis le <?= date('d/m/Y', strtotime($pers['date_embauche'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($pers['fonction']) ?></div>
                                <?php if (!empty($pers['service'])): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($pers['service']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($pers['etablissement_nom']) ?>">
                                    <?= htmlspecialchars($pers['etablissement_nom']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 220px;">
                                    <?= htmlspecialchars($pers['departement_nom'] ?? 'Direction Générale') ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($pers['email'])): ?>
                                    <div class="small text-truncate" style="max-width: 170px;">
                                        <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($pers['email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($pers['telephone'])): ?>
                                    <div class="small text-muted">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($pers['telephone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pers['statut'] === 'en_poste'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">En poste</span>
                                <?php elseif ($pers['statut'] === 'suspendu'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Suspendu</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">Parti</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-2" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/personnels/<?= (int)$pers['id'] ?>">
                                                <i class="bi bi-eye me-2 text-primary"></i>Fiche & Badge
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/personnels/<?= (int)$pers['id'] ?>/modifier">
                                                <i class="bi bi-pencil me-2 text-info"></i>Modifier
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= $base_path ?>/impressions?personnel_id=<?= (int)$pers['id'] ?>">
                                                <i class="bi bi-printer me-2 text-success"></i>Imprimer le badge
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="<?= $base_path ?>/personnels/<?= (int)$pers['id'] ?>/supprimer" method="POST"
                                                  onsubmit="return confirm('Supprimer définitivement cet agent ?');">
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
