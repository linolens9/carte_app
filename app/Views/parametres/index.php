<?php
/**
 * Vue : Paramètres de configuration du système
 */
$base_path = rtrim((string)config('app.url', 'http://localhost/carte_app'), '/');
$activeTab = $_GET['tab'] ?? 'general';

function getP(array $params, string $key, $default = '') {
    if (!isset($params[$key])) {
        return $default;
    }
    return is_array($params[$key]) ? ($params[$key]['valeur'] ?? $default) : $params[$key];
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-gear-wide-connected me-2 text-primary"></i> Paramètres Système
        </h4>
        <p class="text-secondary mb-0 small">
            Configuration globale de la plateforme, sécurité, dimensions des cartes et politique de sauvegarde.
        </p>
    </div>
</div>

<div class="card-custom p-4">
    <!-- Onglets de navigation -->
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="paramTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=general">
                <i class="bi bi-sliders me-1"></i> Général
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'cartes' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=cartes">
                <i class="bi bi-card-heading me-1"></i> Cartes & Impression
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'photos' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=photos">
                <i class="bi bi-camera me-1"></i> Photos & Médias
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'securite' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=securite">
                <i class="bi bi-shield-lock me-1"></i> Sécurité & Accès
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'sauvegardes' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=sauvegardes">
                <i class="bi bi-database-check me-1"></i> Sauvegardes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'notifications' ? 'active' : '' ?>" href="<?= $base_path ?>/parametres?tab=notifications">
                <i class="bi bi-bell me-1"></i> Notifications & Canaux
            </a>
        </li>
    </ul>

    <!-- Formulaire -->
    <form method="POST" action="<?= $base_path ?>/parametres">
        <?= \App\Core\Csrf::inputField() ?>
        <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">

        <?php if ($activeTab === 'general'): ?>
            <!-- ONGLET GÉNÉRAL -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nom de la Plateforme</label>
                    <input type="text" name="params[app.nom]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'app.nom', 'Carte App')) ?>" required>
                    <div class="form-text">Apparaît dans le bandeau et les titres de page.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Langue par défaut</label>
                    <select name="params[app.langue]" class="form-select">
                        <option value="fr" <?= getP($params, 'app.langue') === 'fr' ? 'selected' : '' ?>>Français (fr-FR)</option>
                        <option value="en" <?= getP($params, 'app.langue') === 'en' ? 'selected' : '' ?>>English (en-US)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Fuseau Horaire</label>
                    <input type="text" name="params[app.fuseau_horaire]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'app.fuseau_horaire', 'Africa/Libreville')) ?>" required>
                    <div class="form-text">Ex: Africa/Libreville (UTC+1).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Description / Slogan</label>
                    <input type="text" name="params[app.description]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'app.description', 'Plateforme de gestion et édition de cartes')) ?>">
                </div>
            </div>

        <?php elseif ($activeTab === 'cartes'): ?>
            <!-- ONGLET CARTES -->
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Format standard des cartes</label>
                    <select name="params[carte.format]" class="form-select">
                        <option value="CR80" <?= getP($params, 'carte.format') === 'CR80' ? 'selected' : '' ?>>CR-80 (85.60 × 53.98 mm - Norme ISO/IEC 7810 ID-1)</option>
                        <option value="A6" <?= getP($params, 'carte.format') === 'A6' ? 'selected' : '' ?>>Format A6</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Durée de validité par défaut (mois)</label>
                    <input type="number" name="params[carte.duree_validite_mois]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'carte.duree_validite_mois', '12')) ?>" min="1" max="60" required>
                    <div class="form-text">Ex: 12 mois pour les cartes scolaires annuelles.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Résolution d'impression (DPI)</label>
                    <input type="number" name="params[carte.resolution_dpi]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'carte.resolution_dpi', '300')) ?>" min="150" max="600" required>
                    <div class="form-text">300 DPI recommandé pour les imprimantes à badges plastiques.</div>
                </div>
            </div>

        <?php elseif ($activeTab === 'photos'): ?>
            <!-- ONGLET PHOTOS -->
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Largeur cible (pixels)</label>
                    <input type="number" name="params[photo.largeur]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'photo.largeur', '600')) ?>" min="200" max="2400" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Hauteur cible (pixels)</label>
                    <input type="number" name="params[photo.hauteur]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'photo.hauteur', '800')) ?>" min="200" max="3200" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Qualité JPEG (%)</label>
                    <input type="number" name="params[photo.qualite_jpeg]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'photo.qualite_jpeg', '85')) ?>" min="50" max="100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Poids maximal cible (Ko)</label>
                    <input type="number" name="params[photo.poids_max_ko]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'photo.poids_max_ko', '150')) ?>" min="20" max="2000" required>
                </div>
            </div>

        <?php elseif ($activeTab === 'securite'): ?>
            <!-- ONGLET SÉCURITÉ -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Durée d'inactivité de session (minutes)</label>
                    <input type="number" name="params[securite.duree_session_minutes]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'securite.duree_session_minutes', '30')) ?>" min="5" max="240" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Tentatives de connexion max avant blocage</label>
                    <input type="number" name="params[securite.tentatives_max]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'securite.tentatives_max', '5')) ?>" min="3" max="20" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Durée de verrouillage temporaire (minutes)</label>
                    <input type="number" name="params[securite.verrouillage_minutes]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'securite.verrouillage_minutes', '15')) ?>" min="5" max="1440" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Longueur minimale du mot de passe</label>
                    <input type="number" name="params[securite.mdp_longueur_min]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'securite.mdp_longueur_min', '10')) ?>" min="8" max="32" required>
                </div>
            </div>

        <?php elseif ($activeTab === 'sauvegardes'): ?>
            <!-- ONGLET SAUVEGARDES -->
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Fréquence recommandée</label>
                    <select name="params[sauvegarde.frequence]" class="form-select">
                        <option value="quotidienne" <?= getP($params, 'sauvegarde.frequence') === 'quotidienne' ? 'selected' : '' ?>>Quotidienne (Chaque nuit)</option>
                        <option value="hebdomadaire" <?= getP($params, 'sauvegarde.frequence') === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Rétention des sauvegardes (jours)</label>
                    <input type="number" name="params[sauvegarde.retention_jours]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'sauvegarde.retention_jours', '30')) ?>" min="1" max="365" required>
                    <div class="form-text">Les archives antérieures à ce délai sont nettoyées automatiquement.</div>
                </div>
            </div>

        <?php elseif ($activeTab === 'notifications'): ?>
            <!-- ONGLET NOTIFICATIONS & CANAUX -->
            <div class="row g-4">
                <!-- 1. EMAIL -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-envelope-at text-primary me-2"></i>Canal E-mail (Identifiants & Liens)
                        </h6>
                        <p class="text-muted small mb-3">Envoi automatique des courriels d'accès aux nouveaux utilisateurs.</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Pilote / Driver Mail</label>
                                <select name="params[mail.driver]" class="form-select">
                                    <option value="smtp" <?= getP($params, 'mail.driver', config('mail.driver', 'smtp')) === 'smtp' ? 'selected' : '' ?>>SMTP (Recommandé)</option>
                                    <option value="mail" <?= getP($params, 'mail.driver', config('mail.driver', 'smtp')) === 'mail' ? 'selected' : '' ?>>mail() PHP Natif</option>
                                    <option value="log" <?= getP($params, 'mail.driver', config('mail.driver', 'smtp')) === 'log' ? 'selected' : '' ?>>Simulation (Journaliser sans envoyer)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Hôte SMTP</label>
                                <input type="text" name="params[mail.host]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'mail.host', config('mail.host', '127.0.0.1'))) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Port SMTP</label>
                                <input type="number" name="params[mail.port]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'mail.port', config('mail.port', 1025))) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Nom de l'expéditeur</label>
                                <input type="text" name="params[mail.from_name]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'mail.from_name', config('mail.from_name', 'MBOLO Carte App'))) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Adresse email de l'expéditeur</label>
                                <input type="email" name="params[mail.from_address]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'mail.from_address', config('mail.from_address', 'noreply@carteapp.ga'))) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. WHATSAPP -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-whatsapp text-success me-2"></i>Canal WhatsApp (Click-to-Chat & Cloud API)
                        </h6>
                        <p class="text-muted small mb-3">Envoi automatisé via WhatsApp Cloud API Meta ou génération immédiate de lien Click-to-Chat (wa.me).</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Mode WhatsApp</label>
                                <select name="params[whatsapp.driver]" class="form-select">
                                    <option value="direct" <?= getP($params, 'whatsapp.driver', config('whatsapp.driver', 'direct')) === 'direct' ? 'selected' : '' ?>>Click-to-Chat direct (Sans abonnement API)</option>
                                    <option value="cloud_api" <?= getP($params, 'whatsapp.driver', config('whatsapp.driver', 'direct')) === 'cloud_api' ? 'selected' : '' ?>>Meta WhatsApp Cloud API (Automatisé)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Identifiant de numéro WhatsApp (Phone ID)</label>
                                <input type="text" name="params[whatsapp.phone_number_id]" class="form-control" placeholder="Ex: 1045938291..." value="<?= htmlspecialchars((string)getP($params, 'whatsapp.phone_number_id', config('whatsapp.phone_number_id', ''))) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Jeton d'accès permanent (Bearer Token)</label>
                                <input type="password" name="params[whatsapp.token]" class="form-control" placeholder="EAAB..." value="<?= htmlspecialchars((string)getP($params, 'whatsapp.token', config('whatsapp.token', ''))) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. SMS CLASSIQUE -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-chat-left-dots text-info me-2"></i>Canal SMS classique (Passerelle & URI native)
                        </h6>
                        <p class="text-muted small mb-3">Expédition par passerelle SMS (Twilio, Infobip, etc.) ou lien natif mobile.</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Mode SMS</label>
                                <select name="params[sms.driver]" class="form-select">
                                    <option value="direct" <?= getP($params, 'sms.driver', config('sms.driver', 'direct')) === 'direct' ? 'selected' : '' ?>>Lien SMS natif (Smartphone & tablettes)</option>
                                    <option value="gateway" <?= getP($params, 'sms.driver', config('sms.driver', 'direct')) === 'gateway' ? 'selected' : '' ?>>Passerelle HTTP / API SMS</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">URL de l'API passerelle SMS</label>
                                <input type="url" name="params[sms.gateway_url]" class="form-control" placeholder="https://api.sms-provider.com/send" value="<?= htmlspecialchars((string)getP($params, 'sms.gateway_url', config('sms.gateway_url', ''))) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Nom d'expéditeur SMS (Sender ID)</label>
                                <input type="text" name="params[sms.sender_id]" class="form-control" value="<?= htmlspecialchars((string)getP($params, 'sms.sender_id', config('sms.sender_id', 'MBOLO'))) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm">
                <i class="bi bi-check2"></i>
                <span>Enregistrer les paramètres</span>
            </button>
        </div>
    </form>
</div>
