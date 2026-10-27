<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etab */
/** @var array $stats */
/** @var array $departements */
/** @var array $filieres */
/** @var array $classes */
?>

<!-- En-tête de la fiche Établissement -->
<div class="card-custom mb-4 overflow-hidden">
    <div style="height: 12px; background: <?= htmlspecialchars($etab['couleur_principale'] ?? '#2563eb') ?>;"></div>
    <div class="p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-4">
            <?php if (!empty($etab['logo'])): ?>
                <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['logo']) ?>" alt="Logo" class="rounded-3 border p-2 bg-white" style="width: 72px; height: 72px; object-fit: contain;">
            <?php else: ?>
                <div class="rounded-3 text-white fw-bold d-flex align-items-center justify-content-center"
                     style="width: 72px; height: 72px; font-size: 1.75rem; background: <?= htmlspecialchars($etab['couleur_principale'] ?? '#2563eb') ?>;">
                    <?= strtoupper(substr($etab['code'], 0, 2)) ?>
                </div>
            <?php endif; ?>

            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($etab['nom']) ?></h3>
                    <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($etab['code']) ?></span>
                    <?php $isArchived = ($etab['statut'] === 'archive') || !empty($etab['deleted_at']); ?>
                    <?php if ($isArchived): ?>
                        <span class="badge badge-subtle-danger"><i class="bi bi-archive me-1"></i>Archivé</span>
                    <?php elseif ($etab['statut'] === 'actif'): ?>
                        <span class="badge badge-subtle-success">Actif</span>
                    <?php elseif ($etab['statut'] === 'suspendu'): ?>
                        <span class="badge badge-subtle-warning">Suspendu</span>
                    <?php endif; ?>
                </div>
                <div class="text-muted small mt-1">
                    <span class="badge badge-subtle-primary me-2"><?= ucfirst($etab['categorie']) ?></span>
                    <span><?= str_replace('_', ' ', ucfirst($etab['type'])) ?></span>
                    <?php if (!empty($etab['devise'])): ?>
                        <span class="ms-2 fst-italic">« <?= htmlspecialchars($etab['devise']) ?> »</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($isArchived): ?>
                <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/restaurer" method="POST" class="d-inline">
                    <?= $csrf_input ?>
                    <button type="submit" class="btn btn-success rounded-pill px-3" onclick="return confirm('Restaurer cet établissement et le remettre en service ?');">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Désarchiver / Restaurer
                    </button>
                </form>
            <?php else: ?>
                <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/supprimer" method="POST" class="d-inline">
                    <?= $csrf_input ?>
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3 btn-confirm-delete"
                            data-message="Archiver cet établissement ? L'action sera refusée si des cartes actives existent.">
                        <i class="bi bi-archive me-1"></i>Archiver
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/modifier" class="btn btn-warning rounded-pill px-3">
                <i class="bi bi-pencil me-1"></i>Modifier
            </a>
            <a href="<?= $base_path ?>/etablissements" class="btn btn-light border rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
</div>

<?php if ($isArchived): ?>
    <div class="alert alert-secondary d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-archive-fill fs-3 text-secondary"></i>
            <div>
                <strong class="d-block">Cet établissement est actuellement archivé (hors service)</strong>
                <span class="small text-muted">Ses données sont conservées en lecture seule. Vous pouvez le restaurer à tout moment pour le réactiver.</span>
            </div>
        </div>
        <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/restaurer" method="POST" class="ms-3">
            <?= $csrf_input ?>
            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" onclick="return confirm('Restaurer cet établissement et le remettre en service ?');">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurer
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Statistiques de l'établissement -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="kpi-card p-3">
            <div>
                <div class="kpi-value fs-4 text-primary"><?= (int)($stats['total_eleves'] + $stats['total_etudiants']) ?></div>
                <div class="kpi-label">Élèves / Étudiants</div>
            </div>
            <div class="kpi-icon bg-primary bg-opacity-10 text-primary" style="width: 44px; height: 44px; font-size: 1.25rem;">
                <i class="bi bi-backpack"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card p-3">
            <div>
                <div class="kpi-value fs-4 text-secondary"><?= (int)$stats['total_personnels'] ?></div>
                <div class="kpi-label">Personnels</div>
            </div>
            <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary" style="width: 44px; height: 44px; font-size: 1.25rem;">
                <i class="bi bi-person-badge"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card p-3">
            <div>
                <div class="kpi-value fs-4 text-dark"><?= (int)$stats['total_cartes'] ?></div>
                <div class="kpi-label">Total Cartes</div>
            </div>
            <div class="kpi-icon bg-dark bg-opacity-10 text-dark" style="width: 44px; height: 44px; font-size: 1.25rem;">
                <i class="bi bi-card-heading"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card p-3">
            <div>
                <div class="kpi-value fs-4 text-success"><?= (int)$stats['cartes_actives'] ?></div>
                <div class="kpi-label">Cartes Actives</div>
            </div>
            <div class="kpi-icon bg-success bg-opacity-10 text-success" style="width: 44px; height: 44px; font-size: 1.25rem;">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Onglets Structure et Détails -->
