<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $roles */
/** @var array $etablissements */
/** @var array $errors */
/** @var array $old */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Créer un Utilisateur</h2>
        <p class="text-muted small mb-0">Création de compte, assignation de rôle et affectation aux établissements</p>
    </div>
    <a href="<?= $base_path ?>/users" class="btn btn-light border rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0">Informations du compte</h6>
            </div>
            <div class="p-4">
                <form action="<?= $base_path ?>/users/creer" method="POST">
                    <?= $csrf_input ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" required>
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['nom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" required>
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['prenom'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Adresse e-mail (identifiant) <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                            <?php if (!empty($errors['email'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['email'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                <i class="bi bi-telephone me-1 text-primary"></i>Téléphone
                            </label>
                            <input type="text" name="telephone" id="userTelephone" class="form-control"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+241 07 12 34 56">
                            <div class="form-text small">Recommandé : indicatif pays (ex: +241 ou 241) pour WhatsApp et SMS.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rôle utilisateur <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select <?= !empty($errors['role_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Sélectionner un rôle</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($old['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['libelle']) ?> (<?= htmlspecialchars($r['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Statut initial</label>
                            <select name="statut" class="form-select">
                                <option value="actif" selected>Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>

                        <!-- Mot de passe avec générateur et bouton copier -->
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Mot de passe temporaire <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill" id="btnGenPassword" style="font-size: 0.78rem;">
                                    <i class="bi bi-magic me-1"></i>Générer un mot de passe
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="password" name="mot_de_passe" id="userPassword"
                                       class="form-control font-monospace <?= !empty($errors['mot_de_passe']) ? 'is-invalid' : '' ?>"
                                       minlength="10" placeholder="Minimum 10 caractères" required>
                                <button type="button" class="btn btn-outline-secondary" id="btnTogglePassword" title="Afficher/Masquer le mot de passe">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnCopyPassword" title="Copier le mot de passe">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="form-text small">L'utilisateur sera tenu de modifier ce mot de passe temporaire lors de sa première connexion.</div>
                            <?php if (!empty($errors['mot_de_passe'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['mot_de_passe'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- SECTION NOTIFICATIONS & ENVOI DES IDENTIFIANTS -->
                        <div class="col-md-12">
                            <div class="p-3 rounded-3 border bg-light mt-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold text-dark small">
                                        <i class="bi bi-send-check text-primary me-1"></i>Transmission des identifiants au nouvel utilisateur
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Multi-canaux</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Sélectionnez les canaux par lesquels l'utilisateur recevra ses accès (identifiant, mot de passe temporaire et lien d'accès).
                                </p>

                                <div class="row g-2">
                                    <!-- 1. EMAIL -->
                                    <div class="col-md-4">
                                        <div class="form-check p-3 bg-white rounded-3 border h-100">
                                            <input class="form-check-input" type="checkbox" name="notify_email" value="1" id="notifyEmail" checked>
                                            <label class="form-check-label d-block ms-1" for="notifyEmail">
                                                <div class="fw-bold small text-dark d-flex align-items-center gap-1">
                                                    <i class="bi bi-envelope-at text-primary"></i> E-mail
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    Envoi direct d'un courriel HTML sécurisé avec lien de connexion.
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 2. WHATSAPP -->
                                    <div class="col-md-4">
                                        <div class="form-check p-3 bg-white rounded-3 border h-100">
                                            <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notifyWhatsapp" checked>
                                            <label class="form-check-label d-block ms-1" for="notifyWhatsapp">
                                                <div class="fw-bold small text-dark d-flex align-items-center gap-1">
                                                    <i class="bi bi-whatsapp text-success"></i> WhatsApp
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    Envoi API Cloud ou lien direct Click-to-Chat (wa.me) prêt à l'emploi.
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 3. SMS CLASSIQUE -->
                                    <div class="col-md-4">
                                        <div class="form-check p-3 bg-white rounded-3 border h-100">
                                            <input class="form-check-input" type="checkbox" name="notify_sms" value="1" id="notifySms" checked>
                                            <label class="form-check-label d-block ms-1" for="notifySms">
                                                <div class="fw-bold small text-dark d-flex align-items-center gap-1">
                                                    <i class="bi bi-chat-left-text text-info"></i> SMS classique
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    Passerelle SMS ou lien direct pour messagerie mobile native.
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Établissement(s) assigné(s)</label>
                            <?php if (count($etablissements) === 1): ?>
                                <?php $singleEtab = $etablissements[0]; ?>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($singleEtab['nom']) ?> (<?= htmlspecialchars($singleEtab['code'] ?? '') ?>)" readonly>
                                <input type="hidden" name="etablissements[]" value="<?= (int)$singleEtab['id'] ?>">
                            <?php else: ?>
                                <div class="border rounded p-3 bg-light" style="max-height: 180px; overflow-y: auto;">
                                    <?php if (empty($etablissements)): ?>
                                        <div class="text-muted small">Aucun établissement disponible.</div>
                                    <?php else: ?>
                                        <?php foreach ($etablissements as $etab): ?>
                                            <?php
                                                $isChecked = false;
                                                if (isset($old['etablissements']) && is_array($old['etablissements'])) {
                                                    $isChecked = in_array((string)$etab['id'], array_map('strval', $old['etablissements']), true);
                                                } elseif (!empty($activeEtabId)) {
                                                    $isChecked = ((int)$etab['id'] === (int)$activeEtabId);
                                                }
                                            ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="etablissements[]" value="<?= $etab['id'] ?>" id="etabCheck<?= $etab['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="etabCheck<?= $etab['id'] ?>">
                                                    <strong><?= htmlspecialchars($etab['nom']) ?></strong> (<code><?= htmlspecialchars($etab['code']) ?></code>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text small">Les administrateurs ont automatiquement accès à tous les établissements. Pour les autres profils, cochez les établissements autorisés.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= $base_path ?>/users" class="btn btn-light border px-4">Annuler</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-person-check me-1"></i>Créer et Transmettre les accès
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwdInput = document.getElementById('userPassword');
    const btnToggle = document.getElementById('btnTogglePassword');
    const toggleIcon = document.getElementById('toggleIcon');
    const btnGen = document.getElementById('btnGenPassword');
    const btnCopy = document.getElementById('btnCopyPassword');
    const phoneInput = document.getElementById('userTelephone');
    const chkWa = document.getElementById('notifyWhatsapp');
    const chkSms = document.getElementById('notifySms');

    // Générateur de mot de passe sécurisé (12 caractères : majuscules, minuscules, chiffres, symboles)
    function generateSecurePassword() {
        const uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lowers = 'abcdefghjkmnpqrstuvwxyz';
        const numbers = '23456789';
        const symbols = '!@#$%&*';
        const all = uppers + lowers + numbers + symbols;

        let pwd = '';
        pwd += uppers.charAt(Math.floor(Math.random() * uppers.length));
        pwd += lowers.charAt(Math.floor(Math.random() * lowers.length));
        pwd += numbers.charAt(Math.floor(Math.random() * numbers.length));
        pwd += symbols.charAt(Math.floor(Math.random() * symbols.length));

        for (let i = 4; i < 12; i++) {
            pwd += all.charAt(Math.floor(Math.random() * all.length));
        }

        // Mélange
        return pwd.split('').sort(() => 0.5 - Math.random()).join('');
    }

    if (btnGen) {
        btnGen.addEventListener('click', function() {
            const pwd = generateSecurePassword();
            pwdInput.value = pwd;
            pwdInput.type = 'text';
            toggleIcon.className = 'bi bi-eye-slash';
            pwdInput.focus();
        });
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                pwdInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        });
    }

    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            if (!pwdInput.value) {
                alert('Veuillez d\'abord saisir ou générer un mot de passe.');
                return;
            }
            navigator.clipboard.writeText(pwdInput.value).then(() => {
                const origHtml = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                setTimeout(() => { btnCopy.innerHTML = origHtml; }, 1800);
            });
        });
    }

    // Auto-cocher WhatsApp et SMS lorsque le numéro de téléphone est renseigné
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length >= 4) {
                chkWa.checked = true;
                chkSms.checked = true;
            }
        });
    }
});
</script>
