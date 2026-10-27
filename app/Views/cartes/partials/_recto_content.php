<?php
/**
 * Face RECTO de la carte CR-80
 * Aligné fidèlement d'après les spécifications visuelles officielles
 */
$cardTitle = !empty($rectoCfg['titre_carte']) ? $rectoCfg['titre_carte'] : null;
if (empty($cardTitle) || $cardTitle === 'CARTE D\'IDENTITÉ') {
    $cardTitle = ($type === 'etudiant') ? 'CARTE D\'ÉTUDIANT' : (($type === 'personnel') ? 'BADGE PROFESSIONNEL' : 'CARTE D\'IDENTITÉ SCOLAIRE');
}

$cardYear = !empty($rectoCfg['mention_annee']) ? $rectoCfg['mention_annee'] : null;
if (empty($cardYear) || $cardYear === 'Année 2025-2026') {
    $cardYear = ($type === 'etudiant') ? 'Année Académique 2025–2026' : (($type === 'personnel') ? 'Carte de Service' : 'Année Scolaire 2025–2026');
} elseif ($type === 'eleve' && str_starts_with($cardYear, 'Année ') && !str_starts_with($cardYear, 'Année Scolaire')) {
    $cardYear = str_replace('Année ', 'Année Scolaire ', $cardYear);
}
?>
<div class="cr80-inner">
    <!-- Bandeau tricolore Gabon (Vert / Jaune / Bleu) -->
    <div class="cr80-gabon-ribbon">
        <span class="ribbon-green"></span>
        <span class="ribbon-yellow"></span>
        <span class="ribbon-blue"></span>
    </div>

    <!-- En-tête de la carte -->
    <div class="cr80-header">
        <div class="cr80-header-left">
            <div class="cr80-etab-name" title="<?= htmlspecialchars($etabNom) ?>"><?= htmlspecialchars(mb_strtoupper((string)$etabNom, 'UTF-8')) ?></div>
            <div class="cr80-card-title"><?= htmlspecialchars(mb_strtoupper((string)$cardTitle, 'UTF-8')) ?></div>
            <div class="cr80-card-subtitle"><?= htmlspecialchars($cardYear) ?></div>
        </div>

        <?php if (!isset($rectoCfg['afficher_logo']) || !empty($rectoCfg['afficher_logo'])): ?>
            <div class="cr80-header-right">
                <?php if (!empty($logoSrc)): ?>
                    <img src="<?= htmlspecialchars($logoSrc) ?>" alt="Logo" class="cr80-logo" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                    <div class="cr80-logo-placeholder" style="display:none;" title="Logo Établissement">
                        <i class="bi bi-building"></i>
                    </div>
                <?php else: ?>
                    <div class="cr80-logo-placeholder" title="Logo Établissement">
                        <i class="bi bi-building"></i>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Corps de la carte : Photo + Infos -->
    <div class="cr80-body">
        <!-- Colonne Photo & Puce -->
        <?php $showPhoto = !isset($rectoCfg['afficher_photo']) || !empty($rectoCfg['afficher_photo']); ?>
        <?php $showPuce = !isset($rectoCfg['afficher_puce']) || !empty($rectoCfg['afficher_puce']); ?>
        <?php if ($showPhoto || $showPuce): ?>
        <div class="cr80-photo-col">
            <?php if ($showPhoto): ?>
            <div class="cr80-photo-frame">
                <?php if ($photoSrc): ?>
                    <img src="<?= htmlspecialchars($photoSrc) ?>" alt="Photo" class="cr80-photo-img">
                <?php else: ?>
                    <div class="cr80-photo-empty">
                        <i class="bi bi-person-fill"></i>
                        <span><?= strtoupper(substr($b['prenom'] ?? 'N', 0, 1) . substr($b['nom'] ?? 'P', 0, 1)) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Puce EMV simulée -->
            <?php if ($showPuce): ?>
                <div class="cr80-emv-chip" title="Puce sans-contact">
                    <div class="chip-line chip-l1"></div>
                    <div class="chip-line chip-l2"></div>
                    <div class="chip-line chip-l3"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Colonne Informations Bénéficiaire -->
        <div class="cr80-info-col">
            <div class="cr80-field-name"> 
                <span class="field-val-nom"><?= htmlspecialchars(strtoupper($b['nom'] ?? 'NOM')) ?></span>
                <span class="field-val-prenom"><?= htmlspecialchars($b['prenom'] ?? 'Prénom') ?></span>
            </div>

            <div class="cr80-badge-matricule">
                <span class="lbl"><?= htmlspecialchars($b['matricule_label'] ?? 'Matricule') ?> :</span>
                <span class="val"><?= htmlspecialchars($b['matricule'] ?? 'N/A') ?></span>
            </div>

            <div class="cr80-details-grid">
                <?php if ($type === 'eleve'): ?>
                    <div class="info-row">
                        <span class="lbl">Classe :</span>
                        <span class="val font-highlight"><?= htmlspecialchars($b['classe_nom'] ?? 'N/A') ?></span>
                    </div>
                <?php elseif ($type === 'etudiant'): ?>
                    <div class="info-row">
                        <span class="lbl">Filière :</span>
                        <span class="val font-highlight"><?= htmlspecialchars($b['filiere_nom'] ?? 'N/A') ?></span>
                    </div>
                    <?php if (!empty($b['cycle'])): ?>
                        <div class="info-row">
                            <span class="lbl">Cycle :</span>
                            <span class="val"><?= htmlspecialchars(strtoupper($b['cycle'])) ?></span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="info-row">
                        <span class="lbl">Fonction :</span>
                        <span class="val font-highlight"><?= htmlspecialchars($b['fonction'] ?? 'Personnel') ?></span>
                    </div>
                    <?php if (!empty($b['service']) || !empty($b['departement_nom'])): ?>
                        <div class="info-row">
                            <span class="lbl">Service :</span>
                            <span class="val"><?= htmlspecialchars($b['service'] ?: ($b['departement_nom'] ?? '')) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($b['date_naissance'])): ?>
                    <div class="info-row">
                        <span class="lbl">Né(e) le :</span>
                        <span class="val"><?= date('d/m/Y', strtotime($b['date_naissance'])) ?></span>
                    </div>
                <?php endif; ?>

                <div class="info-row">
                    <span class="lbl">Expire le :</span>
                    <span class="val font-exp"><?= date('d/m/Y', strtotime($card['date_expiration'] ?? '+1 year')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de carte : Code-barres vectoriel -->
    <div class="cr80-footer">
        <?php if (!isset($rectoCfg['afficher_barcode']) || !empty($rectoCfg['afficher_barcode'])): ?>
            <div class="cr80-barcode-container">
                <?= $barcodeSvg ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($rectoCfg['afficher_microtexte'])): ?>
        <div class="cr80-security-microtext">
            <?= htmlspecialchars($rectoCfg['titre_haut'] ?? 'RÉPUBLIQUE GABONAISE') ?> • <?= htmlspecialchars($etabCode) ?> • CARTE SÉCURISÉE • N° <?= htmlspecialchars($card['numero_carte'] ?? '') ?>
        </div>
        <?php endif; ?>
    </div>
</div>
