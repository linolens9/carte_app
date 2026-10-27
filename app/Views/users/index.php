<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $users */
/** @var string $search */

$newCreds = \App\Core\Session::get('user_credentials_created');
if ($newCreds) {
    \App\Core\Session::remove('user_credentials_created');
}
?>

<?php if (!empty($newCreds)): ?>
<!-- BANNIÈRE RÉCAPITULATIF DES IDENTIFIANTS -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border-left: 5px solid #2563eb !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i class="bi bi-person-check-fill fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        Compte créé : <?= htmlspecialchars($newCreds['recipient']['name']) ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" style="font-size: 0.75rem;">
                            <?= htmlspecialchars($newCreds['recipient']['role']) ?>
                        </span>
                    </h5>
                    <div class="text-muted small">
                        Identifiant : <strong><?= htmlspecialchars($newCreds['recipient']['email']) ?></strong>
                        <?php if (!empty($newCreds['recipient']['phone'])): ?>
                            &bull; Téléphone : <strong><?= htmlspecialchars($newCreds['recipient']['phone']) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#credentialsModal">
                    <i class="bi bi-share me-1"></i>Voir & Transmettre les identifiants
                </button>
            </div>
        </div>

        <!-- Statuts des notifications -->
        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
            <!-- Email -->
            <?php if (($newCreds['email']['status'] ?? '') === 'sent'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle p-2">
                    <i class="bi bi-envelope-check-fill me-1"></i>Email envoyé avec succès
                </span>
            <?php elseif (($newCreds['email']['status'] ?? '') === 'simulated'): ?>
                <span class="badge bg-info-subtle text-info border border-info-subtle p-2" title="Journalisé dans storage/logs/mail_simulations.log">
                    <i class="bi bi-file-earmark-text-fill me-1"></i>Email simulé (logs locaux)
                </span>
            <?php elseif (($newCreds['email']['status'] ?? '') === 'failed'): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2">
                    <i class="bi bi-envelope-x-fill me-1"></i>Échec Email : <?= htmlspecialchars($newCreds['email']['message']) ?>
                </span>
            <?php endif; ?>

            <!-- WhatsApp -->
            <?php if (!empty($newCreds['whatsapp_link'])): ?>
                <a href="<?= htmlspecialchars($newCreds['whatsapp_link']) ?>" target="_blank" class="badge bg-success text-white text-decoration-none p-2 shadow-sm">
                    <i class="bi bi-whatsapp me-1"></i>Envoyer sur WhatsApp (Prêt) &rarr;
                </a>
            <?php elseif (($newCreds['whatsapp']['status'] ?? '') === 'warning'): ?>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2">
                    <i class="bi bi-exclamation-circle me-1"></i>WhatsApp : Pas de téléphone renseigné
                </span>
            <?php endif; ?>

            <!-- SMS -->
            <?php if (!empty($newCreds['sms_link'])): ?>
                <a href="<?= htmlspecialchars($newCreds['sms_link']) ?>" class="badge bg-primary text-white text-decoration-none p-2 shadow-sm">
                    <i class="bi bi-chat-left-dots-fill me-1"></i>Envoyer par SMS (Prêt) &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODALE DÉTAILLÉE DES IDENTIFIANTS -->
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-labelledby="credentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                    <h6 class="modal-title fw-bold" id="credentialsModalLabel">
                        Identifiants d'accès — <?= htmlspecialchars($newCreds['recipient']['name']) ?>
                    </h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border rounded-3 p-3 mb-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small fw-bold text-uppercase">Identifiant (Email)</div>
                            <div class="fw-bold fs-6 text-dark select-all"><?= htmlspecialchars($newCreds['credentials']['email']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small fw-bold text-uppercase">Mot de passe temporaire</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-monospace px-3 py-2 fs-6" id="modalPlainPassword">
                                    <?= htmlspecialchars($newCreds['credentials']['password']) ?>
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnCopyModalPwd" title="Copier le mot de passe">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 border-top pt-2 mt-2">
                            <div class="text-muted small">
                                <i class="bi bi-link-45deg me-1"></i>Lien de connexion :
                                <a href="<?= htmlspecialchars($newCreds['credentials']['login_url']) ?>" target="_blank" class="fw-semibold">
                                    <?= htmlspecialchars($newCreds['credentials']['login_url']) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS D'EXPÉDITION MULTI-CANAUX -->
                <h6 class="fw-bold text-dark mb-3">
                    <i class="bi bi-send text-primary me-2"></i>Actions d'expédition rapide :
                </h6>

                <div class="row g-3 mb-4">
                    <!-- CANAL WHATSAPP -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-whatsapp text-success fs-5"></i> WhatsApp
                                    </span>
                                    <?php if (!empty($newCreds['whatsapp_link'])): ?>
                                        <span class="badge bg-success-subtle text-success">Prêt</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Non configuré</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mb-3">
                                    Ouvre directement WhatsApp Web ou l'application mobile avec le message pré-rempli contenant tous les accès.
                                </p>
                            </div>
                            <?php if (!empty($newCreds['whatsapp_link'])): ?>
                                <a href="<?= htmlspecialchars($newCreds['whatsapp_link']) ?>" target="_blank" class="btn btn-success fw-semibold w-100 shadow-sm">
                                    <i class="bi bi-whatsapp me-2"></i>Ouvrir WhatsApp
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-light border text-muted w-100" disabled>
                                    Téléphone non renseigné
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CANAL SMS CLASSIQUE -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="bi bi-chat-left-dots text-info fs-5"></i> Messagerie classique (SMS)
                                    </span>
                                    <?php if (!empty($newCreds['sms_link'])): ?>
                                        <span class="badge bg-primary-subtle text-primary">Prêt</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Non configuré</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mb-3">
                                    Ouvre l'application SMS native avec le texte court prêt à l'envoi vers le destinataire.
                                </p>
                            </div>
                            <?php if (!empty($newCreds['sms_link'])): ?>
                                <a href="<?= htmlspecialchars($newCreds['sms_link']) ?>" class="btn btn-outline-primary fw-semibold w-100 shadow-sm">
                                    <i class="bi bi-chat-left-dots me-2"></i>Ouvrir dans SMS
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-light border text-muted w-100" disabled>
                                    Téléphone non renseigné
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Aperçu du message complet et copie -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-muted mb-0">Message complet (pour partage manuel)</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" id="btnCopyFullMessage">
                            <i class="bi bi-clipboard me-1"></i>Copier tout le message
                        </button>
                    </div>
                    <textarea class="form-control font-monospace small bg-light" id="fullMessageTextarea" rows="5" readonly><?= htmlspecialchars($newCreds['summary_text']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 px-4 rounded-bottom-4">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold text-dark mb-1">Gestion des Utilisateurs</h2>
        <p class="text-muted small mb-0">Contrôle d'accès, rôles et affectation aux établissements</p>
    </div>
    <a href="<?= $base_path ?>/users/creer" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
        <i class="bi bi-person-plus me-1"></i>Créer un utilisateur
    </a>
</div>

<!-- Filtre -->
<div class="card-custom mb-4">
    <div class="p-3">
        <form method="GET" action="<?= $base_path ?>/users" class="row g-2 align-items-center">
            <div class="<?= count($etablissements) > 1 ? 'col-md-6' : 'col-md-10' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Rechercher par nom, prénom ou email..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <?php if (count($etablissements) > 1): ?>
            <div class="col-md-4">
                <select name="etab" class="form-select bg-light">
                    <?php if (\App\Core\Auth::hasRole('admin')): ?>
                        <option value="">Tous les établissements</option>
                    <?php endif; ?>
                    <?php foreach ($etablissements as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= ($selectedEtabId === (int)$e['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nom']) ?> (<?= htmlspecialchars($e['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php elseif (!empty($selectedEtabId)): ?>
                <input type="hidden" name="etab" value="<?= (int)$selectedEtabId ?>">
            <?php endif; ?>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1"><i class="bi bi-filter me-1"></i>Filtrer</button>
                <?php if ($search !== '' || $selectedEtabId !== null): ?>
                    <a href="<?= $base_path ?>/users" class="btn btn-light border" title="Réinitialiser les filtres"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card-custom">
    <div class="p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2"></i>
                <h6 class="fw-bold">Aucun utilisateur trouvé</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Rôle</th>
                            <th>Établissement(s)</th>
                            <th>Téléphone</th>
                            <th>Dernière connexion</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center"
                                             style="width: 38px; height: 38px;">
                                            <?= strtoupper(substr($u['prenom'], 0, 1)) . strtoupper(substr($u['nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $badgeClass = match($u['role_code']) {
                                            'admin' => 'badge-subtle-danger',
                                            'gestionnaire' => 'badge-subtle-primary',
                                            'responsable' => 'badge-subtle-warning',
                                            'operateur' => 'badge-subtle-success',
                                            default => 'badge-subtle-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($u['role_libelle']) ?></span>
                                </td>
                                <td>
                                    <?php if ($u['role_code'] === 'admin'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-globe me-1"></i>Tous (Admin)
                                        </span>
                                    <?php elseif (!empty($u['etablissements_codes'])): ?>
                                        <?php
                                            $codes = explode('||', $u['etablissements_codes']);
                                            $noms = explode('||', $u['etablissements_noms']);
                                        ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($codes as $idx => $code): ?>
                                                <span class="badge badge-subtle-primary" title="<?= htmlspecialchars($noms[$idx] ?? $code) ?>">
                                                    <?= htmlspecialchars($code) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border" title="Aucun établissement assigné">
                                            <i class="bi bi-exclamation-circle me-1"></i>Non assigné
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($u['telephone'])): ?>
                                        <?php $waPhone = \App\Services\NotificationService::normalizePhoneNumber($u['telephone']); ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span><?= htmlspecialchars($u['telephone']) ?></span>
                                            <a href="https://wa.me/<?= $waPhone ?>" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle p-1" title="Contacter sur WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                            <a href="sms:<?= htmlspecialchars($u['telephone']) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle p-1" title="Envoyer un SMS">
                                                <i class="bi bi-chat-left-dots"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $u['derniere_connexion'] ? date('d/m/Y H:i', strtotime($u['derniere_connexion'])) : 'Jamais' ?>
                                </td>
                                <td>
                                    <?php if ($u['statut'] === 'actif'): ?>
                                        <span class="badge badge-subtle-success">Actif</span>
                                    <?php elseif ($u['statut'] === 'verrouille'): ?>
                                        <span class="badge badge-subtle-danger" title="Compte verrouillé">Verrouillé</span>
                                    <?php else: ?>
                                        <span class="badge badge-subtle-secondary">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?= $base_path ?>/users/<?= $u['id'] ?>/modifier" class="btn btn-light btn-sm border" title="Modifier les informations">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php 
                                            // L'administrateur principal (rôle 'admin' ou ID 1) doit uniquement modifier ses informations
                                            $isAdminPrincipal = ($u['role_code'] === 'admin' || (int)$u['id'] === 1);
                                        ?>

                                        <?php if (!$isAdminPrincipal): ?>
                                            <form action="<?= $base_path ?>/users/<?= $u['id'] ?>/toggle-statut" method="POST" class="d-inline">
                                                <?= $csrf_input ?>
                                                <button type="submit" class="btn btn-light btn-sm border"
                                                        title="<?= $u['statut'] === 'actif' ? 'Désactiver' : 'Activer / Déverrouiller' ?>">
                                                    <i class="bi bi-power <?= $u['statut'] === 'actif' ? 'text-danger' : 'text-success' ?>"></i>
                                                </button>
                                            </form>

                                            <?php if ($u['id'] !== \App\Core\Auth::id()): ?>
                                                <form action="<?= $base_path ?>/users/<?= $u['id'] ?>/supprimer" method="POST" class="d-inline">
                                                    <?= $csrf_input ?>
                                                    <button type="submit" class="btn btn-light btn-sm border text-danger btn-confirm-delete"
                                                            data-message="Supprimer cet utilisateur ?" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Si la modale des identifiants récents est présente, l'ouvrir automatiquement
    const credModalEl = document.getElementById('credentialsModal');
    if (credModalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(credModalEl);
        modal.show();
    }

    // Copie du mot de passe
    const btnCopyPwd = document.getElementById('btnCopyModalPwd');
    const pwdEl = document.getElementById('modalPlainPassword');
    if (btnCopyPwd && pwdEl) {
        btnCopyPwd.addEventListener('click', function() {
            navigator.clipboard.writeText(pwdEl.innerText.trim()).then(() => {
                const orig = btnCopyPwd.innerHTML;
                btnCopyPwd.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                setTimeout(() => { btnCopyPwd.innerHTML = orig; }, 1800);
            });
        });
    }

    // Copie du message complet
    const btnCopyFull = document.getElementById('btnCopyFullMessage');
    const txtFull = document.getElementById('fullMessageTextarea');
    if (btnCopyFull && txtFull) {
        btnCopyFull.addEventListener('click', function() {
            navigator.clipboard.writeText(txtFull.value).then(() => {
                const orig = btnCopyFull.innerHTML;
                btnCopyFull.innerHTML = '<i class="bi bi-check-lg text-success me-1"></i>Copié !';
                setTimeout(() => { btnCopyFull.innerHTML = orig; }, 1800);
            });
        });
    }
});
</script>
