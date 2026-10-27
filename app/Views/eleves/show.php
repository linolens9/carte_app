<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $eleve */

$hasPhoto = !empty($eleve['photo']) && (file_exists(public_path($eleve['photo'])) || str_starts_with($eleve['photo'], 'uploads/'));
$photoUrl = $hasPhoto ? asset_url($eleve['photo']) : null;

$rawLogo = $eleve['etablissement_logo'] ?? ($eleve['etab_logo'] ?? null);
$hasLogo = !empty($rawLogo) && (file_exists(public_path($rawLogo)) || str_starts_with($rawLogo, 'uploads/'));
$logoUrl = $hasLogo ? asset_url($rawLogo) : null;

// Calcul de l'âge
$age = null;
if (!empty($eleve['date_naissance'])) {
    try {
        $birthDate = new DateTime($eleve['date_naissance']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    } catch (Exception $e) {}
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></h2>
            <span class="badge <?= $eleve['statut'] === 'actif' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' ?> rounded-pill px-3 py-1">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?= ucfirst($eleve['statut']) ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            Matricule : <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($eleve['matricule']) ?></span>
            &bull; <?= htmlspecialchars($eleve['etablissement_nom'] ?? 'Établissement') ?>
            &bull; Classe : <strong><?= htmlspecialchars($eleve['classe_libelle'] ?? 'Non affecté') ?></strong>
        </p>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>/modifier" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
        <a href="<?= $base_path ?>/impressions?eleve_id=<?= (int)$eleve['id'] ?>" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i>Imprimer la Carte
        </a>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteEleveModal">
            <i class="bi bi-trash me-1"></i>Supprimer
        </button>
        <a href="<?= $base_path ?>/eleves" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne GAUCHE : Fiche Administrative Détaillée -->
    <div class="col-lg-7">
        <!-- 1. État Civil -->
        <div class="card-custom mb-4">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>État Civil & Identité</h6>
                <span class="badge bg-light text-secondary border font-monospace">ID #<?= (int)$eleve['id'] ?></span>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Nom de famille</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($eleve['nom']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Prénom(s)</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($eleve['prenom']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Sexe</div>
                        <div class="fw-semibold text-dark">
                            <?php if ($eleve['sexe'] === 'M'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-gender-male me-1"></i>Masculin</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-gender-female me-1"></i>Féminin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Date de naissance</div>
                        <div class="fw-semibold text-dark">
                            <?= date('d/m/Y', strtotime($eleve['date_naissance'])) ?>
                            <?php if ($age !== null): ?>
                                <span class="text-muted small">(<?= $age ?> ans)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Lieu de naissance</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($eleve['lieu_naissance'] ?: 'Non précisé') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Nationalité</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($eleve['nationalite'] ?: 'Gabonaise') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Téléphone élève</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($eleve['telephone'] ?: 'Non renseigné') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Parcours & Scolarité -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>Scolarité & Inscription</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Établissement</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($eleve['etablissement_nom'] ?? 'Établissement') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($eleve['etablissement_ville'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Classe actuelle</div>
                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($eleve['classe_libelle'] ?? 'Non affecté') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Niveau scolaire</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($eleve['niveau']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Année scolaire</div>
                        <div class="fw-semibold text-dark"><span class="badge bg-light text-dark border"><?= htmlspecialchars($eleve['annee_scolaire']) ?></span></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Date d'inscription</div>
                        <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($eleve['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tuteur Légal & Contact d'Urgence -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-telephone-plus me-2 text-primary"></i>Tuteur Légal & Urgence</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Nom du tuteur / parent</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($eleve['tuteur_nom'] ?: 'Non renseigné') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Téléphone d'urgence</div>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($eleve['tuteur_telephone'])): ?>
                                <a href="tel:<?= htmlspecialchars($eleve['tuteur_telephone']) ?>" class="text-decoration-none text-primary">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($eleve['tuteur_telephone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Non renseigné</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Adresse de résidence</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($eleve['adresse'] ?: 'Non renseignée') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne DROITE : Maquette Visuelle Réaliste de la Carte d'Identité Scolaire -->
    <div class="col-lg-5">
        <div class="card-custom sticky-top" style="top: 20px;">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-badge-ad me-2 text-primary"></i>Aperçu de la Carte Scolaire (Recto)</h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Format CR-80</span>
            </div>
            
            <div class="p-4 bg-light d-flex flex-column align-items-center">
                <!-- Visuel interactif de la Carte Scolaire (Dimensions standardisées 85.6 x 53.98 mm ratio ~ 1.586) -->
                <div class="id-card-preview shadow-lg position-relative overflow-hidden" 
                     style="width: 340px; height: 215px; border-radius: 14px; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%); color: #ffffff; padding: 12px; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.4) !important;">
                    
                    <!-- Liseré supérieur décoratif Gabon (Vert / Jaune / Bleu discret) -->
                    <div class="position-absolute top-0 start-0 w-100 d-flex" style="height: 4px;">
                        <div class="w-100" style="background: #009e49;"></div>
                        <div class="w-100" style="background: #fcd116;"></div>
                        <div class="w-100" style="background: #0072ce;"></div>
                    </div>

                    <!-- En-tête de la carte -->
                    <div class="d-flex align-items-center justify-content-between mb-2 mt-1 border-bottom pb-1" style="border-color: rgba(255,255,255,0.2) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center p-1" style="width: 32px; height: 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); flex-shrink: 0;">
                                <?php if ($logoUrl): ?>
                                    <img src="<?= $logoUrl ?>" alt="Logo" class="w-100 h-100 object-fit-contain rounded-circle">
                                <?php else: ?>
                                    <i class="bi bi-mortarboard-fill text-primary" style="font-size: 15px;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; line-height: 1.1;">
                                    <?= htmlspecialchars(mb_substr($eleve['etablissement_nom'] ?: ($eleve['etab_nom'] ?? 'ÉTABLISSEMENT SCOLAIRE'), 0, 32)) ?>
                                </div>
                                <div style="font-size: 8px; opacity: 0.8; letter-spacing: 0.3px;">CARTE D'IDENTITÉ SCOLAIRE</div>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 8px; padding: 2px 5px;">
                            <?= htmlspecialchars($eleve['annee_scolaire']) ?>
                        </span>
                    </div>

                    <!-- Corps de la carte : Photo + Infos -->
                    <div class="d-flex gap-2">
                        <!-- Photo 3:4 officielle -->
                        <div class="position-relative flex-shrink-0 bg-white p-1 rounded" style="width: 82px; height: 110px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                            <?php if ($photoUrl): ?>
                                <img src="<?= $photoUrl ?>" alt="Photo élève" class="w-100 h-100 object-fit-cover rounded">
                            <?php else: ?>
                                <div class="w-100 h-100 bg-secondary-subtle text-muted d-flex flex-column align-items-center justify-content-center rounded">
                                    <i class="bi bi-person-fill fs-2"></i>
                                    <span style="font-size: 7px;">SANS PHOTO</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Données textuelles -->
                        <div class="flex-grow-1" style="font-size: 9.5px; line-height: 1.25;">
                            <div class="mb-1">
                                <div style="font-size: 7.5px; opacity: 0.75; text-transform: uppercase;">Nom & Prénom(s)</div>
                                <div class="fw-bold text-truncate" style="font-size: 11px; max-width: 200px;">
                                    <?= htmlspecialchars($eleve['nom']) ?>
                                </div>
                                <div class="text-truncate" style="font-size: 10px; max-width: 200px;">
                                    <?= htmlspecialchars($eleve['prenom']) ?>
                                </div>
                            </div>

                            <div class="row g-0 mb-1">
                                <div class="col-6">
                                    <div style="font-size: 7.5px; opacity: 0.75;">CLASSE</div>
                                    <div class="fw-bold text-warning"><?= htmlspecialchars($eleve['classe_libelle'] ?? '-') ?></div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size: 7.5px; opacity: 0.75;">SEXE</div>
                                    <div class="fw-semibold"><?= $eleve['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></div>
                                </div>
                            </div>

                            <div class="row g-0 mb-1">
                                <div class="col-12">
                                    <div style="font-size: 7.5px; opacity: 0.75;">NÉ(E) LE</div>
                                    <div class="fw-semibold"><?= date('d/m/Y', strtotime($eleve['date_naissance'])) ?> à <?= htmlspecialchars($eleve['lieu_naissance'] ?: '-') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pied de carte : Matricule & Code-barres simulé -->
                    <div class="position-absolute bottom-0 start-0 w-100 px-3 py-1 d-flex align-items-center justify-content-between" 
                         style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); font-size: 9px;">
                        <div class="font-monospace fw-bold tracking-wider" style="letter-spacing: 1px;">
                            <?= htmlspecialchars($eleve['matricule']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <!-- Code-barres SVG simulé -->
                            <svg width="70" height="16" viewBox="0 0 100 24" style="filter: invert(1);">
                                <rect x="0" y="0" width="3" height="24" fill="#000"/>
                                <rect x="5" y="0" width="2" height="24" fill="#000"/>
                                <rect x="9" y="0" width="4" height="24" fill="#000"/>
                                <rect x="15" y="0" width="2" height="24" fill="#000"/>
                                <rect x="20" y="0" width="5" height="24" fill="#000"/>
                                <rect x="28" y="0" width="2" height="24" fill="#000"/>
                                <rect x="33" y="0" width="3" height="24" fill="#000"/>
                                <rect x="38" y="0" width="4" height="24" fill="#000"/>
                                <rect x="44" y="0" width="2" height="24" fill="#000"/>
                                <rect x="50" y="0" width="6" height="24" fill="#000"/>
                                <rect x="58" y="0" width="2" height="24" fill="#000"/>
                                <rect x="63" y="0" width="4" height="24" fill="#000"/>
                                <rect x="70" y="0" width="2" height="24" fill="#000"/>
                                <rect x="75" y="0" width="5" height="24" fill="#000"/>
                                <rect x="83" y="0" width="3" height="24" fill="#000"/>
                                <rect x="90" y="0" width="4" height="24" fill="#000"/>
                                <rect x="97" y="0" width="3" height="24" fill="#000"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="mt-4 w-100">
                    <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                        <span>Statut de production :</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-clock me-1"></i>En attente d'impression
                        </span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?= $base_path ?>/impressions?eleve_id=<?= (int)$eleve['id'] ?>" class="btn btn-primary rounded-pill btn-sm">
                            <i class="bi bi-printer me-1"></i>Lancer l'impression PDF / Recto-Verso
                        </a>
                        <a href="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>/modifier" class="btn btn-outline-secondary rounded-pill btn-sm">
                            <i class="bi bi-camera me-1"></i>Mettre à jour la photo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Suppression Élève -->
<div class="modal fade" id="deleteEleveModal" tabindex="-1" aria-labelledby="deleteEleveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteEleveModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer cet élève ?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary mb-2">
                    Êtes-vous sûr de vouloir supprimer définitivement l'élève <strong><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></strong> (Matricule : <span class="font-monospace"><?= htmlspecialchars($eleve['matricule']) ?></span>) ?
                </p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>Cette action supprimera également ses données de cartes associées.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <form action="<?= $base_path ?>/eleves/<?= (int)$eleve['id'] ?>/supprimer" method="POST">
                    <?= $csrf_input ?>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="bi bi-trash me-1"></i>Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
