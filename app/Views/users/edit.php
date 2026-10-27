<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $user */
/** @var array $roles */
/** @var array $etablissements */
/** @var array $assignedEtabIds */
/** @var array $errors */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modifier l'Utilisateur</h2>
        <p class="text-muted small mb-0"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
    </div>
    <a href="<?= $base_path ?>/users" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0">Modifier les informations</h6>
            </div>
            <div class="p-4">
                <form action="<?= $base_path ?>/users/<?= $user['id'] ?>/modifier" method="POST">
                    <?= $csrf_input ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Adresse e-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rôle utilisateur <span class="text-danger">*</span></label>
                            <?php if (!empty($isAdminPrincipal)): ?>
                                <input type="text" class="form-control bg-light fw-semibold" value="Administrateur principal (admin)" readonly>
                                <input type="hidden" name="role_id" value="<?= (int)$user['role_id'] ?>">
                                <div class="form-text small text-muted">Le rôle de l'administrateur principal est permanent.</div>
                            <?php else: ?>
                                <select name="role_id" class="form-select" required>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['libelle']) ?> (<?= htmlspecialchars($r['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Statut du compte <span class="text-danger">*</span></label>
                            <?php if (!empty($isAdminPrincipal)): ?>
                                <input type="text" class="form-control bg-light text-success fw-semibold" value="Actif (Permanent pour l'administrateur principal)" readonly>
                                <input type="hidden" name="statut" value="actif">
                                <div class="form-text small text-muted">Ce compte doit toujours rester actif.</div>
                            <?php else: ?>
                                <select name="statut" class="form-select" required>
                                    <option value="actif" <?= $user['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= $user['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                    <option value="verrouille" <?= $user['statut'] === 'verrouille' ? 'selected' : '' ?>>Verrouillé</option>
                                </select>
                                <?php if ($user['statut'] === 'verrouille'): ?>
                                    <div class="form-text text-warning small">Sélectionner "Actif" pour déverrouiller immédiatement ce compte.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Nouveau mot de passe (optionnel)</label>
                            <input type="password" name="nouveau_mot_de_passe" class="form-control" minlength="10" placeholder="Laisser vide pour ne pas modifier">
                            <div class="form-text small">Si modifié, l'utilisateur devra changer son mot de passe à la prochaine connexion.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Établissement(s) assigné(s)</label>
                            <?php if (count($etablissements) === 1): ?>
                                <?php $singleEtab = $etablissements[0]; ?>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?> (<?= htmlspecialchars($singleEtab['code'] ?? '') ?>)" readonly>
                                <input type="hidden" name="etablissements[]" value="<?= (int)$singleEtab['id'] ?>">
                            <?php else: ?>
                                <div class="border rounded p-3 bg-light" style="max-height: 180px; overflow-y: auto;">
                                    <?php foreach ($etablissements as $etab): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="etablissements[]"
                                                   value="<?= $etab['id'] ?>"
                                                   id="etabCheck<?= $etab['id'] ?>"
                                                   <?= in_array($etab['id'], $assignedEtabIds, false) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="etabCheck<?= $etab['id'] ?>">
                                                <strong><?= htmlspecialchars($etab['nom']) ?></strong> (<code><?= htmlspecialchars($etab['code']) ?></code>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= $base_path ?>/users" class="btn btn-light border px-4">Annuler</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
