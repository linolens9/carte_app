<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $personnel */

$hasPhoto = !empty($personnel['photo']) && (file_exists(public_path($personnel['photo'])) || str_starts_with($personnel['photo'], 'uploads/'));
$photoUrl = $hasPhoto ? asset_url($personnel['photo']) : null;

$rawLogo = $personnel['etablissement_logo'] ?? null;
$hasLogo = !empty($rawLogo) && (file_exists(public_path($rawLogo)) || str_starts_with($rawLogo, 'uploads/'));
$logoUrl = $hasLogo ? asset_url($rawLogo) : null;

// Ancienneté
$anciennete = null;
if (!empty($personnel['date_embauche'])) {
    try {
        $hireDate = new DateTime($personnel['date_embauche']);
        $today = new DateTime();
        $diff = $today->diff($hireDate);
        $anciennete = $diff->y > 0 ? $diff->y . ' an(s) ' . $diff->m . ' mois' : $diff->m . ' mois';
    } catch (Exception $e) {}
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']) ?></h2>
            <span class="badge <?= $personnel['statut'] === 'en_poste' ? 'bg-success-subtle text-success border border-success-subtle' : ($personnel['statut'] === 'suspendu' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-secondary') ?> rounded-pill px-3 py-1">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?= $personnel['statut'] === 'en_poste' ? 'En poste' : ucfirst($personnel['statut']) ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            N° Professionnel : <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($personnel['numero_professionnel']) ?></span>
            &bull; <?= htmlspecialchars($personnel['etablissement_nom']) ?>
            &bull; Fonction : <strong><?= htmlspecialchars($personnel['fonction']) ?></strong>
        </p>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>/modifier" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
        <a href="<?= $base_path ?>/impressions?personnel_id=<?= (int)$personnel['id'] ?>" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i>Imprimer le Badge
        </a>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deletePersonnelModal">
            <i class="bi bi-trash me-1"></i>Supprimer
        </button>
        <a href="<?= $base_path ?>/personnels" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne GAUCHE : Fiche Administrative du Collaborateur -->
    <div class="col-lg-7">
        <!-- 1. Identité de l'agent -->
        <div class="card-custom mb-4">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>Identité du Collaborateur</h6>
                <span class="badge bg-light text-secondary border font-monospace">ID #<?= (int)$personnel['id'] ?></span>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Nom de famille</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($personnel['nom']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Prénom(s)</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($personnel['prenom']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Numéro Professionnel / Matricule</div>
                        <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($personnel['numero_professionnel']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Date d'enregistrement</div>
                        <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($personnel['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Poste, Affectation & Carrière -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-briefcase me-2 text-primary"></i>Affectation & Carrière</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Organisation / Entreprise</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($personnel['etablissement_nom']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($personnel['etablissement_ville'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Département / Direction</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($personnel['departement_nom'] ?? 'Direction Générale') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Fonction officielle</div>
                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($personnel['fonction']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Service / Pôle</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($personnel['service'] ?: 'Non spécifié') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Date d'embauche</div>
                        <div class="fw-semibold text-dark">
                            <?= !empty($personnel['date_embauche']) ? date('d/m/Y', strtotime($personnel['date_embauche'])) : 'Non renseignée' ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Ancienneté estimée</div>
                        <div class="fw-semibold text-dark">
                            <?= $anciennete ? '<span class="badge bg-light text-dark border">' . $anciennete . '</span>' : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Coordonnées & Contact -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-envelope-at me-2 text-primary"></i>Coordonnées Professionnelles</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Adresse E-mail</div>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($personnel['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($personnel['email']) ?>" class="text-decoration-none text-primary">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($personnel['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Non renseignée</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Numéro de Téléphone</div>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($personnel['telephone'])): ?>
                                <a href="tel:<?= htmlspecialchars($personnel['telephone']) ?>" class="text-decoration-none text-primary">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($personnel['telephone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Non renseigné</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Adresse de résidence</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($personnel['adresse'] ?: 'Non renseignée') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne DROITE : Maquette Visuelle Réaliste du Badge Professionnel -->
    <div class="col-lg-5">
        <div class="card-custom sticky-top" style="top: 20px;">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-badge-ad me-2 text-primary"></i>Badge Professionnel (CR-80)</h6>
                <span class="badge bg-dark text-white font-monospace">Recto Sécurisé</span>
            </div>
            
            <div class="p-4 bg-light d-flex flex-column align-items-center">
                <!-- Visuel Badge Professionnel (Élégant Dark / Saphir avec puce et liseré officiel) -->
                <div class="id-card-preview shadow-lg position-relative overflow-hidden" 
                     style="width: 340px; height: 215px; border-radius: 14px; background: linear-gradient(135deg, #1e1b4b 0%, #2e1065 50%, #4c1d95 100%); color: #ffffff; padding: 12px; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 10px 25px -5px rgba(46, 16, 101, 0.45) !important;">
                    
                    <!-- Liseré Gabon supérieur -->
                    <div class="position-absolute top-0 start-0 w-100 d-flex" style="height: 4px;">
                        <div class="w-100" style="background: #009e49;"></div>
                        <div class="w-100" style="background: #fcd116;"></div>
                        <div class="w-100" style="background: #0072ce;"></div>
                    </div>

                    <!-- En-tête de l'organisation -->
                    <div class="d-flex align-items-center justify-content-between mb-2 mt-1 border-bottom pb-1" style="border-color: rgba(255,255,255,0.2) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center p-1" style="width: 32px; height: 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.25); flex-shrink: 0;">
                                <?php if ($logoUrl): ?>
                                    <img src="<?= $logoUrl ?>" alt="Logo" class="w-100 h-100 object-fit-contain rounded-circle">
                                <?php else: ?>
                                    <i class="bi bi-shield-check" style="font-size: 15px; color: #4c1d95;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-uppercase text-truncate" style="font-size: 9.5px; letter-spacing: 0.5px; line-height: 1.1; max-width: 220px;">
                                    <?= htmlspecialchars($personnel['etablissement_nom']) ?>
                                </div>
                                <div style="font-size: 7.5px; opacity: 0.85; letter-spacing: 0.5px;">CARTE PROFESSIONNELLE D'AGENT</div>
                            </div>
                        </div>
                        <!-- Puce RFID simulée -->
                        <div style="width: 22px; height: 16px; border: 1px solid #d8b4fe; border-radius: 3px; background: linear-gradient(135deg, #e9d5ff 0%, #c084fc 100%); opacity: 0.85;" title="Puce sans contact RFID/NFC"></div>
                    </div>

                    <!-- Corps du badge : Photo + Infos -->
                    <div class="d-flex gap-2">
                        <!-- Photo 3:4 -->
                        <div class="position-relative flex-shrink-0 bg-white p-1 rounded" style="width: 82px; height: 110px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                            <?php if ($photoUrl): ?>
                                <img src="<?= $photoUrl ?>" alt="Photo personnel" class="w-100 h-100 object-fit-cover rounded">
                            <?php else: ?>
                                <div class="w-100 h-100 bg-secondary-subtle text-muted d-flex flex-column align-items-center justify-content-center rounded">
                                    <i class="bi bi-person-fill fs-2"></i>
                                    <span style="font-size: 7px;">SANS PHOTO</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Données textuelles de l'agent -->
                        <div class="flex-grow-1" style="font-size: 9.5px; line-height: 1.25;">
                            <div class="mb-1">
                                <div style="font-size: 7px; opacity: 0.75; text-transform: uppercase;">COLLABORATEUR</div>
                                <div class="fw-bold text-truncate" style="font-size: 11px; max-width: 200px;">
                                    <?= htmlspecialchars($personnel['nom']) ?>
                                </div>
                                <div class="text-truncate text-light" style="font-size: 10px; max-width: 200px;">
                                    <?= htmlspecialchars($personnel['prenom']) ?>
                                </div>
                            </div>

                            <div class="mb-1">
                                <div style="font-size: 7px; opacity: 0.75;">FONCTION</div>
                                <div class="fw-bold text-warning text-truncate" style="max-width: 200px;">
                                    <?= htmlspecialchars($personnel['fonction']) ?>
                                </div>
                            </div>

                            <div class="row g-0 mb-1">
                                <div class="col-12">
                                    <div style="font-size: 7px; opacity: 0.75;">DÉPARTEMENT / PÔLE</div>
                                    <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                        <?= htmlspecialchars($personnel['departement_nom'] ?? ($personnel['service'] ?: 'Direction Générale')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pied de badge : N° Pro & Code-barres officiel -->
                    <div class="position-absolute bottom-0 start-0 w-100 px-3 py-1 d-flex align-items-center justify-content-between" 
                         style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); font-size: 9px;">
                        <div class="font-monospace fw-bold tracking-wider text-warning" style="letter-spacing: 1px;">
                            <?= htmlspecialchars($personnel['numero_professionnel']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <svg width="65" height="15" viewBox="0 0 100 24" style="filter: invert(1);">
                                <rect x="0" y="0" width="3" height="24" fill="#000"/>
                                <rect x="6" y="0" width="3" height="24" fill="#000"/>
                                <rect x="12" y="0" width="2" height="24" fill="#000"/>
                                <rect x="17" y="0" width="5" height="24" fill="#000"/>
                                <rect x="25" y="0" width="2" height="24" fill="#000"/>
                                <rect x="30" y="0" width="4" height="24" fill="#000"/>
                                <rect x="37" y="0" width="2" height="24" fill="#000"/>
                                <rect x="42" y="0" width="5" height="24" fill="#000"/>
                                <rect x="50" y="0" width="3" height="24" fill="#000"/>
                                <rect x="56" y="0" width="2" height="24" fill="#000"/>
                                <rect x="61" y="0" width="6" height="24" fill="#000"/>
                                <rect x="70" y="0" width="2" height="24" fill="#000"/>
                                <rect x="75" y="0" width="4" height="24" fill="#000"/>
                                <rect x="82" y="0" width="3" height="24" fill="#000"/>
                                <rect x="88" y="0" width="5" height="24" fill="#000"/>
                                <rect x="96" y="0" width="3" height="24" fill="#000"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="mt-4 w-100">
                    <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                        <span>Statut du badge :</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-clock me-1"></i>En attente d'impression
                        </span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?= $base_path ?>/impressions?personnel_id=<?= (int)$personnel['id'] ?>" class="btn btn-primary rounded-pill btn-sm">
                            <i class="bi bi-printer me-1"></i>Imprimer le badge professionnel
                        </a>
                        <a href="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>/modifier" class="btn btn-outline-secondary rounded-pill btn-sm">
                            <i class="bi bi-camera me-1"></i>Mettre à jour la photo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Suppression Personnel -->
<div class="modal fade" id="deletePersonnelModal" tabindex="-1" aria-labelledby="deletePersonnelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deletePersonnelModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer ce collaborateur ?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary mb-2">
                    Êtes-vous sûr de vouloir supprimer définitivement le collaborateur <strong><?= htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']) ?></strong> (N° : <span class="font-monospace"><?= htmlspecialchars($personnel['numero_professionnel']) ?></span>) ?
                </p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>Cette action désactivera également son historique de badge.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <form action="<?= $base_path ?>/personnels/<?= (int)$personnel['id'] ?>/supprimer" method="POST">
                    <?= $csrf_input ?>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="bi bi-trash me-1"></i>Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
