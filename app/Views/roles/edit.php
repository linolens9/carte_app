<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $role */
/** @var array $groupedPermissions */
/** @var array $activePermissionIds */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modifier les Permissions du Rôle</h2>
        <p class="text-muted small mb-0">Rôle : <strong><?= htmlspecialchars($role['libelle']) ?></strong> (<code><?= htmlspecialchars($role['code']) ?></code>)</p>
    </div>
    <a href="<?= $base_path ?>/roles" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour à la matrice
    </a>
</div>

<form action="<?= $base_path ?>/roles/<?= $role['id'] ?>/modifier" method="POST">
    <?= $csrf_input ?>

    <div class="card-custom mb-4">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Permissions accordées</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCheckAll">Tout cocher</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnUncheckAll">Tout décocher</button>
            </div>
        </div>
        <div class="p-4">
            <div class="row g-4">
                <?php foreach ($groupedPermissions as $module => $perms): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <h6 class="fw-bold text-primary text-uppercase small mb-3 border-bottom pb-2">
                                <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($module) ?>
                            </h6>
                            <?php foreach ($perms as $p): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input perm-checkbox" type="checkbox"
                                           name="permissions[]" value="<?= $p['id'] ?>"
                                           id="perm_<?= $p['id'] ?>"
                                           <?= in_array($p['id'], $activePermissionIds, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="perm_<?= $p['id'] ?>">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['libelle']) ?></div>
                                        <code class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($p['code']) ?></code>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= $base_path ?>/roles" class="btn btn-light border px-4">Annuler</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-save me-1"></i>Enregistrer les permissions
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    document.getElementById('btnCheckAll').addEventListener('click', () => {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
    });
    document.getElementById('btnUncheckAll').addEventListener('click', () => {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
    });
</script>
