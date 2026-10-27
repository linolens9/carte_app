<?php
/**
 * Vue : Journal d'audit et traçabilité des opérations
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-shield-shaded me-2 text-primary"></i> Journal d'Audit & Sécurité
        </h4>
        <p class="text-secondary mb-0 small">
            Traçabilité inaltérable de l'ensemble des opérations sensibles, connexions et modifications.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $base_path ?>/logs/export/csv" class="btn btn-outline-success d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Export CSV</span>
        </a>
    </div>
</div>

<!-- Filtres -->
<div class="card-custom p-3 mb-4">
    <form method="GET" action="<?= $base_path ?>/logs" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Action</label>
            <input type="text" name="action" class="form-control form-control-sm" placeholder="ex: carte, login, creation..." value="<?= htmlspecialchars($filters['action'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Module</label>
            <select name="module" class="form-select form-select-sm">
                <option value="">Tous les modules</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($m)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Établissement</label>
            <select name="etablissement_id" class="form-select form-select-sm">
                <option value="">Tous les établissements</option>
                <?php foreach ($etablissements as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= ($filters['etablissement_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Date début</label>
            <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-1">
                <i class="bi bi-filter"></i> Filtrer
            </button>
            <a href="<?= $base_path ?>/logs" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau des événements -->
<div class="card-custom p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">Événements enregistrés (<?= number_format($total, 0, ',', ' ') ?>)</h6>
        <span class="badge bg-light text-secondary border">Ajout seul (inaltérable)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Établissement</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Détails</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                            Aucun événement ne correspond aux critères de recherche.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap font-monospace text-muted small">
                                <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <?php if (!empty($log['user_nom'])): ?>
                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($log['user_prenom'] . ' ' . $log['user_nom']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($log['user_email']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Système</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= !empty($log['etab_nom']) ? htmlspecialchars($log['etab_nom']) : '<span class="text-muted">Global</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($log['objet_type'] ?? 'N/A') ?></span>
                                <?php if (!empty($log['objet_id'])): ?>
                                    <span class="text-muted font-monospace small">#<?= $log['objet_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php
                                $details = '';
                                if (!empty($log['donnees_apres'])) {
                                    $data = json_decode($log['donnees_apres'], true);
                                    if (isset($data['description'])) {
                                        $details = $data['description'];
                                    } else {
                                        $details = substr($log['donnees_apres'], 0, 80);
                                    }
                                }
                                ?>
                                <span class="text-dark"><?= htmlspecialchars($details) ?></span>
                            </td>
                            <td class="font-monospace small text-muted text-nowrap">
                                <?= htmlspecialchars($log['adresse_ip'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <span class="small text-muted">Page <?= $page ?> sur <?= $totalPages ?></span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base_path ?>/logs?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">Précédent</a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $base_path ?>/logs?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base_path ?>/logs?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">Suivant</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
