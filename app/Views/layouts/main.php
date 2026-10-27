<?php
declare(strict_types=1);
/** @var string $content */
/** @var string $base_path */
/** @var string $app_name */
/** @var array|null $current_user */
/** @var array|null $active_etab */
/** @var array $flashes */
/** @var string $csrf_token */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? $app_name) ?> - Plateforme unifiée de gestion des identités</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $base_path ?>/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= $base_path ?>/assets/img/favicon.png">

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= $base_path ?>/assets/css/app.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header" style="padding: 0 1.25rem;">
            <a href="<?= $base_path ?>/dashboard" class="sidebar-brand w-100 d-flex align-items-center justify-content-center text-decoration-none">
                <img src="<?= $base_path ?>/assets/img/logo-dashboard-white.png" alt="MBOLO - Plateforme unifiée de gestion des identités" style="max-height: 46px; width: auto; max-width: 100%; object-fit: contain;">
            </a>
        </div>

        <div class="sidebar-menu">
            <div class="menu-title">Principal</div>
            <a href="<?= $base_path ?>/dashboard" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/dashboard') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Tableau de bord</span>
            </a>
            <?php if (\App\Core\Auth::can('statistique.consulter')): ?>
                <a href="<?= $base_path ?>/statistiques" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/statistiques') ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Statistiques</span>
                </a>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasRole('admin', 'gestionnaire')): ?>
                <div class="menu-title">Gestion des Structures</div>
                <a href="<?= $base_path ?>/etablissements" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/etablissements') ? 'active' : '' ?>">
                    <i class="bi bi-buildings"></i>
                    <span>Établissements</span>
                </a>
            <?php endif; ?>

            <?php
                $etabType = $active_etab['type'] ?? '';
                $etabCat = $active_etab['categorie'] ?? '';
                $isAdminOrGest = \App\Core\Auth::hasRole('admin', 'gestionnaire');

                $isSchool = in_array($etabType, ['primaire', 'college', 'lycee'], true);
                $isHigherEd = in_array($etabType, ['universite', 'centre_formation'], true);
                $isPro = ($etabCat === 'professionnel') || in_array($etabType, ['entreprise', 'administration', 'association', 'ong', 'etablissement_prive'], true);

                $showEleves = !$isHigherEd && !$isPro;
                $showEtudiants = !$isSchool && !$isPro && ($isAdminOrGest || $isHigherEd);
                $showPersonnel = true;
            ?>

            <div class="menu-title">Bénéficiaires</div>
            <?php if ($showEleves): ?>
                <a href="<?= $base_path ?>/eleves" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/eleves') ? 'active' : '' ?>">
                    <i class="bi bi-backpack"></i>
                    <span>Élèves</span>
                </a>
            <?php endif; ?>
            <?php if ($showEtudiants): ?>
                <a href="<?= $base_path ?>/etudiants" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/etudiants') ? 'active' : '' ?>">
                    <i class="bi bi-mortarboard"></i>
                    <span>Étudiants</span>
                </a>
            <?php endif; ?>
            <?php if ($showPersonnel): ?>
                <a href="<?= $base_path ?>/personnels" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/personnels') ? 'active' : '' ?>">
                    <i class="bi bi-person-badge"></i>
                    <span>Personnel</span>
                </a>
            <?php endif; ?>

            <div class="menu-title">Cartes & Impression</div>
            <a href="<?= $base_path ?>/cartes" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/cartes') && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/templates') ? 'active' : '' ?>">
                <i class="bi bi-card-heading"></i>
                <span>Cartes d'identité</span>
            </a>
            <a href="<?= $base_path ?>/templates" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/templates') ? 'active' : '' ?>">
                <i class="bi bi-palette"></i>
                <span>Modèles de cartes</span>
            </a>
            <a href="<?= $base_path ?>/impressions" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/impressions') ? 'active' : '' ?>">
                <i class="bi bi-printer"></i>
                <span>Impression & Lots</span>
            </a>

            <?php if (\App\Core\Auth::hasRole('admin', 'gestionnaire')): ?>
                <div class="menu-title">Administration</div>
                <a href="<?= $base_path ?>/users" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/users') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Utilisateurs</span>
                </a>
                <?php if (\App\Core\Auth::hasRole('admin')): ?>
                    <a href="<?= $base_path ?>/roles" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/roles') ? 'active' : '' ?>">
                        <i class="bi bi-shield-lock"></i>
                        <span>Rôles & Droits</span>
                    </a>
                    <a href="<?= $base_path ?>/sauvegardes" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/sauvegardes') ? 'active' : '' ?>">
                        <i class="bi bi-database-check"></i>
                        <span>Sauvegardes</span>
                    </a>
                    <a href="<?= $base_path ?>/logs" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/logs') ? 'active' : '' ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>Journal d'audit</span>
                    </a>
                    <a href="<?= $base_path ?>/parametres" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/parametres') ? 'active' : '' ?>">
                        <i class="bi bi-gear"></i>
                        <span>Paramètres</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                    <?= strtoupper(substr($current_user['prenom'] ?? 'U', 0, 1)) . strtoupper(substr($current_user['nom'] ?? 'N', 0, 1)) ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <div class="text-white text-truncate fw-semibold" style="font-size: 0.88rem;">
                        <?= htmlspecialchars(($current_user['prenom'] ?? '') . ' ' . ($current_user['nom'] ?? '')) ?>
                    </div>
                    <div class="text-truncate text-secondary" style="font-size: 0.75rem;">
                        <?= htmlspecialchars($current_user['role_libelle'] ?? 'Utilisateur') ?>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <!-- Établissement switcher -->
                <?php 
                    $userAccessibleEtabs = \App\Core\Auth::getAccessibleEtablissements();
                ?>
                <?php if (\App\Core\Auth::hasRole('admin') || count($userAccessibleEtabs) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-3 py-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-building text-primary"></i>
                            <span class="fw-semibold text-truncate" style="max-width: 220px;">
                                <?= $active_etab ? htmlspecialchars($active_etab['nom']) : 'Tous les établissements' ?>
                            </span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 0.75rem;"></i>
                        </button>
                        <ul class="dropdown-menu shadow-sm" style="min-width: 260px;">
                            <li><h6 class="dropdown-header">Sélectionner le contexte</h6></li>
                            <?php if (\App\Core\Auth::hasRole('admin')): ?>
                                <li>
                                    <form action="<?= $base_path ?>/dashboard/switch-etablissement" method="POST">
                                        <?= \App\Core\Csrf::inputField() ?>
                                        <input type="hidden" name="etablissement_id" value="0">
                                        <button class="dropdown-item d-flex align-items-center justify-content-between <?= !$active_etab ? 'active' : '' ?>">
                                            <span><i class="bi bi-globe me-2"></i>Vue Globale (Tous)</span>
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <?php foreach ($userAccessibleEtabs as $etabItem): ?>
                                <li>
                                    <form action="<?= $base_path ?>/dashboard/switch-etablissement" method="POST">
                                        <?= \App\Core\Csrf::inputField() ?>
                                        <input type="hidden" name="etablissement_id" value="<?= $etabItem['id'] ?>">
                                        <button class="dropdown-item d-flex align-items-center justify-content-between <?= ($active_etab && $active_etab['id'] == $etabItem['id']) ? 'active' : '' ?>">
                                            <span class="text-truncate"><?= htmlspecialchars($etabItem['nom']) ?></span>
                                            <small class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($etabItem['code'] ?? '') ?></small>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif (!empty($active_etab)): ?>
                    <div class="d-flex align-items-center gap-2 rounded-pill px-3 py-2 bg-light border">
                        <i class="bi bi-building text-primary"></i>
                        <span class="fw-semibold text-truncate text-dark" style="max-width: 260px;">
                            <?= htmlspecialchars($active_etab['nom'] ?? '') ?>
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" style="font-size: 0.7rem;">
                            <?= htmlspecialchars($active_etab['code'] ?? '') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Topbar Right -->
            <div class="d-flex align-items-center gap-3">
                <a href="<?= $base_path ?>/change-password" class="btn btn-outline-secondary btn-sm rounded-pill" title="Modifier mot de passe">
                    <i class="bi bi-key me-1"></i>Mot de passe
                </a>
                <a href="<?= $base_path ?>/logout" class="btn btn-outline-danger btn-sm rounded-pill" title="Se déconnecter">
                    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                </a>
            </div>
        </header>

        <!-- Page Body -->
        <main class="page-body">
            <!-- Flash Messages -->
            <?php foreach ($flashes as $type => $messages): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill"></i>
                            <div><?= htmlspecialchars($msg) ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Toggle sidebar mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', () => {
            appSidebar.classList.toggle('open');
        });
    }

    // Confirmation pour suppression générique
    document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = btn.closest('form');
            const message = btn.getAttribute('data-message') || "Cette action est irréversible. Continuer ?";
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Oui, continuer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // 3D Flip Card handler (clic sur le bouton ou clic direct sur la carte 3D)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.btn-flip-trigger');
        if (trigger) {
            e.preventDefault();
            const targetId = trigger.getAttribute('data-target');
            const cardEl = document.querySelector(targetId);
            if (cardEl) {
                cardEl.classList.toggle('flipped');
                const isFlipped = cardEl.classList.contains('flipped');
                trigger.innerHTML = isFlipped ? '<i class="bi bi-arrow-repeat me-1"></i> Voir le Recto' : '<i class="bi bi-arrow-repeat me-1"></i> Retourner la carte (Verso)';
            }
            return;
        }

        const card3d = e.target.closest('.cr80-card-3d');
        if (card3d) {
            card3d.classList.toggle('flipped');
            const targetSelector = `[data-target="#${card3d.id}"]`;
            const linkedBtn = document.querySelector(targetSelector);
            if (linkedBtn) {
                const isFlipped = card3d.classList.contains('flipped');
                linkedBtn.innerHTML = isFlipped ? '<i class="bi bi-arrow-repeat me-1"></i> Voir le Recto' : '<i class="bi bi-arrow-repeat me-1"></i> Retourner la carte (Verso)';
            }
        }
    });
</script>

</body>
</html>
