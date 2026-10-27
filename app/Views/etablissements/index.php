<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $etablissements */
/** @var array $filters */
/** @var array $counts */
/** @var string $csrf_input */
$currentStatut = $filters['statut'] ?? '';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion des Établissements</h2>
        <p class="text-muted small mb-0">Structures scolaires, académiques et professionnelles enregistrées</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_path ?>/etablissements/creer" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>Créer un établissement
        </a>
    </div>
</div>

<!-- Onglets Rapides Statuts -->
<ul class="nav nav-pills mb-3 gap-2">
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 py-2 <?= (empty($currentStatut) || $currentStatut === 'actif') ? 'active bg-primary' : 'bg-light text-dark border' ?>" 
           href="<?= $base_path ?>/etablissements">
            <i class="bi bi-building-check me-1"></i>En service 
            <span class="badge <?= (empty($currentStatut) || $currentStatut === 'actif') ? 'bg-white text-dark' : 'bg-secondary text-white' ?> ms-1"><?= (int)($counts['total_en_service'] ?? 0) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 py-2 <?= ($currentStatut === 'archive') ? 'active bg-dark text-white' : 'bg-light text-dark border' ?>" 
           href="<?= $base_path ?>/etablissements?statut=archive">
            <i class="bi bi-archive me-1"></i>Archivés 
            <span class="badge <?= ($currentStatut === 'archive') ? 'bg-danger text-white' : 'bg-secondary text-white' ?> ms-1"><?= (int)($counts['archives'] ?? 0) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 py-2 <?= ($currentStatut === 'tous') ? 'active bg-primary' : 'bg-light text-dark border' ?>" 
           href="<?= $base_path ?>/etablissements?statut=tous">
            <i class="bi bi-buildings me-1"></i>Tous 
            <span class="badge <?= ($currentStatut === 'tous') ? 'bg-white text-dark' : 'bg-secondary text-white' ?> ms-1"><?= (int)($counts['total'] ?? 0) ?></span>
        </a>
    </li>
</ul>

<!-- Bannière d'information si affichage des archives -->
<?php if ($currentStatut === 'archive'): ?>
    <div class="alert alert-secondary d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-archive-fill fs-5 text-secondary"></i>
            <div>
                <strong>Mode Archives</strong> : affichage des établissements archivés ou hors service.
            </div>
        </div>
        <a href="<?= $base_path ?>/etablissements" class="btn btn-sm btn-outline-dark rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour aux établissements en service
        </a>
    </div>
<?php endif; ?>

<!-- Filtres de recherche -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/etablissements" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Rechercher par nom, code ou ville..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="categorie" class="form-select bg-light">
                    <option value="">Toutes catégories</option>
                    <option value="scolaire" <?= ($filters['categorie'] ?? '') === 'scolaire' ? 'selected' : '' ?>>Scolaire</option>
                    <option value="professionnel" <?= ($filters['categorie'] ?? '') === 'professionnel' ? 'selected' : '' ?>>Professionnel</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select bg-light">
                    <option value="">Tous les types</option>
                    <option value="primaire" <?= ($filters['type'] ?? '') === 'primaire' ? 'selected' : '' ?>>École primaire</option>
                    <option value="college" <?= ($filters['type'] ?? '') === 'college' ? 'selected' : '' ?>>Collège</option>
                    <option value="lycee" <?= ($filters['type'] ?? '') === 'lycee' ? 'selected' : '' ?>>Lycée</option>
                    <option value="universite" <?= ($filters['type'] ?? '') === 'universite' ? 'selected' : '' ?>>Université</option>
                    <option value="centre_formation" <?= ($filters['type'] ?? '') === 'centre_formation' ? 'selected' : '' ?>>Centre de formation</option>
                    <option value="entreprise" <?= ($filters['type'] ?? '') === 'entreprise' ? 'selected' : '' ?>>Entreprise</option>
                    <option value="administration" <?= ($filters['type'] ?? '') === 'administration' ? 'selected' : '' ?>>Administration</option>
                    <option value="association" <?= ($filters['type'] ?? '') === 'association' ? 'selected' : '' ?>>Association</option>
                    <option value="ong" <?= ($filters['type'] ?? '') === 'ong' ? 'selected' : '' ?>>ONG</option>
                    <option value="etablissement_prive" <?= ($filters['type'] ?? '') === 'etablissement_prive' ? 'selected' : '' ?>>Établissement privé</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select bg-light">
                    <option value="" <?= empty($currentStatut) ? 'selected' : '' ?>>En service</option>
                    <option value="actif" <?= $currentStatut === 'actif' ? 'selected' : '' ?>>Actifs uniquement</option>
                    <option value="suspendu" <?= $currentStatut === 'suspendu' ? 'selected' : '' ?>>Suspendus uniquement</option>
                    <option value="archive" <?= $currentStatut === 'archive' ? 'selected' : '' ?>>Archivés uniquement</option>
                    <option value="tous" <?= $currentStatut === 'tous' ? 'selected' : '' ?>>Tous les statuts</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1 d-flex align-items-center justify-content-center gap-1" title="Filtrer">
                    <i class="bi bi-filter"></i>
                    <span class="d-md-none fw-semibold">Filtrer</span>
                </button>
                <a href="<?= $base_path ?>/etablissements" class="btn btn-light border d-flex align-items-center justify-content-center" title="Réinitialiser">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span class="d-md-none ms-1">Effacer</span>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des établissements -->
