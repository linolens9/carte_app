<?php
/**
 * Liste des Modèles de Cartes (Templates)
 */
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Modèles de Cartes (Templates)</h4>
        <p class="text-secondary mb-0">Personnalisez les chartes graphiques, couleurs, éléments de sécurité et mentions officielles.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $base_path ?>/templates/creer" class="btn btn-primary-custom d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            <span>Nouveau modèle</span>
        </a>
    </div>
</div>

<!-- Filtres par cible -->
<div class="card-custom mb-4 p-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="btn-group" role="group">
            <a href="<?= $base_path ?>/templates" class="btn btn-sm <?= empty($cible_filter) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Tous les modèles
            </a>
            <a href="<?= $base_path ?>/templates?cible=eleve" class="btn btn-sm <?= ($cible_filter === 'eleve') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-backpack me-1"></i> Élèves (Scolaire)
            </a>
            <a href="<?= $base_path ?>/templates?cible=etudiant" class="btn btn-sm <?= ($cible_filter === 'etudiant') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-mortarboard me-1"></i> Étudiants (Universitaire)
            </a>
            <a href="<?= $base_path ?>/templates?cible=personnel" class="btn btn-sm <?= ($cible_filter === 'personnel') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-briefcase me-1"></i> Personnel & Agents
            </a>
        </div>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i> Format standardisé : <strong>CR-80 (85.6 × 53.98 mm)</strong>
        </div>
    </div>
</div>

<!-- Grille des Modèles -->
<div class="row g-4">
    <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="card-custom text-center p-5">
                <i class="bi bi-palette text-secondary display-3 mb-3"></i>
                <h5 class="fw-bold">Aucun modèle de carte trouvé</h5>
                <p class="text-muted mb-3">Créez votre premier modèle ou réinitialisez les modèles par défaut.</p>
                <a href="<?= $base_path ?>/templates/creer" class="btn btn-primary-custom mx-auto">
                    <i class="bi bi-plus-lg me-1"></i> Créer un modèle
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $tpl): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-custom h-100 d-flex flex-column">
                    <!-- Bannière avec dégradé des couleurs du modèle -->
                    <div style="height: 100px; background: linear-gradient(135deg, <?= htmlspecialchars($tpl['couleur_principale'] ?? '#1e3a8a') ?> 0%, <?= htmlspecialchars($tpl['couleur_secondaire'] ?? '#3b82f6') ?> 100%); position: relative; border-radius: 16px 16px 0 0;" class="p-3 text-white d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-white text-dark shadow-sm">
                                <?php if ($tpl['cible'] === 'eleve'): ?>
                                    <i class="bi bi-backpack text-primary me-1"></i> Élèves
                                <?php elseif ($tpl['cible'] === 'etudiant'): ?>
                                    <i class="bi bi-mortarboard text-success me-1"></i> Étudiants
                                <?php else: ?>
                                    <i class="bi bi-briefcase text-warning me-1"></i> Personnel
                                <?php endif; ?>
                            </span>
                            <span class="badge <?= $tpl['actif'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $tpl['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <div class="fw-bold text-truncate" style="font-size: 0.95rem;">
                            <?= htmlspecialchars($tpl['nom']) ?>
                        </div>
                    </div>

                    <div class="card-body p-3 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-2 text-secondary small">
                                <i class="bi bi-building me-1"></i>
                                <?= $tpl['etablissement_nom'] ? htmlspecialchars($tpl['etablissement_nom']) : '<span class="fst-italic">Modèle universel / Global</span>' ?>
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3 text-muted small">
                                <div>
                                    <i class="bi bi-card-heading me-1 text-primary"></i>
                                    <strong><?= (int)($tpl['nb_cartes'] ?? 0) ?></strong> cartes émises
                                </div>
                                <div>
                                    <i class="bi bi-aspect-ratio me-1 text-info"></i>
                                    CR-80 <?= htmlspecialchars($tpl['orientation']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-2">
                            <a href="<?= $base_path ?>/templates/<?= $tpl['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-eye me-1"></i> Aperçu 3D
                            </a>
                            <div class="d-flex align-items-center gap-1">
                                <a href="<?= $base_path ?>/templates/<?= $tpl['id'] ?>/dupliquer" class="btn btn-sm btn-light border rounded-circle" title="Dupliquer le modèle">
                                    <i class="bi bi-copy"></i>
                                </a>
                                <a href="<?= $base_path ?>/templates/<?= $tpl['id'] ?>/modifier" class="btn btn-sm btn-light border rounded-circle text-primary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="<?= $base_path ?>/templates/<?= $tpl['id'] ?>/supprimer" class="d-inline">
                                    <?= \App\Core\Csrf::inputField() ?>
                                    <button type="button" class="btn btn-sm btn-light border rounded-circle text-danger btn-confirm-delete" data-message="Supprimer ce modèle ?" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
