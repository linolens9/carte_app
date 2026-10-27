<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var array $roles */
/** @var array $groupedPermissions */
/** @var array $rolePermissions */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Rôles & Permissions (RBAC)</h2>
        <p class="text-muted small mb-0">Contrôle d'accès basé sur les rôles et matrice des privilèges</p>
    </div>
</div>

<!-- Liste des Rôles -->
<div class="row g-3 mb-4">
    <?php foreach ($roles as $r): ?>
        <div class="col-md-4">
            <div class="card-custom h-100 p-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($r['code']) ?></span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= (int)$r['user_count'] ?> utilisateur(s)</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($r['libelle']) ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($r['description'] ?? 'Aucune description.') ?></p>
                </div>
                <div>
                    <?php if ($r['code'] !== 'admin'): ?>
                        <a href="<?= $base_path ?>/roles/<?= $r['id'] ?>/modifier" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                            <i class="bi bi-shield-check me-1"></i>Configurer les permissions
                        </a>
                    <?php else: ?>
                        <span class="badge badge-subtle-danger w-100 py-2">Super-utilisateur (Accès Total)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Matrice des Permissions -->
<div class="card-custom">
    <div class="card-header-custom">
        <h6 class="fw-bold mb-0">Matrice des Permissions Granulaires</h6>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table table-custom table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 250px;">Module & Permission</th>
                        <?php foreach ($roles as $r): ?>
                            <th class="text-center" style="width: 140px;">
                                <div class="fw-bold"><?= htmlspecialchars($r['libelle']) ?></div>
                                <code class="small text-muted"><?= htmlspecialchars($r['code']) ?></code>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupedPermissions as $module => $perms): ?>
                        <tr class="table-light">
                            <td colspan="<?= count($roles) + 1 ?>" class="fw-bold text-uppercase small py-2 bg-light text-primary">
                                <i class="bi bi-folder2 me-1"></i>Module : <?= htmlspecialchars($module) ?>
                            </td>
                        </tr>
                        <?php foreach ($perms as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($p['libelle']) ?></div>
                                    <code class="small text-muted"><?= htmlspecialchars($p['code']) ?></code>
                                </td>
                                <?php foreach ($roles as $r): ?>
                                    <td class="text-center">
                                        <?php
                                            $hasPerm = ($r['code'] === 'admin') || in_array($p['id'], $rolePermissions[$r['id']] ?? [], true);
                                        ?>
                                        <?php if ($hasPerm): ?>
                                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                        <?php else: ?>
                                            <i class="bi bi-dash text-muted fs-4"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
