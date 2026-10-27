<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $errors */
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <div>
                    <h5 class="fw-bold mb-0">Modifier mon mot de passe</h5>
                    <small class="text-muted">Pour des raisons de sécurité, choisissez un mot de passe robuste.</small>
                </div>
                <div class="sidebar-brand-icon" style="width: 42px; height: 42px; font-size: 1.2rem;">
                    <i class="bi bi-shield-lock"></i>
                </div>
            </div>

            <div class="p-4">
                <form action="<?= $base_path ?>/change-password" method="POST">
                    <?= $csrf_input ?>

                    <!-- Mot de passe actuel -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold text-dark small">Mot de passe actuel</label>
                        <input type="password" class="form-control <?= !empty($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
                        <?php if (!empty($errors['current_password'])): ?>
                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['current_password'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Nouveau mot de passe -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold text-dark small">Nouveau mot de passe (min. 10 caractères)</label>
                        <input type="password" class="form-control <?= !empty($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" minlength="10" required>
                        <?php if (!empty($errors['new_password'])): ?>
                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['new_password'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold text-dark small">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" minlength="10" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['confirm_password'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $base_path ?>/dashboard" class="btn btn-light border px-4">Annuler</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Enregistrer le mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