<div class="card-custom">
    <div class="p-0">
        <?php if (empty($etablissements)): ?>
            <div class="text-center py-5">
                <i class="bi bi-buildings text-muted fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold"><?= $currentStatut === 'archive' ? 'Aucun établissement archivé' : 'Aucun établissement trouvé' ?></h5>
                <p class="text-muted small">
                    <?= $currentStatut === 'archive' ? 'Aucun établissement n\'a été archivé pour le moment.' : 'Ajustez vos filtres ou commencez par en créer un.' ?>
                </p>
                <?php if ($currentStatut === 'archive'): ?>
                    <a href="<?= $base_path ?>/etablissements" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                        Voir les établissements en service
                    </a>
                <?php else: ?>
                    <a href="<?= $base_path ?>/etablissements/creer" class="btn btn-outline-primary btn-sm rounded-pill mt-2">
                        Créer le premier établissement
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Établissement</th>
                            <th>Code</th>
                            <th>Catégorie & Type</th>
                            <th>Ville / Pays</th>
                            <th>Effectifs</th>
                            <th>Cartes</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etablissements as $etab): ?>
                            <?php $isArchived = !empty($etab['is_archived']) || ($etab['statut'] === 'archive') || !empty($etab['deleted_at']); ?>
                            <tr class="<?= $isArchived ? 'opacity-75 bg-light' : '' ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($etab['logo'])): ?>
                                            <img src="<?= $base_path ?>/<?= htmlspecialchars($etab['logo']) ?>" alt="Logo" class="rounded border" style="width: 40px; height: 40px; object-fit: contain; background: #fff;">
                                        <?php else: ?>
                                            <div class="rounded border d-flex align-items-center justify-content-center text-white fw-bold"
                                                 style="width: 40px; height: 40px; background: <?= htmlspecialchars($etab['couleur_principale'] ?? '#2563eb') ?>;">
                                                <?= strtoupper(substr($etab['code'], 0, 2)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($etab['nom']) ?>
                                            </a>
                                            <div class="text-muted small"><?= htmlspecialchars($etab['email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="fw-bold text-primary"><?= htmlspecialchars($etab['code']) ?></code>
                                </td>
                                <td>
                                    <span class="badge badge-subtle-primary mb-1 d-inline-block"><?= ucfirst($etab['categorie']) ?></span><br>
                                    <small class="text-muted"><?= str_replace('_', ' ', ucfirst($etab['type'])) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($etab['ville'] ?? 'N/A') ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($etab['pays'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if ($etab['categorie'] === 'scolaire'): ?>
                                        <span class="badge bg-light text-dark border me-1" title="Élèves"><i class="bi bi-backpack me-1"></i><?= (int)$etab['nb_eleves'] ?></span>
                                        <span class="badge bg-light text-dark border" title="Étudiants"><i class="bi bi-mortarboard me-1"></i><?= (int)$etab['nb_etudiants'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border" title="Personnel"><i class="bi bi-person-badge me-1"></i><?= (int)$etab['nb_personnels'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= (int)$etab['nb_cartes'] ?></span>
                                    <small class="text-success d-block">(<?= (int)$etab['nb_cartes_actives'] ?> actives)</small>
                                </td>
                                <td>
                                    <?php if ($isArchived): ?>
                                        <span class="badge badge-subtle-danger"><i class="bi bi-archive me-1"></i>Archivé</span>
                                    <?php elseif ($etab['statut'] === 'actif'): ?>
                                        <span class="badge badge-subtle-success">Actif</span>
                                    <?php elseif ($etab['statut'] === 'suspendu'): ?>
                                        <span class="badge badge-subtle-warning">Suspendu</span>
                                    <?php else: ?>
                                        <span class="badge badge-subtle-danger">Archivé</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>">
                                                    <i class="bi bi-eye me-2 text-primary"></i>Consulter la fiche
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/modifier">
                                                    <i class="bi bi-pencil me-2 text-warning"></i>Modifier
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if ($isArchived): ?>
                                                <li>
                                                    <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/restaurer" method="POST" class="d-inline">
                                                        <?= $csrf_input ?>
                                                        <button type="submit" class="dropdown-item text-success"
                                                                onclick="return confirm('Restaurer cet établissement et le remettre en service ?');">
                                                            <i class="bi bi-arrow-counterclockwise me-2"></i>Désarchiver / Restaurer
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <form action="<?= $base_path ?>/etablissements/<?= $etab['id'] ?>/supprimer" method="POST" class="d-inline">
                                                        <?= $csrf_input ?>
                                                        <button type="submit" class="dropdown-item text-danger btn-confirm-delete"
                                                                data-message="Archiver cet établissement ? L'action sera refusée si des cartes actives existent.">
                                                            <i class="bi bi-archive me-2"></i>Archiver
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
