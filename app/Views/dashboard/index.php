<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $kpis */
/** @var array $cartesBientotExpirees */
/** @var array $sansPhoto */
/** @var array $recentLogs */
/** @var array $chartBeneficiaires */
/** @var array $chartStatuts */
/** @var array $chartMois */
/** @var array $topEtabs */
/** @var array|null $active_etab */
?>

<!-- En-tête du Dashboard -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Tableau de bord</h2>
        <p class="text-muted small mb-0">
            <?php if ($active_etab): ?>
                Vue filtrée pour l'établissement : <strong><?= htmlspecialchars($active_etab['nom']) ?></strong> (<?= htmlspecialchars($active_etab['code']) ?>)
            <?php else: ?>
                Vue globale multi-établissements (synthèse en temps réel)
            <?php endif; ?>
        </p>
    </div>

    <!-- Actions rapides -->
    <div class="d-flex gap-2">
        <?php if (\App\Core\Auth::hasRole('admin', 'gestionnaire')): ?>
            <a href="<?= $base_path ?>/etablissements/creer" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold small">
                <i class="bi bi-plus-circle me-1"></i>Nouvel établissement
            </a>
        <?php endif; ?>
        <a href="<?= $base_path ?>/cartes" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold small shadow-sm">
            <i class="bi bi-credit-card-2-front me-1"></i>Générer une carte
        </a>
    </div>
</div>

<?php
    $etabType = $active_etab['type'] ?? '';
    $etabCat = $active_etab['categorie'] ?? '';
    $isAdminOrGest = \App\Core\Auth::hasRole('admin', 'gestionnaire');

    $isSchool = in_array($etabType, ['primaire', 'college', 'lycee'], true);
    $isHigherEd = in_array($etabType, ['universite', 'centre_formation'], true);
    $isPro = ($etabCat === 'professionnel') || in_array($etabType, ['entreprise', 'administration', 'association', 'ong', 'etablissement_prive'], true);

    $showKpiEtabs = $isAdminOrGest && empty($active_etab);
    $showKpiEleves = !$isHigherEd && !$isPro;
    $showKpiEtudiants = !$isSchool && !$isPro && ($isAdminOrGest || $isHigherEd);
    $showKpiPersonnel = true;

    $benefKpiCount = ($showKpiEtabs ? 1 : 0) + ($showKpiEleves ? 1 : 0) + ($showKpiEtudiants ? 1 : 0) + ($showKpiPersonnel ? 1 : 0);
    $benefColClass = match($benefKpiCount) {
        1 => 'col-xl-12 col-md-12',
        2 => 'col-xl-6 col-md-6',
        3 => 'col-xl-4 col-md-4',
        default => 'col-xl-3 col-md-6',
    };
?>

<!-- KPI Cards Bénéficiaires & Structures -->
<div class="row g-3 mb-3">
    <?php if ($showKpiEtabs): ?>
        <!-- Établissements -->
        <div class="<?= $benefColClass ?>">
            <div class="kpi-card">
                <div>
                    <div class="kpi-value text-primary"><?= number_format($kpis['etablissements']) ?></div>
                    <div class="kpi-label">Établissements</div>
                </div>
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-buildings"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showKpiEleves): ?>
        <!-- Élèves -->
        <div class="<?= $benefColClass ?>">
            <div class="kpi-card">
                <div>
                    <div class="kpi-value text-info"><?= number_format($kpis['eleves']) ?></div>
                    <div class="kpi-label">Élèves actifs</div>
                </div>
                <div class="kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-backpack"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showKpiEtudiants): ?>
        <!-- Étudiants -->
        <div class="<?= $benefColClass ?>">
            <div class="kpi-card">
                <div>
                    <div class="kpi-value" style="color: #6366f1;"><?= number_format($kpis['etudiants']) ?></div>
                    <div class="kpi-label">Étudiants</div>
                </div>
                <div class="kpi-icon bg-opacity-10 text-primary" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="bi bi-mortarboard"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($showKpiPersonnel): ?>
        <!-- Personnels -->
        <div class="<?= $benefColClass ?>">
            <div class="kpi-card">
                <div>
                    <div class="kpi-value text-secondary"><?= number_format($kpis['personnels']) ?></div>
                    <div class="kpi-label">Personnels</div>
                </div>
                <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-person-badge"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- KPI Cards Cycle de vie des Cartes -->
