<?php
/**
 * Liste des cartes d'identité avec filtres, statuts et actions par lot
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Cartes d'Identité</h4>
        <p class="text-secondary mb-0">Gestion du cycle de vie des cartes : génération, validation, impression et vérification.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $base_path ?>/cartes/generer-lot" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-stack"></i>
            <span>Génération par Lot</span>
        </a>
        <a href="<?= $base_path ?>/cartes/generer" class="btn btn-primary-custom d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            <span>Générer une carte</span>
        </a>
    </div>
</div>

<!-- KPI & Filtres par statut -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-primary <?= empty($statut_filter) ? 'bg-light border-primary' : '' ?>">
                <div class="text-muted small fw-semibold">TOUTES</div>
                <div class="fs-4 fw-bold text-dark"><?= (int)($kpi['total'] ?? 0) ?></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes?statut=brouillon" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-secondary <?= ($statut_filter === 'brouillon') ? 'bg-light' : '' ?>">
                <div class="text-secondary small fw-semibold">BROUILLONS</div>
                <div class="fs-4 fw-bold text-secondary"><?= (int)($kpi['brouillon'] ?? 0) ?></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes?statut=validee" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-warning <?= ($statut_filter === 'validee') ? 'bg-light' : '' ?>">
                <div class="text-warning small fw-semibold">VALIDÉES</div>
                <div class="fs-4 fw-bold text-warning"><?= (int)($kpi['validee'] ?? 0) ?></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes?statut=imprimee" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-info <?= ($statut_filter === 'imprimee') ? 'bg-light' : '' ?>">
                <div class="text-info small fw-semibold">IMPRIMÉES</div>
                <div class="fs-4 fw-bold text-info"><?= (int)($kpi['imprimee'] ?? 0) ?></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes?statut=active" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-success <?= ($statut_filter === 'active') ? 'bg-light' : '' ?>">
                <div class="text-success small fw-semibold">ACTIVES</div>
                <div class="fs-4 fw-bold text-success"><?= (int)($kpi['active'] ?? 0) ?></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <a href="<?= $base_path ?>/cartes?statut=desactivee" class="text-decoration-none">
            <div class="card-custom p-3 text-center border-start border-4 border-danger <?= ($statut_filter === 'desactivee') ? 'bg-light' : '' ?>">
                <div class="text-danger small fw-semibold">DÉSACTIVÉES</div>
                <div class="fs-4 fw-bold text-danger"><?= (int)($kpi['desactivee'] ?? 0) ?></div>
            </div>
        </a>
    </div>
</div>

<!-- Filtres et Recherche -->
<div class="card-custom p-3 mb-4">
    <form method="GET" action="<?= $base_path ?>/cartes" class="row g-2 align-items-center">
        <?php if ($statut_filter): ?>
            <input type="hidden" name="statut" value="<?= htmlspecialchars($statut_filter) ?>">
        <?php endif; ?>

        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Rechercher par n° carte, matricule ou nom..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
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

        <div class="col-md-3">
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les types</option>
                <?php if ($showEleves): ?>
                    <option value="eleve" <?= ($type_filter === 'eleve') ? 'selected' : '' ?>>Élèves (Scolaire)</option>
                <?php endif; ?>
                <?php if ($showEtudiants): ?>
                    <option value="etudiant" <?= ($type_filter === 'etudiant') ? 'selected' : '' ?>>Étudiants (Universitaire)</option>
                <?php endif; ?>
                <?php if ($showPersonnel): ?>
                    <option value="personnel" <?= ($type_filter === 'personnel') ? 'selected' : '' ?>>Personnel (Professionnel)</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-4 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary px-3">Filtrer</button>
            <?php if ($search || $type_filter || $statut_filter): ?>
                <a href="<?= $base_path ?>/cartes" class="btn btn-light border px-3">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Formulaire pour Actions par lot -->
<form id="batchActionForm" method="POST" action="<?= $base_path ?>/cartes/valider-lot">
    <?= \App\Core\Csrf::inputField() ?>

    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
        <div class="text-muted small">
            Affichage de <strong><?= count($cartes) ?></strong> sur <strong><?= $total ?></strong> carte(s)
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-sm btn-outline-success" id="btnBatchValidate" disabled>
                <i class="bi bi-check2-circle me-1"></i> Valider la sélection
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnBatchPrint" disabled>
                <i class="bi bi-printer me-1"></i> Imprimer la sélection
            </button>
        </div>
    </div>

    <!-- Tableau des Cartes -->
    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="checkAll">
                        </th>
                        <th>Bénéficiaire</th>
                        <th>Type / Groupe</th>
                        <th>N° Carte & Code</th>
                        <th>Validité</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cartes)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-card-heading fs-1 d-block mb-2 text-secondary"></i>
                                Aucune carte d'identité ne correspond à vos critères.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cartes as $c): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="cartes_ids[]" value="<?= $c['id'] ?>" class="form-check-input card-chk">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($c['beneficiaire_photo'])): ?>
                                            <img src="<?= htmlspecialchars(asset_url($c['beneficiaire_photo'])) ?>" alt="Photo" class="rounded-circle object-fit-cover shadow-sm border" style="width: 40px; height: 40px;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.85rem;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <a href="<?= $base_path ?>/cartes/<?= $c['id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($c['beneficiaire_nom_complet'] ?? 'Bénéficiaire') ?>
                                                </a>
                                            </div>
                                            <div class="small text-muted font-monospace">
                                                <?= htmlspecialchars($c['beneficiaire_matricule'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $c['beneficiaire_type'] === 'eleve' ? 'bg-primary' : ($c['beneficiaire_type'] === 'etudiant' ? 'bg-success' : 'bg-warning text-dark') ?> mb-1">
                                        <?= ucfirst(htmlspecialchars($c['beneficiaire_type'])) ?>
                                    </span>
                                    <div class="small text-secondary"><?= htmlspecialchars($c['beneficiaire_detail'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary font-monospace" style="font-size: 0.88rem;">
                                        <?= htmlspecialchars($c['numero_carte']) ?>
                                    </div>
                                    <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                                        Jeton : <?= substr($c['jeton_verification'], 0, 12) ?>...
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark">Émise : <?= date('d/m/Y', strtotime($c['date_emission'])) ?></div>
                                    <div class="small text-danger fw-semibold">Expire : <?= date('d/m/Y', strtotime($c['date_expiration'])) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $st = $c['statut'];
                                    $badgeClass = match ($st) {
                                        'brouillon' => 'bg-secondary',
                                        'validee' => 'bg-warning text-dark',
                                        'imprimee' => 'bg-info text-dark',
                                        'active' => 'bg-success',
                                        'expiree' => 'bg-warning',
                                        'desactivee' => 'bg-danger',
                                        'archivee' => 'bg-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                                        <?= ucfirst(htmlspecialchars($st)) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= $base_path ?>/cartes/<?= $c['id'] ?>" class="btn btn-sm btn-light border rounded-circle text-primary" title="Aperçu 3D">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($c['statut'] === 'brouillon'): ?>
                                            <a href="<?= $base_path ?>/cartes/<?= $c['id'] ?>/valider" class="btn btn-sm btn-light border rounded-circle text-success" title="Valider">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= $base_path ?>/impressions/carte/<?= $c['id'] ?>" class="btn btn-sm btn-light border rounded-circle text-info" title="Imprimer">
                                            <i class="bi bi-printer"></i>
                                        </a>

                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-light border rounded-circle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li><a class="dropdown-item" href="<?= $base_path ?>/verifier/<?= $c['jeton_verification'] ?>" target="_blank"><i class="bi bi-qr-code-scan me-2 text-info"></i> Page de vérification publique</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <?php if ($c['statut'] !== 'active'): ?>
                                                    <li><a class="dropdown-item text-success" href="<?= $base_path ?>/cartes/<?= $c['id'] ?>/activer"><i class="bi bi-check-circle me-2"></i> Marquer Active / Remise</a></li>
                                                <?php endif; ?>
                                                <li><a class="dropdown-item text-primary" href="<?= $base_path ?>/cartes/<?= $c['id'] ?>/renouveler"><i class="bi bi-arrow-repeat me-2"></i> Renouveler la carte</a></li>
                                                <li><a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#modalDesactiver-<?= $c['id'] ?>"><i class="bi bi-slash-circle me-2"></i> Désactiver</a></li>
                                                <li><a class="dropdown-item text-secondary" href="<?= $base_path ?>/cartes/<?= $c['id'] ?>/archiver"><i class="bi bi-archive me-2"></i> Archiver</a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Modal Désactiver -->
                                    <div class="modal fade" id="modalDesactiver-<?= $c['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= $base_path ?>/cartes/<?= $c['id'] ?>/desactiver">
                                                    <?= \App\Core\Csrf::inputField() ?>
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Désactiver la carte <?= htmlspecialchars($c['numero_carte']) ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Motif de la désactivation</label>
                                                            <select name="motif" class="form-select" required>
                                                                <option value="Perte signalée par le titulaire">Perte signalée</option>
                                                                <option value="Vol de la carte">Vol de la carte</option>
                                                                <option value="Détérioration physique de la carte">Détérioration physique</option>
                                                                <option value="Départ / Exclusion / Fin de contrat">Départ / Fin de contrat</option>
                                                                <option value="Erreur de saisie / Annulation">Erreur administrative</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" class="btn btn-danger">Désactiver la carte</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
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
                <span class="text-muted small">Page <?= $currentPage ?> sur <?= $totalPages ?></span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $base_path ?>/cartes?page=<?= $p ?><?= $statut_filter ? '&statut=' . urlencode($statut_filter) : '' ?><?= $type_filter ? '&type=' . urlencode($type_filter) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</form>

<script>
    // Sélection par lot
    const checkAll = document.getElementById('checkAll');
    const cardChks = document.querySelectorAll('.card-chk');
    const btnBatchValidate = document.getElementById('btnBatchValidate');
    const btnBatchPrint = document.getElementById('btnBatchPrint');
    const batchForm = document.getElementById('batchActionForm');

    function updateBatchButtons() {
        const checkedCount = document.querySelectorAll('.card-chk:checked').length;
        btnBatchValidate.disabled = checkedCount === 0;
        btnBatchPrint.disabled = checkedCount === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            cardChks.forEach(chk => chk.checked = checkAll.checked);
            updateBatchButtons();
        });
    }

    cardChks.forEach(chk => {
        chk.addEventListener('change', updateBatchButtons);
    });

    if (btnBatchPrint) {
        btnBatchPrint.addEventListener('click', () => {
            batchForm.action = '<?= $base_path ?>/impressions/planche-a4';
            batchForm.submit();
        });
    }
</script>
