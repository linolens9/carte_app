<?php
declare(strict_types=1);
/** @var string $content */
/** @var string $base_path */
/** @var string $app_name */
/** @var array $flashes */
/** @var string $csrf_token */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Connexion') ?> - <?= htmlspecialchars($app_name) ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $base_path ?>/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= $base_path ?>/assets/img/favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base_path ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>

<div class="auth-bg">
    <div class="auth-card">
        <!-- Logo / Brand -->
        <div class="text-center mb-4">
            <a href="<?= $base_path ?>/login" class="d-inline-block text-decoration-none">
                <img src="<?= $base_path ?>/assets/img/logo-login.png" alt="MBOLO" style="max-height: 125px; width: auto; max-width: 100%; object-fit: contain;">
            </a>
        </div>

        <!-- Flash messages -->
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show rounded-3 small mb-4" role="alert">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
