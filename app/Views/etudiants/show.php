<?php
declare(strict_types=1);
/** @var string $base_path */
/** @var string $csrf_input */
/** @var array $etudiant */

$hasPhoto = !empty($etudiant['photo']) && (file_exists(public_path($etudiant['photo'])) || str_starts_with($etudiant['photo'], 'uploads/'));
$photoUrl = $hasPhoto ? asset_url($etudiant['photo']) : null;

$rawLogo = $etudiant['etablissement_logo'] ?? null;
$hasLogo = !empty($rawLogo) && (file_exists(public_path($rawLogo)) || str_starts_with($rawLogo, 'uploads/'));
$logoUrl = $hasLogo ? asset_url($rawLogo) : null;

// Âge
$age = null;
if (!empty($etudiant['date_naissance'])) {
    try {
        $birthDate = new DateTime($etudiant['date_naissance']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    } catch (Exception $e) {}
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?></h2>
            <span class="badge <?= $etudiant['statut'] === 'actif' ? 'bg-success-subtle text-success border border-success-subtle' : ($etudiant['statut'] === 'diplome' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary') ?> rounded-pill px-3 py-1">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i><?= ucfirst($etudiant['statut']) ?>
            </span>
        </div>
        <p class="text-muted small mb-0">
            Matricule : <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($etudiant['matricule']) ?></span>
            &bull; <?= htmlspecialchars($etudiant['etablissement_nom'] ?? 'Université') ?>
            &bull; Filière : <strong><?= htmlspecialchars($etudiant['filiere_libelle'] ?? 'Générale') ?></strong>
        </p>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= $base_path ?>/etudiants/<?= (int)$etudiant['id'] ?>/modifier" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
        <a href="<?= $base_path ?>/impressions?etudiant_id=<?= (int)$etudiant['id'] ?>" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i>Imprimer la Carte
        </a>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteEtudiantModal">
            <i class="bi bi-trash me-1"></i>Supprimer
        </button>
        <a href="<?= $base_path ?>/etudiants" class="btn btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne GAUCHE : Fiche Administrative et Universitaire -->
    <div class="col-lg-7">
        <!-- 1. État Civil & Identité -->
        <div class="card-custom mb-4">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>État Civil & Identité</h6>
                <span class="badge bg-light text-secondary border font-monospace">ID #<?= (int)$etudiant['id'] ?></span>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Nom de famille</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($etudiant['nom']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Prénom(s)</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($etudiant['prenom']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Sexe</div>
                        <div class="fw-semibold text-dark">
                            <?php if ($etudiant['sexe'] === 'M'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-gender-male me-1"></i>Masculin</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-gender-female me-1"></i>Féminin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Date de naissance</div>
                        <div class="fw-semibold text-dark">
                            <?= date('d/m/Y', strtotime($etudiant['date_naissance'])) ?>
                            <?php if ($age !== null): ?>
                                <span class="text-muted small">(<?= $age ?> ans)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Nationalité</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($etudiant['nationalite']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Parcours Universitaire & Affiliation -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>Cursus & Affiliation Académique</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Établissement / Université</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($etudiant['etablissement_nom']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($etudiant['etablissement_ville'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Département / Faculté</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($etudiant['departement_nom'] ?? 'Faculté Principale') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Filière / Spécialité</div>
                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($etudiant['filiere_libelle']) ?></div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Niveau LMD</div>
                        <div class="fw-semibold text-dark"><span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($etudiant['niveau']) ?></span></div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small">Année Universitaire</div>
                        <div class="fw-semibold text-dark"><span class="badge bg-light text-dark border"><?= htmlspecialchars($etudiant['annee_scolaire']) ?></span></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Groupe / TD</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($etudiant['classe_libelle'] ?? 'Promotion générale') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Date d'inscription</div>
                        <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($etudiant['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Coordonnées & Contact -->
        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h6 class="fw-bold mb-0"><i class="bi bi-envelope-at me-2 text-primary"></i>Coordonnées & Résidence</h6>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Adresse E-mail</div>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($etudiant['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($etudiant['email']) ?>" class="text-decoration-none text-primary">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($etudiant['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Non renseignée</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Numéro de Téléphone</div>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($etudiant['telephone'])): ?>
                                <a href="tel:<?= htmlspecialchars($etudiant['telephone']) ?>" class="text-decoration-none text-primary">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($etudiant['telephone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Non renseigné</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Adresse de résidence</div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($etudiant['adresse'] ?: 'Non renseignée') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne DROITE : Maquette Visuelle Réaliste de la Carte d'Étudiant Universitaire -->
    <div class="col-lg-5">
        <div class="card-custom sticky-top" style="top: 20px;">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-badge-ad me-2 text-primary"></i>Carte d'Étudiant (Format CR-80)</h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Recto</span>
            </div>
            
            <div class="p-4 bg-light d-flex flex-column align-items-center">
                <!-- Visuel Carte Universitaire Standardisée (Bleu foncé académique + Or) -->
                <div class="id-card-preview shadow-lg position-relative overflow-hidden" 
                     style="width: 340px; height: 215px; border-radius: 14px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); color: #ffffff; padding: 12px; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4) !important;">
                    
                    <!-- Liseré supérieur Gabon & Or -->
                    <div class="position-absolute top-0 start-0 w-100 d-flex" style="height: 4px;">
                        <div class="w-100" style="background: #009e49;"></div>
                        <div class="w-100" style="background: #fcd116;"></div>
                        <div class="w-100" style="background: #0072ce;"></div>
                    </div>

                    <!-- En-tête de la carte universitaire -->
                    <div class="d-flex align-items-center justify-content-between mb-2 mt-1 border-bottom pb-1" style="border-color: rgba(255,255,255,0.15) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center p-1" style="width: 32px; height: 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.25); flex-shrink: 0;">
                                <?php if ($logoUrl): ?>
                                    <img src="<?= $logoUrl ?>" alt="Logo" class="w-100 h-100 object-fit-contain rounded-circle">
                                <?php else: ?>
                                    <i class="bi bi-mortarboard-fill text-warning" style="font-size: 15px;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-uppercase text-warning" style="font-size: 10px; letter-spacing: 0.5px; line-height: 1.1;">
                                    <?= htmlspecialchars(mb_substr($etudiant['etablissement_nom'] ?? 'UNIVERSITÉ OMAR BONGO', 0, 30)) ?>
                                </div>
                                <div style="font-size: 8px; opacity: 0.85; letter-spacing: 0.4px;">CARTE D'ÉTUDIANT &bull; CYCLE LMD</div>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 8px; padding: 2px 6px;">
                            <?= htmlspecialchars($etudiant['annee_scolaire']) ?>
                        </span>
                    </div>

                    <!-- Corps de la carte : Photo + Données -->
                    <div class="d-flex gap-2">
                        <!-- Photo 3:4 -->
                        <div class="position-relative flex-shrink-0 bg-white p-1 rounded" style="width: 82px; height: 110px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                            <?php if ($photoUrl): ?>
                                <img src="<?= $photoUrl ?>" alt="Photo étudiant" class="w-100 h-100 object-fit-cover rounded">
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
                                    <?= htmlspecialchars($etudiant['nom']) ?>
                                </div>
                                <div class="text-truncate text-light" style="font-size: 10px; max-width: 200px;">
                                    <?= htmlspecialchars($etudiant['prenom']) ?>
                                </div>
                            </div>

                            <div class="mb-1">
                                <div style="font-size: 7.5px; opacity: 0.75;">FILIÈRE / DÉPARTEMENT</div>
                                <div class="fw-semibold text-warning text-truncate" style="max-width: 200px;">
                                    <?= htmlspecialchars($etudiant['filiere_libelle']) ?>
                                </div>
                            </div>

                            <div class="row g-0 mb-1">
                                <div class="col-6">
                                    <div style="font-size: 7.5px; opacity: 0.75;">CYCLE / NIVEAU</div>
                                    <div class="fw-bold text-light"><?= htmlspecialchars($etudiant['niveau']) ?></div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size: 7.5px; opacity: 0.75;">NATIONALITÉ</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($etudiant['nationalite']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pied de carte : Matricule & QR Code interactif simulé -->
                    <div class="position-absolute bottom-0 start-0 w-100 px-3 py-1 d-flex align-items-center justify-content-between" 
                         style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); font-size: 9px;">
                        <div class="font-monospace fw-bold tracking-wider text-warning" style="letter-spacing: 1px;">
                            <?= htmlspecialchars($etudiant['matricule']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Mini QR Code SVG simulé -->
                            <div class="bg-white p-1 rounded" style="width: 20px; height: 20px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="#000">
                                    <path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm14 2h2v2h-2v-2zm-4-2h2v2h-2v-2zm2-2h2v2h-2v-2zm2 4h2v2h-2v-2z"/>
                                </svg>
                            </div>
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
                        <a href="<?= $base_path ?>/impressions?etudiant_id=<?= (int)$etudiant['id'] ?>" class="btn btn-primary rounded-pill btn-sm">
                            <i class="bi bi-printer me-1"></i>Lancer l'impression PDF / Recto-Verso
                        </a>
                        <a href="<?= $base_path ?>/etudiants/<?= (int)$etudiant['id'] ?>/modifier" class="btn btn-outline-secondary rounded-pill btn-sm">
                            <i class="bi bi-camera me-1"></i>Mettre à jour la photo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Suppression Étudiant -->
<div class="modal fade" id="deleteEtudiantModal" tabindex="-1" aria-labelledby="deleteEtudiantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteEtudiantModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer cet étudiant ?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary mb-2">
                    Êtes-vous sûr de vouloir supprimer définitivement l'étudiant <strong><?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?></strong> (Matricule : <span class="font-monospace"><?= htmlspecialchars($etudiant['matricule']) ?></span>) ?
                </p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>Cette action supprimera également ses données de cartes associées.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <form action="<?= $base_path ?>/etudiants/<?= (int)$etudiant['id'] ?>/supprimer" method="POST">
                    <?= $csrf_input ?>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="bi bi-trash me-1"></i>Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
