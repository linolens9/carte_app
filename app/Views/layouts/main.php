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
        <div class="sidebar-header d-flex align-items-center justify-content-between" style="padding: 0 1.25rem;">
            <a href="<?= $base_path ?>/dashboard" class="sidebar-brand d-flex align-items-center text-decoration-none flex-grow-1 overflow-hidden">
                <img src="<?= $base_path ?>/assets/img/logo-dashboard-white.png" alt="MBOLO - Plateforme unifiée de gestion des identités" style="max-height: 44px; width: auto; max-width: 180px; object-fit: contain;">
            </a>
            <!-- Bouton Croix (X) pour fermer la sidebar sur mobile/tablette -->
            <button type="button" class="btn btn-sm text-white-50 p-0 d-lg-none sidebar-close-btn" id="sidebarClose" aria-label="Fermer le menu" title="Fermer le menu">
                <i class="bi bi-x-lg fs-5 text-white"></i>
            </button>
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

            <a href="<?= $base_path ?>/profil" class="nav-link-custom <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/profil') ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <a href="<?= $base_path ?>/profil" class="d-flex align-items-center gap-3 text-decoration-none" title="Accéder à mon profil">
                <?php if (!empty($current_user['photo'])): ?>
                    <img src="<?= $base_path ?>/<?= htmlspecialchars($current_user['photo']) ?>" alt="Photo de profil" class="rounded-circle object-fit-cover flex-shrink-0 border border-secondary" style="width: 40px; height: 40px;">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px;">
                        <?= strtoupper(substr($current_user['prenom'] ?? 'U', 0, 1)) . strtoupper(substr($current_user['nom'] ?? 'N', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden flex-1">
                    <div class="text-white text-truncate fw-semibold" style="font-size: 0.88rem;">
                        <?= htmlspecialchars(($current_user['prenom'] ?? '') . ' ' . ($current_user['nom'] ?? '')) ?>
                    </div>
                    <div class="text-truncate text-secondary" style="font-size: 0.75rem;">
                        <?= htmlspecialchars($current_user['role_libelle'] ?? 'Utilisateur') ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-secondary small ms-auto"></i>
            </a>
        </div>
    </aside>

    <!-- Overlay sombre pour fermer la sidebar au toucher sur smartphone -->
    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop" aria-hidden="true"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-2 gap-sm-3 overflow-hidden">
                <button class="btn btn-light d-lg-none flex-shrink-0" id="sidebarToggle" aria-label="Menu latéral" title="Menu">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <!-- Établissement switcher -->
                <?php 
                    $userAccessibleEtabs = \App\Core\Auth::getAccessibleEtablissements();
                ?>
                <?php if (\App\Core\Auth::hasRole('admin') || count($userAccessibleEtabs) > 1): ?>
                    <div class="dropdown text-truncate">
                        <button class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-2 px-sm-3 py-1 py-sm-2 etab-selector-btn" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-building text-primary"></i>
                            <span class="fw-semibold text-truncate etab-name-truncate">
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
                    <div class="d-flex align-items-center gap-2 rounded-pill px-2 px-sm-3 py-1 py-sm-2 bg-light border etab-selector-btn">
                        <i class="bi bi-building text-primary"></i>
                        <span class="fw-semibold text-truncate text-dark etab-name-truncate">
                            <?= htmlspecialchars($active_etab['nom'] ?? '') ?>
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace d-none d-sm-inline" style="font-size: 0.7rem;">
                            <?= htmlspecialchars($active_etab['code'] ?? '') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Topbar Right -->
            <div class="d-flex align-items-center gap-2">
                <!-- Boutons bureau (grand écran) -->
                <div class="d-none d-md-flex align-items-center gap-2">
                    <a href="<?= $base_path ?>/logout" class="btn btn-outline-danger btn-sm rounded-pill" title="Se déconnecter">
                        <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                    </a>
                </div>

                <!-- Menu Utilisateur (Mobile & Dropdown Rapide) -->
                <div class="dropdown">
                    <button class="btn btn-light border rounded-pill d-flex align-items-center gap-2 p-1 pe-2 topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Menu utilisateur">
                        <?php if (!empty($current_user['photo'])): ?>
                            <img src="<?= $base_path ?>/<?= htmlspecialchars($current_user['photo']) ?>" alt="Avatar" class="rounded-circle object-fit-cover border" style="width: 32px; height: 32px;">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                <?= strtoupper(substr($current_user['prenom'] ?? 'U', 0, 1)) . strtoupper(substr($current_user['nom'] ?? 'N', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down text-muted" style="font-size: 0.7rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 230px;">
                        <li class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                            <?php if (!empty($current_user['photo'])): ?>
                                <img src="<?= $base_path ?>/<?= htmlspecialchars($current_user['photo']) ?>" alt="Avatar" class="rounded-circle object-fit-cover border flex-shrink-0" style="width: 38px; height: 38px;">
                            <?php endif; ?>
                            <div class="overflow-hidden flex-1">
                                <div class="fw-semibold text-truncate"><?= htmlspecialchars(($current_user['prenom'] ?? '') . ' ' . ($current_user['nom'] ?? '')) ?></div>
                                <small class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($current_user['role_libelle'] ?? 'Utilisateur') ?></small>
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="<?= $base_path ?>/profil">
                                <i class="bi bi-person-circle text-muted me-2"></i>Mon Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="<?= $base_path ?>/logout">
                                <i class="bi bi-box-arrow-right me-2"></i>Se déconnecter
                            </a>
                        </li>
                    </ul>
                </div>
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
    // Gestion complète de la Sidebar Mobile (ouverture / fermeture / croix / overlay / touche Échap)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const appSidebar = document.getElementById('appSidebar');

    function openSidebar() {
        if (appSidebar) appSidebar.classList.add('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (appSidebar) appSidebar.classList.remove('open');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (appSidebar && appSidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    // Fermeture avec la touche Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && appSidebar && appSidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Fermeture automatique lors du clic sur un lien du menu sur smartphone
    if (appSidebar) {
        appSidebar.querySelectorAll('.nav-link-custom').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
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
