<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
?>

<div class="mb-4">
    <h5 class="fw-bold text-dark">Récupération de mot de passe</h5>
    <p class="text-muted small">Indiquez votre adresse e-mail pour recevoir un lien temporaire sécurisé (validité 60 minutes).</p>
</div>

<form action="<?= $base_path ?>/forgot-password" method="POST">
    <?= $csrf_input ?>

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold text-dark small">Adresse E-mail</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control bg-light border-start-0" id="email" name="email" placeholder="nom@exemple.com" required autofocus>
        </div>
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
            <i class="bi bi-send me-2"></i>Envoyer le lien
        </button>
    </div>

    <div class="text-center">
        <a href="<?= $base_path ?>/login" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i>Retour à la connexion
        </a>
    </div>
</form>
