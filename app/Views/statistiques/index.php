<?php
/**
 * Vue : Tableau de bord analytique et statistiques avancées
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
$kpi = $stats['kpi'];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-graph-up-arrow me-2 text-primary"></i> Statistiques & Analyse
        </h4>
        <p class="text-secondary mb-0 small">
            Indicateurs de performance, production de cartes, ventilation par établissement et analyses temporelles.
        </p>
    </div>

    <!-- Actions & Filtres -->
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="<?= $base_path ?>/statistiques" class="d-flex align-items-center gap-2">
            <select name="etablissement_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 220px;">
                <option value="">Tous les établissements</option>
                <?php foreach ($etablissements as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= (isset($selectedEtabId) && $selectedEtabId == $e['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= $base_path ?>/statistiques/export/csv<?= $selectedEtabId ? '?etablissement_id=' . $selectedEtabId : '' ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Export CSV</span>
        </a>

        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-printer"></i>
            <span>Imprimer</span>
        </button>
    </div>
</div>

<!-- Cartes KPI Majeures -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Cartes Produites</span>
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-card-heading fs-5"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= number_format($kpi['total_cartes'], 0, ',', ' ') ?></div>
            <div class="small text-muted mt-1">
                <span class="text-success fw-semibold"><i class="bi bi-check2"></i> <?= $kpi['cartes_validees'] ?></span> validées en attente
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Cartes Actives (En service)</span>
                <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-shield-check fs-5"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-success"><?= number_format($kpi['cartes_actives'], 0, ',', ' ') ?></div>
            <div class="small text-muted mt-1">
                <?= $kpi['total_cartes'] > 0 ? round(($kpi['cartes_actives'] / $kpi['total_cartes']) * 100, 1) : 0 ?>% de la production totale
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Cartes Imprimées</span>
                <span class="p-2 rounded-3 bg-info bg-opacity-10 text-info">
                    <i class="bi bi-printer fs-5"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-info"><?= number_format($kpi['cartes_imprimees'], 0, ',', ' ') ?></div>
            <div class="small text-muted mt-1">
                Tirages physiques réalisés
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Couverture Photos</span>
                <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-camera fs-5"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark"><?= $kpi['taux_photos'] ?>%</div>
            <div class="small text-muted mt-1">
                <?= $kpi['total_avec_photo'] ?> / <?= $kpi['total_beneficiaires'] ?> bénéficiaires
            </div>
        </div>
    </div>
</div>

<!-- Graphiques Ligne 1 -->
<div class="row g-4 mb-4">
    <!-- Évolution mensuelle -->
    <div class="col-lg-8">
        <div class="card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-graph-up me-2 text-primary"></i> Production & Impressions sur 12 Mois
                </h6>
                <span class="badge bg-light text-secondary border">Évolution temporelle</span>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="chartEvolution"></canvas>
            </div>
        </div>
    </div>

    <!-- Cycle de vie des cartes -->
    <div class="col-lg-4">
        <div class="card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-pie-chart me-2 text-primary"></i> Statuts des Cartes
                </h6>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="chartStatuts"></canvas>
            </div>
            <div class="mt-3 text-center small text-muted">
                <span class="me-3"><i class="bi bi-circle-fill text-success" style="font-size:0.6rem;"></i> Actives : <?= $kpi['cartes_actives'] ?></span>
                <span class="me-3"><i class="bi bi-circle-fill text-danger" style="font-size:0.6rem;"></i> Expirées : <?= $kpi['cartes_expirees'] ?></span>
                <span><i class="bi bi-circle-fill text-secondary" style="font-size:0.6rem;"></i> Désactivées : <?= $kpi['cartes_desactivees'] ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques Ligne 2 -->
<div class="row g-4 mb-4">
    <!-- Répartition bénéficiaires -->
    <div class="col-lg-4">
        <div class="card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-people me-2 text-primary"></i> Répartition des Titulaires
                </h6>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="chartBeneficiaires"></canvas>
            </div>
            <div class="mt-3 text-center small text-muted">
                Total : <?= number_format($kpi['total_beneficiaires'], 0, ',', ' ') ?> bénéficiaires enregistrés
            </div>
        </div>
    </div>

    <!-- Top Établissements -->
    <div class="col-lg-8">
        <div class="card-custom p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-bar-chart-steps me-2 text-primary"></i> Top Établissements par Volume de Cartes
                </h6>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="chartTopEtabs"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tableau détaillé par Établissement -->
<div class="card-custom p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-table me-2 text-primary"></i> Ventilation Détaillée par Établissement
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Établissement</th>
                    <th>Type</th>
                    <th class="text-center">Bénéficiaires</th>
                    <th class="text-center">Total Cartes</th>
                    <th class="text-center">Cartes Actives</th>
                    <th class="text-center">Cartes Imprimées</th>
                    <th class="text-center">Taux Couverture</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats['etabs_table'])): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Aucun établissement enregistré.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stats['etabs_table'] as $row): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($row['nom']) ?></span>
                                <span class="badge bg-light text-secondary border ms-1 font-monospace"><?= htmlspecialchars($row['code']) ?></span>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['type']) ?></span></td>
                            <td class="text-center font-monospace fw-semibold"><?= $row['total_beneficiaires'] ?></td>
                            <td class="text-center font-monospace fw-bold text-primary"><?= $row['total_cartes'] ?></td>
                            <td class="text-center font-monospace text-success fw-semibold"><?= $row['cartes_actives'] ?></td>
                            <td class="text-center font-monospace text-info"><?= $row['cartes_imprimees'] ?></td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="progress flex-1" style="height: 6px; width: 60px;">
                                        <div class="progress-bar bg-success" style="width: <?= min(100, $row['taux_couverture']) ?>%;"></div>
                                    </div>
                                    <span class="small font-monospace"><?= $row['taux_couverture'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Initialisation des Graphiques Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Évolution sur 12 mois
    const ctxEvol = document.getElementById('chartEvolution').getContext('2d');
    new Chart(ctxEvol, {
        type: 'line',
        data: {
            labels: <?= json_encode($stats['evolution']['labels']) ?>,
            datasets: [
                {
                    label: 'Cartes Générées',
                    data: <?= json_encode($stats['evolution']['creees']) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                },
                {
                    label: 'Cartes Imprimées',
                    data: <?= json_encode($stats['evolution']['imprimees']) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    tension: 0.35,
                    borderDash: [5, 5],
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // 2. Statuts des Cartes (Doughnut)
    const ctxStatuts = document.getElementById('chartStatuts').getContext('2d');
    new Chart(ctxStatuts, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($stats['statuts_chart']['labels']) ?>,
            datasets: [{
                data: <?= json_encode($stats['statuts_chart']['data']) ?>,
                backgroundColor: ['#94a3b8', '#3b82f6', '#06b6d4', '#10b981', '#ef4444', '#f59e0b', '#64748b'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            },
            cutout: '65%'
        }
    });

    // 3. Répartition Bénéficiaires (Pie)
    const ctxBenef = document.getElementById('chartBeneficiaires').getContext('2d');
    new Chart(ctxBenef, {
        type: 'pie',
        data: {
            labels: <?= json_encode($stats['beneficiaires_chart']['labels']) ?>,
            datasets: [{
                data: <?= json_encode($stats['beneficiaires_chart']['data']) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });

    // 4. Top Établissements (Horizontal Bar)
    const ctxTop = document.getElementById('chartTopEtabs').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: <?= json_encode($stats['top_etabs']['labels']) ?>,
            datasets: [{
                label: 'Cartes Produites',
                data: <?= json_encode($stats['top_etabs']['data']) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
});
</script>