<div class="card-custom">
    <div class="card-header-custom p-0 border-bottom">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="etabTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="classes-tab" data-bs-toggle="tab" data-bs-target="#tab-classes">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Classes (<?= count($classes) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="filieres-tab" data-bs-toggle="tab" data-bs-target="#tab-filieres">
                    <i class="bi bi-diagram-3 me-1"></i>Filières (<?= count($filieres) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="dept-tab" data-bs-toggle="tab" data-bs-target="#tab-dept">
                    <i class="bi bi-diagram-2 me-1"></i>Départements & Services (<?= count($departements) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#tab-infos">
                    <i class="bi bi-info-circle me-1"></i>Coordonnées & Informations
                </button>
            </li>
        </ul>
    </div>

    <div class="p-4 tab-content">
        <!-- 1. Onglet Classes -->
        <div class="tab-pane fade show active" id="tab-classes">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Classes et Niveaux</h6>
                <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddClasse">
                    <i class="bi bi-plus me-1"></i>Ajouter une classe
                </button>
            </div>

            <?php if (empty($classes)): ?>
                <div class="text-center py-4 text-muted small">Aucune classe n'est encore configurée pour cet établissement.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Libellé</th>
                                <th>Niveau</th>
                                <th>Filière</th>
                                <th>Année Scolaire</th>
                                <th>Effectif actuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $cl): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($cl['libelle']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cl['niveau']) ?></span></td>
                                    <td><?= htmlspecialchars($cl['filiere_libelle'] ?? 'Générale') ?></td>
                                    <td><?= htmlspecialchars($cl['annee_scolaire']) ?></td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <?= (int)$cl['nb_eleves'] ?> <?= $cl['effectif_max'] ? '/ ' . $cl['effectif_max'] : '' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Onglet Filières -->
        <div class="tab-pane fade" id="tab-filieres">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Filières et Séries</h6>
                <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddFiliere">
                    <i class="bi bi-plus me-1"></i>Ajouter une filière
                </button>
            </div>

            <?php if (empty($filieres)): ?>
                <div class="text-center py-4 text-muted small">Aucune filière configurée.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Intitulé de la filière</th>
                                <th>Département de rattachement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filieres as $f): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($f['code']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($f['libelle']) ?></td>
                                    <td><?= htmlspecialchars($f['departement_nom'] ?? 'Aucun') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Onglet Départements & Services -->
        <div class="tab-pane fade" id="tab-dept">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Départements, Facultés & Directions</h6>
                <button class="btn btn-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddDept">
                    <i class="bi bi-plus me-1"></i>Ajouter un département
                </button>
            </div>

            <?php if (empty($departements)): ?>
                <div class="text-center py-4 text-muted small">Aucun département configuré.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Hiérarchie (Parent)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departements as $d): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($d['code']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($d['nom']) ?></td>
                                    <td><?= htmlspecialchars($d['parent_nom'] ?? 'Racine') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Onglet Coordonnées -->
        <div class="tab-pane fade" id="tab-infos">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Coordonnées</h6>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Adresse :</span>
                            <span class="fw-semibold"><?= htmlspecialchars($etab['adresse'] ?? 'Non spécifiée') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Ville :</span>
                            <span class="fw-semibold"><?= htmlspecialchars($etab['ville'] ?? 'N/A') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Pays :</span>
                            <span class="fw-semibold"><?= htmlspecialchars($etab['pays'] ?? 'Gabon') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Téléphone :</span>
                            <span class="fw-semibold"><?= htmlspecialchars($etab['telephone'] ?? 'N/A') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">E-mail :</span>
                            <span class="fw-semibold"><?= htmlspecialchars($etab['email'] ?? 'N/A') ?></span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Visuels officiels</h6>
                    <div class="row g-3">
                        <div class="col-6 text-center">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-2">Logo</small>
                                <?php if (!empty($etab['logo'])): ?>
                                    <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['logo']) ?>" alt="Logo" style="max-height: 80px; object-fit: contain;">
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Non renseigné</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-2">Cachet / Tampon</small>
                                <?php if (!empty($etab['cachet'])): ?>
                                    <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['cachet']) ?>" alt="Cachet" style="max-height: 80px; object-fit: contain;">
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Non renseigné</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales d'ajout de structure -->

<!-- Modal Classe -->
<div class="modal fade" id="modalAddClasse" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/classes/ajouter" method="POST">
                <?= $csrf_input ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ajouter une classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Libellé (ex: 6e A, Tle D, L1 Info)</label>
                        <input type="text" name="libelle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Niveau (ex: 6e, 3e, Terminale, L1, Master 2)</label>
                        <input type="text" name="niveau" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Filière / Série (optionnel)</label>
                        <select name="filiere_id" class="form-select">
                            <option value="">Aucune filière</option>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Année Scolaire</label>
                        <input type="text" name="annee_scolaire" class="form-control" value="<?= htmlspecialchars($etab['annee_active'] ?? '2026-2027') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Capacité maximale</label>
                        <input type="number" name="effectif_max" class="form-control" placeholder="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Enregistrer la classe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Filière -->
<div class="modal fade" id="modalAddFiliere" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/filieres/ajouter" method="POST">
                <?= $csrf_input ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ajouter une filière / série</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Code court (ex: D, INFO, G2)</label>
                        <input type="text" name="code" class="form-control text-uppercase" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Intitulé complet (ex: Série Scientifique D)</label>
                        <input type="text" name="libelle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Département de rattachement</label>
                        <select name="departement_id" class="form-select">
                            <option value="">Aucun</option>
                            <?php foreach ($departements as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Enregistrer la filière</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Département -->
<div class="modal fade" id="modalAddDept" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/departements/ajouter" method="POST">
                <?= $csrf_input ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ajouter un département / direction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Code (ex: DIR, DSI, FAC_SCIENCES)</label>
                        <input type="text" name="code" class="form-control text-uppercase" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nom complet</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Département parent (hiérarchie)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Aucun (Département racine)</option>
                            <?php foreach ($departements as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
