<?php
/**
 * Aperçu détaillé et interactif d'un Modèle de Carte (Template)
 */

// Mock card data pour l'aperçu dynamique du modèle
$demoEtabNom = match($template['cible']) {
    'etudiant' => 'Université Omar Bongo (UOB)',
    'personnel' => 'Agence Nationale des Infrastructures Numériques',
    default => 'Lycée National Léon Mba'
};
$demoLogo = match($template['cible']) {
    'etudiant' => 'uploads/logos/2f31da1a3fbafb713ea22a42ea73accb.jpg',
    'personnel' => 'uploads/logos/205e488b77e762cf35d212f79dde90b0.jpg',
    default => 'uploads/logos/adeffe5b9128949ec54e56db644cb849.jpg'
};

$mockCard = [
    'id' => 999,
    'numero_carte' => 'C-2026-DEMO-00001',
    'jeton_verification' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
    'date_emission' => date('Y-m-d'),
    'date_expiration' => date('Y-m-d', strtotime('+1 year')),
    'statut' => 'validee',
    'template_nom' => $template['nom'],
    'template_logo' => $template['logo'] ?? null,
    'couleur_principale' => $template['couleur_principale'] ?? '#1e3a8a',
    'couleur_secondaire' => $template['couleur_secondaire'] ?? '#3b82f6',
    'config_recto' => $template['config_recto'],
    'config_verso' => $template['config_verso'],
    'etablissement_nom' => !empty($template['etablissement_nom']) ? $template['etablissement_nom'] : $demoEtabNom,
    'etablissement_code' => $template['etablissement_code'] ?? 'GA',
    'etablissement_logo' => !empty($template['etablissement_logo']) ? $template['etablissement_logo'] : (!empty($template['logo']) ? $template['logo'] : $demoLogo),
    'etablissement_ville' => 'Libreville, Gabon',
    'beneficiaire_type' => $template['cible'],
    'beneficiaire' => [
        'nom' => 'OBAME NGUEMA',
        'prenom' => 'Jean-Marc',
        'matricule' => $template['cible'] === 'eleve' ? 'LNLM-2025-00142' : ($template['cible'] === 'etudiant' ? 'UOB-2025-45892' : 'ANINF-2025-00088'),
        'classe_nom' => 'Terminale S2',
        'filiere_nom' => 'Informatique de Gestion (L3)',
        'cycle' => 'Licence',
        'fonction' => 'Ingénieur Système & Réseaux',
        'service' => 'Direction Infrastructure',
        'date_naissance' => '2005-08-14',
        'photo' => null,
        'matricule_label' => $template['cible'] === 'eleve' ? 'Matricule' : ($template['cible'] === 'etudiant' ? 'N° Étudiant' : 'N° Professionnel'),
    ]
];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= $base_path ?>/templates" class="btn btn-light border rounded-circle">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($template['nom']) ?></h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary">Format CR-80 (85.6 × 53.98 mm)</span>
                <span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($template['cible'])) ?></span>
                <span class="badge <?= $template['actif'] ? 'bg-success' : 'bg-danger' ?>"><?= $template['actif'] ? 'Actif' : 'Inactif' ?></span>
            </div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $base_path ?>/cartes/generer?template_id=<?= $template['id'] ?>" class="btn btn-success d-flex align-items-center gap-2">
            <i class="bi bi-card-heading"></i>
            <span>Générer des cartes</span>
        </a>
        <a href="<?= $base_path ?>/templates/<?= $template['id'] ?>/modifier" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <i class="bi bi-pencil"></i>
            <span>Modifier</span>
        </a>
        <a href="<?= $base_path ?>/templates/<?= $template['id'] ?>/dupliquer" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-copy"></i>
            <span>Dupliquer</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Colonne Aperçu 3D Interactif -->
    <div class="col-lg-6">
        <div class="card-custom p-4 text-center">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-eye me-1 text-primary"></i> Rendu Réaliste CR-80
                </h6>
                <span class="badge bg-light text-dark border">Cliquez ou utilisez le bouton pour retourner</span>
            </div>

            <div class="py-4 bg-light rounded-4 border d-flex flex-column align-items-center justify-content-center">
                <?php
                $card = $mockCard;
                $interactive = true;
                $mode = 'flip';
                include __DIR__ . '/../cartes/partials/card_cr80.php';
                ?>
            </div>

            <div class="mt-3 text-muted small text-start">
                <i class="bi bi-info-circle me-1"></i>
                Cet aperçu simule les données réelles d'un bénéficiaire. Le code-barres Code 128 et le QR code sont générés vectoriellement à l'échelle.
            </div>
        </div>
    </div>

    <!-- Colonne Paramètres et Spécifications -->
    <div class="col-lg-6">
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-palette me-1 text-primary"></i> Charte Graphique & Couleurs
            </h6>
            <div class="d-flex align-items-center gap-4 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: <?= htmlspecialchars($template['couleur_principale']) ?>; border: 1px solid #0002;"></div>
                    <div>
                        <div class="small text-muted">Couleur Principale</div>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($template['couleur_principale']) ?></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: <?= htmlspecialchars($template['couleur_secondaire']) ?>; border: 1px solid #0002;"></div>
                    <div>
                        <div class="small text-muted">Couleur Secondaire</div>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($template['couleur_secondaire']) ?></div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                <i class="bi bi-card-text me-1 text-primary"></i> Configuration Recto
            </h6>
            <div class="row g-2 small">
                <div class="col-6 text-muted">Titre de la carte :</div>
                <div class="col-6 fw-bold"><?= htmlspecialchars($template['config_recto']['titre_carte'] ?? 'CARTE D\'IDENTITÉ') ?></div>
                <div class="col-6 text-muted">Sous-titre / Mention :</div>
                <div class="col-6 fw-bold"><?= htmlspecialchars($template['config_recto']['mention_annee'] ?? 'N/A') ?></div>
                <div class="col-6 text-muted">Code-barres Code 128 :</div>
                <div class="col-6"><?= !empty($template['config_recto']['afficher_barcode']) ? '<span class="text-success fw-bold">✓ Actif</span>' : '<span class="text-danger">✗ Inactif</span>' ?></div>
                <div class="col-6 text-muted">Puce sécurisée EMV :</div>
                <div class="col-6"><?= !empty($template['config_recto']['afficher_puce']) ? '<span class="text-success fw-bold">✓ Active</span>' : '<span class="text-danger">✗ Inactive</span>' ?></div>
            </div>

            <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                <i class="bi bi-qr-code me-1 text-primary"></i> Configuration Verso
            </h6>
            <div class="row g-2 small">
                <div class="col-6 text-muted">QR Code de vérification :</div>
                <div class="col-6"><?= !empty($template['config_verso']['afficher_qr']) ? '<span class="text-success fw-bold">✓ Actif (Mode Hors-ligne & En ligne)</span>' : '<span class="text-danger">✗ Inactif</span>' ?></div>
                <div class="col-6 text-muted">Titre signature :</div>
                <div class="col-6 fw-bold"><?= htmlspecialchars($template['config_verso']['titre_signature'] ?? 'Le Responsable') ?></div>
                <div class="col-12 text-muted mt-2">Texte du règlement :</div>
                <div class="col-12 bg-light p-2 rounded border font-monospace" style="font-size: 0.75rem;">
                    <?= nl2br(htmlspecialchars($template['config_verso']['texte_reglement'] ?? 'Aucun texte')) ?>
                </div>
            </div>
        </div>
    </div>
</div>
