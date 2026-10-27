<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var string $token */
/** @var string $email */
?>

<div class="mb-4">
    <h5 class="fw-bold text-dark">Nouveau mot de passe</h5>
    <p class="text-muted small">Définissez un nouveau mot de passe pour le compte <strong><?= htmlspecialchars($email) ?></strong>.</p>
</div>

<form action="<?= $base_path ?>/reset-password" method="POST">
    <?= $csrf_input ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold text-dark small">Nouveau mot de passe (10 car. min)</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control bg-light border-start-0" id="password" name="password" minlength="10" required autofocus>
        </div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold text-dark small">Confirmation</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control bg-light border-start-0" id="password_confirmation" name="password_confirmation" minlength="10" required>
        </div>
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
            <i class="bi bi-check-circle me-2"></i>Mettre à jour mon mot de passe
        </button>
    </div>
</form>
