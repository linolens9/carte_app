<?php
/**
 * Face VERSO de la carte CR-80
 * Aligné fidèlement d'après les spécifications visuelles officielles
 */
$stampId = 'stamp_' . ($card['id'] ?? uniqid());
$contactText = !empty($versoCfg['mention_retour']) ? $versoCfg['mention_retour'] : ('Contact administratif : ' . ($card['etablissement_ville'] ?? 'Libreville, Gabon'));
?>
<div class="cr80-inner cr80-verso-inner">
    <!-- Bandeau tricolore supérieur -->
    <div class="cr80-gabon-ribbon">
        <span class="ribbon-green"></span>
        <span class="ribbon-yellow"></span>
        <span class="ribbon-blue"></span>
    </div>

    <!-- Corps Verso : Instructions à gauche, QR Code à droite -->
    <div class="cr80-verso-body">
        <div class="cr80-verso-left">
            <div class="cr80-verso-title">
                <i class="bi bi-info-circle"></i>
                <span><?= htmlspecialchars($versoCfg['titre'] ?? 'CONDITIONS D\'UTILISATION') ?></span>
            </div>
            
            <div class="cr80-verso-text">
                <?= nl2br(htmlspecialchars($versoCfg['texte_reglement'] ?? "Cette carte est strictement personnelle.\nEn cas de perte, merci de la rapporter immédiatement à\nl'administration de l'établissement.")) ?>
            </div>

            <div class="cr80-verso-contact">
                <i class="bi bi-geo-alt-fill"></i>
                <span><?= htmlspecialchars($contactText) ?></span>
            </div>

            <!-- Signature autorisée & Cachet -->
            <?php if (!isset($versoCfg['afficher_signature']) || !empty($versoCfg['afficher_signature'])): ?>
            <div class="cr80-verso-signature-box">
                <div class="signature-title"><?= htmlspecialchars($versoCfg['titre_signature'] ?? 'Le Chef d\'Établissement') ?></div>
                <div class="signature-stamp">
                    <?php if (!empty($cachetSrc)): ?>
                        <img src="<?= htmlspecialchars($cachetSrc) ?>" alt="Cachet Établissement" class="cachet-img" onerror="this.style.display='none'; var s=this.nextElementSibling; if(s) s.style.display='block';">
                    <?php endif; ?>
                    <svg class="cachet-svg" style="<?= !empty($cachetSrc) ? 'display:none;' : '' ?>" viewBox="0 0 60 60" width="46" height="46" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="30" cy="30" r="26" fill="none" stroke="#2563eb" stroke-width="1.3" stroke-dasharray="3.5,2.5" />
                        <defs>
                            <path id="cTop_<?= $stampId ?>" d="M 7,30 A 23,23 0 0,1 53,30" />
                            <path id="cBot_<?= $stampId ?>" d="M 53,30 A 23,23 0 0,1 7,30" />
                        </defs>
                        <text fill="#2563eb" font-size="5.2" font-weight="800" font-family="system-ui, -apple-system, sans-serif" letter-spacing="0.8">
                            <textPath href="#cTop_<?= $stampId ?>" startOffset="50%" text-anchor="middle">ÉTABLISSEMENT</textPath>
                        </text>
                        <g transform="translate(22, 22) scale(1)">
                            <path fill="#2563eb" d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708"/>
                        </g>
                        <text fill="#2563eb" font-size="5.2" font-weight="800" font-family="system-ui, -apple-system, sans-serif" letter-spacing="1.2">
                            <textPath href="#cBot_<?= $stampId ?>" startOffset="50%" text-anchor="middle">OFFICIEL</textPath>
                        </text>
                    </svg>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="cr80-verso-right">
            <?php if (!isset($versoCfg['afficher_qr']) || !empty($versoCfg['afficher_qr'])): ?>
            <div class="cr80-qr-box">
                <div class="cr80-qr-image">
                    <?= $qrSvg ?>
                </div>
                <div class="cr80-qr-label">
                    <span>VÉRIFICATION</span>
                    <span>OFFICIELLE</span>
                </div>
                <div class="cr80-qr-hint">
                    <span>Scannez pour vérifier</span>
                    <span>l'authenticité</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="cr80-jeton-code">
                <span class="jeton-lbl">HASH :</span>
                <span class="jeton-val"><?= substr($card['jeton_verification'] ?? '0000000000000000', 0, 16) ?></span>
            </div>
        </div>
    </div>

    <!-- Pied Verso -->
    <div class="cr80-verso-footer">
        <span>CARTE D'IDENTITÉ NUMÉRIQUE HOMOLOGUÉE • MBOLO ID GABON</span>
    </div>
</div>
