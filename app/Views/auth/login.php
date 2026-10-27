<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $errors */
/** @var array $old */
?>

<form action="<?= $base_path ?>/login" method="POST" autocomplete="on">
    <?= $csrf_input ?>

    <!-- E-mail -->
    <div class="mb-3">
        <label for="email" class="form-label fw-semibold text-dark small">Adresse E-mail</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email"
                   class="form-control bg-light border-start-0 <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                   id="email"
                   name="email"
                   placeholder="nom@exemple.com"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                   required
                   autofocus>
        </div>
        <?php if (!empty($errors['email'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['email'][0]) ?></div>
        <?php endif; ?>
    </div>

    <!-- Mot de passe -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label fw-semibold text-dark small mb-0">Mot de passe</label>
            <a href="<?= $base_path ?>/forgot-password" class="text-decoration-none small text-primary">Mot de passe oublié ?</a>
        </div>
        <div class="input-group mt-1">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password"
                   class="form-control bg-light border-start-0 border-end-0 <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password"
                   name="password"
                   placeholder="••••••••••••"
                   required>
            <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn">
                <i class="bi bi-eye" id="togglePasswordIcon"></i>
            </button>
        </div>
        <?php if (!empty($errors['password'])): ?>
            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
    </div>

    <!-- Se souvenir -->
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
        <label class="form-check-label small text-muted" for="rememberMe">
            Se souvenir de mon e-mail
        </label>
    </div>

    <!-- Bouton connexion -->
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
        </button>
    </div>

    <div class="text-center text-muted small mt-4 pt-3 border-top">
        <span>Compte par défaut : <strong>admin@carteapp.com</strong></span><br>
        <span>Mot de passe : <code>Admin@2026</code></span>
    </div>
</form>

<script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && pwdInput && icon) {
        toggleBtn.addEventListener('click', () => {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                pwdInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    }

    // Gestion mémorisation email dans localStorage
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.getElementById('rememberMe');
    const savedEmail = localStorage.getItem('carteapp_remember_email');

    if (savedEmail && emailInput && !emailInput.value) {
        emailInput.value = savedEmail;
        rememberCheckbox.checked = true;
    }

    document.querySelector('form').addEventListener('submit', () => {
        if (rememberCheckbox.checked && emailInput.value) {
            localStorage.setItem('carteapp_remember_email', emailInput.value);
        } else {
            localStorage.removeItem('carteapp_remember_email');
        }
    });
</script>
