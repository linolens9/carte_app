<?php
declare(strict_types=1);
/** @var array $user */
/** @var array $etablissements */
/** @var array $errors */
/** @var array $old */
/** @var string $base_path */
/** @var string $csrf_input */
?>

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= $base_path ?>/dashboard" class="text-decoration-none">Tableau de bord</a></li>
            <li class="breadcrumb-item active" aria-current="page">Mon Profil</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0">Mon Profil</h2>
            <p class="text-muted small mb-0">Consultez et modifiez vos informations personnelles et vos identifiants d'accès.</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne Gauche : Aperçu du profil -->
    <div class="col-lg-4">
        <div class="card-custom text-center p-4 mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow-sm mb-3 fw-bold" 
                 style="width: 86px; height: 86px; font-size: 2rem; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <?= strtoupper(substr($user['prenom'] ?? 'U', 0, 1)) . strtoupper(substr($user['nom'] ?? 'N', 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></h5>
            <p class="text-muted small mb-2"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fs-6">
                    <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($user['role_libelle'] ?? 'Utilisateur') ?>
                </span>
            </div>

            <hr class="my-4 text-muted opacity-25">

            <div class="text-start">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="bi bi-circle-fill text-success me-2" style="font-size: 0.65rem;"></i>Statut</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                        <?= ucfirst(htmlspecialchars($user['statut'] ?? 'actif')) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small"><i class="bi bi-calendar3 me-2"></i>Inscrit le</span>
                    <span class="small fw-semibold text-dark">
                        <?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small"><i class="bi bi-clock-history me-2"></i>Dernière connexion</span>
                    <span class="small fw-semibold text-dark">
                        <?= !empty($user['derniere_connexion']) ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : 'Première session' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Établissements accessibles -->
        <div class="card-custom p-4">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-buildings text-primary"></i>Établissements accessibles
            </h6>
            <?php if (empty($etablissements)): ?>
                <div class="alert alert-light border small text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>Aucun établissement affecté ou accès global restreint.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush small">
                    <?php foreach ($etablissements as $etab): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="text-truncate me-2 fw-semibold text-dark">
                                <i class="bi bi-building me-1 text-muted"></i><?= htmlspecialchars($etab['nom']) ?>
                            </span>
                            <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($etab['code'] ?? '') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne Droite : Formulaire d'édition -->
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Modifier mes informations</h5>
                    <small class="text-muted">Mettez à jour vos informations de contact et votre mot de passe de connexion.</small>
                </div>
                <div class="sidebar-brand-icon" style="width: 42px; height: 42px; font-size: 1.2rem;">
                    <i class="bi bi-person-gear"></i>
                </div>
            </div>

            <div class="p-4">
                <form action="<?= $base_path ?>/profil" method="POST" autocomplete="off">
                    <?= $csrf_input ?>

                    <!-- Section Informations personnelles -->
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-badge text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Informations personnelles</h6>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nom" class="form-label fw-semibold text-dark small">Nom <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>" 
                                   id="nom" 
                                   name="nom" 
                                   value="<?= htmlspecialchars($old['nom'] ?? $user['nom'] ?? '') ?>" 
                                   required 
                                   placeholder="Votre nom de famille">
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="prenom" class="form-label fw-semibold text-dark small">Prénom <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>" 
                                   id="prenom" 
                                   name="prenom" 
                                   value="<?= htmlspecialchars($old['prenom'] ?? $user['prenom'] ?? '') ?>" 
                                   required 
                                   placeholder="Votre prénom">
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-7">
                            <label for="email" class="form-label fw-semibold text-dark small">Adresse e-mail <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>" 
                                       required 
                                       placeholder="nom@exemple.com">
                            </div>
                            <?php if (!empty($errors['email'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['email'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-5">
                            <label for="telephone" class="form-label fw-semibold text-dark small">Téléphone</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="tel" 
                                       class="form-control <?= !empty($errors['telephone']) ? 'is-invalid' : '' ?>" 
                                       id="telephone" 
                                       name="telephone" 
                                       value="<?= htmlspecialchars($old['telephone'] ?? $user['telephone'] ?? '') ?>" 
                                       placeholder="+241 00 00 00 00">
                            </div>
                            <?php if (!empty($errors['telephone'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['telephone'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section Sécurité et Mot de passe -->
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock text-primary fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0">Modifier mon mot de passe</h6>
                        </div>
                        <span class="badge bg-light text-muted border">Optionnel</span>
                    </div>

                    <div class="alert alert-light border rounded-3 p-3 mb-3 small text-muted">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                        Laissez les champs suivants <strong>vides</strong> si vous souhaitez conserver votre mot de passe actuel.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="current_password" class="form-label fw-semibold text-dark small">Mot de passe actuel</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control <?= !empty($errors['current_password']) ? 'is-invalid' : '' ?>" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Requis uniquement si vous changez de mot de passe">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="#current_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (!empty($errors['current_password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['current_password'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="new_password" class="form-label fw-semibold text-dark small">Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control <?= !empty($errors['new_password']) ? 'is-invalid' : '' ?>" 
                                       id="new_password" 
                                       name="new_password" 
                                       minlength="10" 
                                       placeholder="10 caractères minimum">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="#new_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (!empty($errors['new_password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['new_password'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold text-dark small">Confirmer le nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       minlength="10" 
                                       placeholder="Répétez le nouveau mot de passe">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="#confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (!empty($errors['confirm_password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['confirm_password'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= $base_path ?>/dashboard" class="btn btn-light border px-4 rounded-pill">
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Basculer l'affichage du mot de passe
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetSelector = btn.getAttribute('data-target');
            const targetInput = document.querySelector(targetSelector);
            if (targetInput) {
                const isPassword = targetInput.getAttribute('type') === 'password';
                targetInput.setAttribute('type', isPassword ? 'text' : 'password');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);
                }
            }
        });
    });
</script>
