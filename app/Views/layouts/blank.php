<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Impression - Carte App') ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= rtrim((string)config('app.url', 'http://localhost/carte_app'), '/') ?>/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= rtrim((string)config('app.url', 'http://localhost/carte_app'), '/') ?>/assets/img/favicon.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS avec cache-buster -->
    <link href="<?= rtrim((string)config('app.url', 'http://localhost/carte_app'), '/') ?>/assets/css/app.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* Garantie absolue : aucun élément verso à plat ne doit être pivoté à 180° ni être inversé en miroir */
        .cr80-flat,
        .cr80-verso,
        .cr80-flat.cr80-verso,
        .cr80-mode-verso_only .cr80-verso,
        .cr80-mode-both .cr80-verso,
        .crop-mark-card .cr80-verso {
            transform: none !important;
            -webkit-transform: none !important;
            -webkit-backface-visibility: visible !important;
            backface-visibility: visible !important;
        }

        @media print {
            .cr80-flat,
            .cr80-verso,
            .cr80-flat.cr80-verso,
            .cr80-mode-verso_only .cr80-verso,
            .cr80-mode-both .cr80-verso,
            .crop-mark-card .cr80-verso {
                transform: none !important;
                -webkit-transform: none !important;
                -webkit-backface-visibility: visible !important;
                backface-visibility: visible !important;
            }

            /* Alignement textuel et dimensionnel strict pour l'impression CR-80 */
            .cr80-face, .cr80-flat {
                width: 85.6mm !important;
                height: 53.98mm !important;
                box-shadow: none !important;
                border: 0.25mm solid #cbd5e1 !important;
                border-radius: 3.18mm !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: inline-block !important;
                position: relative !important;
            }

            .cr80-inner {
                padding: 0.50rem 0.70rem 0.35rem !important;
                height: 100% !important;
                box-sizing: border-box !important;
            }

            .cr80-header {
                border-bottom: 2px solid var(--c-primary, #1e3a8a) !important;
                padding-bottom: 0.25rem !important;
            }

            .cr80-etab-name {
                font-size: 0.70rem !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
                color: var(--c-primary, #1e3a8a) !important;
            }

            .cr80-card-title {
                font-size: 0.64rem !important;
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .cr80-card-subtitle {
                font-size: 0.54rem !important;
                color: #64748b !important;
            }

            .cr80-photo-frame {
                width: 70px !important;
                height: 92px !important;
                border: 1.5px solid var(--c-primary, #1e3a8a) !important;
                border-radius: 8px !important;
            }

            .cr80-emv-chip {
                width: 30px !important;
                height: 20px !important;
                background: linear-gradient(135deg, #fef08a 0%, #eab308 50%, #ca8a04 100%) !important;
                border: 0.8px solid #a16207 !important;
                margin-top: 0.3rem !important;
            }

            .field-val-nom {
                font-size: 0.80rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }

            .field-val-prenom {
                font-size: 0.72rem !important;
                font-weight: 700 !important;
                color: var(--c-primary, #1e3a8a) !important;
            }

            .cr80-badge-matricule {
                background: #f0f7ff !important;
                border: 1px dashed #2563eb !important;
                border-radius: 6px !important;
                padding: 0.15rem 0.50rem 0.12rem !important;
                margin-top: 0.2rem !important;
                margin-bottom: 0.35rem !important;
            }

            .cr80-badge-matricule .lbl {
                font-size: 0.58rem !important;
                color: #64748b !important;
                font-weight: 600 !important;
            }

            .cr80-badge-matricule .val {
                font-size: 0.62rem !important;
                color: #0f172a !important;
                font-weight: 800 !important;
            }

            .cr80-details-grid {
                gap: 0.18rem !important;
                font-size: 0.58rem !important;
            }

            .cr80-details-grid .info-row {
                display: flex !important;
                align-items: baseline !important;
                gap: 0.35rem !important;
            }

            .cr80-details-grid .lbl {
                min-width: 58px !important;
                width: 58px !important;
                flex-shrink: 0 !important;
                color: #64748b !important;
                font-weight: 600 !important;
            }

            .cr80-details-grid .val {
                color: #1e293b !important;
                font-weight: 700 !important;
            }

            .cr80-details-grid .font-highlight {
                color: var(--c-primary, #1e3a8a) !important;
                font-weight: 800 !important;
            }

            .cr80-details-grid .font-exp {
                color: #dc2626 !important;
                font-weight: 800 !important;
            }

            .cr80-footer {
                margin-top: auto !important;
                padding-top: 0.2rem !important;
            }

            .cr80-barcode-container {
                height: 34px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .cr80-barcode-container svg {
                width: 95% !important;
                height: 34px !important;
                max-height: 34px !important;
            }

            .signature-stamp .cachet-img {
                max-height: 48px !important;
                max-width: 58px !important;
                width: auto !important;
                height: auto !important;
                object-fit: contain !important;
                transform: rotate(-8deg) !important;
                opacity: 0.95 !important;
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        @media screen {
            body {
                background: #0f172a;
                padding-bottom: 80px;
            }
            .print-toolbar {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(15, 23, 42, 0.9);
                backdrop-filter: blur(8px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 50px;
                padding: 10px 24px;
                display: flex;
                align-items: center;
                gap: 16px;
                z-index: 9999;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            }
        }
    </style>
</head>
<body>

    <?= $content ?>

    <!-- Toolbar d'impression (masquée automatiquement à l'impression) -->
    <div class="print-toolbar no-print">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
        <span class="text-white small">Format d'impression calibré CR-80</span>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 shadow fw-bold" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Lancer l'impression
        </button>
    </div>

</body>
</html>