<div class="row g-3 mb-4">

    <!-- Cartes Générées -->
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card">
            <div>
                <div class="kpi-value text-dark"><?= number_format($kpis['cartes']) ?></div>
                <div class="kpi-label">Cartes Générées</div>
            </div>
            <div class="kpi-icon bg-dark bg-opacity-10 text-dark">
                <i class="bi bi-card-heading"></i>
            </div>
        </div>
    </div>

    <!-- Cartes Actives -->
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card">
            <div>
                <div class="kpi-value text-success"><?= number_format($kpis['cartes_actives']) ?></div>
                <div class="kpi-label">Cartes Actives</div>
            </div>
            <div class="kpi-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>
    </div>

    <!-- Cartes Expirées -->
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card">
            <div>
                <div class="kpi-value text-danger"><?= number_format($kpis['cartes_expirees']) ?></div>
                <div class="kpi-label">Cartes Expirées</div>
            </div>
            <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques (Section 7.2) -->
<div class="row g-4 mb-4">
    <!-- Évolution mensuelle -->
    <div class="col-lg-8">
        <div class="card-custom h-100">
            <div class="card-header-custom">
                <div>
                    <h6 class="fw-bold mb-0">Évolution de la production des cartes</h6>
                    <small class="text-muted">Volume de cartes générées sur les 12 derniers mois</small>
                </div>
                <span class="badge badge-subtle-primary px-3 py-2 rounded-pill">12 Mois</span>
            </div>
            <div class="p-3">
                <canvas id="chartEvolutionMois" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Répartition Bénéficiaires -->
    <div class="col-lg-4">
        <div class="card-custom h-100">
            <div class="card-header-custom">
                <div>
                    <h6 class="fw-bold mb-0">Bénéficiaires</h6>
                    <small class="text-muted">Répartition par profil</small>
                </div>
            </div>
            <div class="p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 220px; height: 220px;">
                    <canvas id="chartBeneficiaires"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Anneau statuts cartes -->
    <div class="col-lg-4">
        <div class="card-custom h-100">
            <div class="card-header-custom">
                <div>
                    <h6 class="fw-bold mb-0">Statuts des Cartes</h6>
                    <small class="text-muted">Répartition du cycle de vie</small>
                </div>
            </div>
            <div class="p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 220px; height: 220px;">
                    <canvas id="chartStatuts"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et Notifications (Section 7.4) -->
    <div class="col-lg-8">
        <div class="card-custom h-100">
            <div class="card-header-custom">
                <div>
                    <h6 class="fw-bold mb-0">Alertes du Système</h6>
                    <small class="text-muted">Expirations imminentes et photos manquantes</small>
                </div>
                <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-1 rounded-pill">Vigilance</span>
            </div>
            <div class="p-3">
                <ul class="nav nav-pills mb-3" id="alertTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active btn-sm rounded-pill" id="exp-tab" data-bs-toggle="pill" data-bs-target="#tab-exp">
                            <i class="bi bi-clock-history me-1"></i>Cartes expirant sous 30 j (<?= count($cartesBientotExpirees) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm rounded-pill ms-2" id="photo-tab" data-bs-toggle="pill" data-bs-target="#tab-photo">
                            <i class="bi bi-camera me-1"></i>Sans photo (<?= count($sansPhoto) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm rounded-pill ms-2" id="backup-tab" data-bs-toggle="pill" data-bs-target="#tab-backup">
                            <i class="bi bi-database-check me-1"></i>Sauvegarde
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Expiration -->
                    <div class="tab-pane fade show active" id="tab-exp">
                        <?php if (empty($cartesBientotExpirees)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Aucune carte n'arrive à expiration dans les 30 prochains jours.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-custom">
                                    <thead>
                                        <tr>
                                            <th>N° Carte</th>
                                            <th>Établissement</th>
                                            <th>Date Expiration</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartesBientotExpirees as $c): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($c['numero_carte']) ?></td>
                                                <td><?= htmlspecialchars($c['etab_nom']) ?></td>
                                                <td class="text-danger fw-semibold"><?= htmlspecialchars($c['date_expiration']) ?></td>
                                                <td><span class="badge badge-subtle-warning">Expire bientôt</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Photos -->
                    <div class="tab-pane fade" id="tab-photo">
                        <?php if (empty($sansPhoto)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Tous les bénéficiaires disposent actuellement d'une photo.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-custom">
                                    <thead>
                                        <tr>
                                            <th>Profil</th>
                                            <th>Identité</th>
                                            <th>Matricule</th>
                                            <th>Date saisie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sansPhoto as $sp): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark border"><?= ucfirst($sp['type']) ?></span></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($sp['nom'] . ' ' . $sp['prenom']) ?></td>
                                                <td><code><?= htmlspecialchars($sp['matricule']) ?></code></td>
                                                <td class="text-muted small"><?= date('d/m/Y', strtotime($sp['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <div class="tab-pane fade" id="tab-backup">
                        <?php if (empty($latestBackup)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-exclamation-triangle text-warning fs-3 d-block mb-2"></i>
                                Aucune sauvegarde n'a encore été enregistrée.
                                <div class="mt-2">
                                    <a href="<?= $base_path ?>/sauvegardes" class="btn btn-sm btn-primary rounded-pill">
                                        Effectuer une sauvegarde
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle p-2 <?= $latestBackup['statut'] === 'reussie' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?> fs-4">
                                        <i class="bi <?= $latestBackup['statut'] === 'reussie' ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-dark">
                                            Dernière sauvegarde : <?= htmlspecialchars($latestBackup['nom_fichier']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            Le <?= date('d/m/Y à H:i', strtotime($latestBackup['created_at'])) ?> (<?= number_format($latestBackup['taille_octets'] / 1024, 1) ?> Ko) • Statut : 
                                            <span class="badge <?= $latestBackup['statut'] === 'reussie' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($latestBackup['statut']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= $base_path ?>/sauvegardes" class="btn btn-sm btn-outline-primary rounded-pill">
                                    Gérer les sauvegardes
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (\App\Core\Auth::hasRole('admin') || \App\Core\Auth::can('log.consulter') || \App\Core\Auth::hasRole('responsable', 'chef_etablissement')): ?>
<!-- Activités Récentes (Section 7.3) -->
<div class="card-custom">
    <div class="card-header-custom">
        <div>
            <h6 class="fw-bold mb-0">Activités Récentes</h6>
            <small class="text-muted">Journal des 15 dernières actions système</small>
        </div>
        <?php if (\App\Core\Auth::hasRole('admin') || \App\Core\Auth::can('log.consulter')): ?>
            <a href="<?= $base_path ?>/logs" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="bi bi-journal-text me-1"></i>Consulter le journal complet
            </a>
        <?php endif; ?>
    </div>
    <div class="p-0">
        <?php if (empty($recentLogs)): ?>
            <div class="text-center py-4 text-muted small">Aucune activité enregistrée récemment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Utilisateur</th>
                            <th>Établissement</th>
                            <th>Adresse IP</th>
                            <th>Date & Heure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars(($log['user_prenom'] ?? '') . ' ' . ($log['user_nom'] ?? 'Système/Invité')) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($log['etab_nom'] ?? 'Global') ?>
                                </td>
                                <td>
                                    <code class="small"><?= htmlspecialchars($log['adresse_ip'] ?? '') ?></code>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Script Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Évolution Mois
    const ctxMois = document.getElementById('chartEvolutionMois');
    if (ctxMois) {
        new Chart(ctxMois, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartMois['labels']) ?>,
                datasets: [{
                    label: 'Cartes produites',
                    data: <?= json_encode($chartMois['data']) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Bénéficiaires
    const ctxBenef = document.getElementById('chartBeneficiaires');
    if (ctxBenef) {
        new Chart(ctxBenef, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartBeneficiaires['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($chartBeneficiaires['data']) ?>,
                    backgroundColor: <?= json_encode($chartBeneficiaires['colors'] ?? ['#0ea5e9', '#6366f1', '#64748b']) ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 3. Statuts Cartes
    const ctxStatuts = document.getElementById('chartStatuts');
    if (ctxStatuts) {
        new Chart(ctxStatuts, {
            type: 'pie',
            data: {
                labels: <?= json_encode($chartStatuts['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($chartStatuts['data']) ?>,
                    backgroundColor: ['#94a3b8', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
