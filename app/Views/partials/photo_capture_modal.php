<!-- =========================================================================
     MBOLO - Studio de Capture Photo (Webcam & Smartphone branché en USB)
     Composant modal partagé : Éléves, Étudiants, Personnel
     ========================================================================= -->

<!-- Modal 1 : Studio Photo Vidéo (Webcam & Smartphone USB) -->
<div class="modal fade" id="modalWebcam" tabindex="-1" aria-labelledby="modalWebcamLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <!-- En-tête de la modale -->
            <div class="modal-header bg-dark text-white border-bottom border-secondary border-opacity-25 px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-25 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-camera-video-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalWebcamLabel" style="font-size: 1.1rem;">
                            Studio de Capture Photo
                        </h5>
                        <small class="text-white-50" style="font-size: 0.75rem;">Webcam & Smartphone branché en USB (Haute Définition)</small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Badge d'état USB / Caméra -->
                    <span id="cameraStatusBadge" class="badge rounded-pill bg-secondary bg-opacity-50 text-white fw-normal px-3 py-2 d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="status-indicator-dot bg-warning"></span>
                        <span id="cameraStatusText">Initialisation...</span>
                    </span>
                    <button type="button" class="btn-close btn-close-white" id="btnCloseWebcam" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>

            <!-- Onglets de navigation -->
            <div class="bg-dark px-4 pt-2 border-bottom border-secondary border-opacity-25">
                <ul class="nav nav-tabs border-0" id="studioTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-white border-0 py-2 px-3 fw-semibold small" id="tab-direct-camera" data-bs-toggle="tab" data-bs-target="#panel-direct-camera" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-camera-reels me-1 text-primary"></i>Caméra Directe (USB & Webcam)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white-50 border-0 py-2 px-3 fw-semibold small" id="tab-stream-camera" data-bs-toggle="tab" data-bs-target="#panel-stream-camera" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-hdd-network me-1 text-info"></i>Flux Smartphone USB (IP / DroidCam)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white-50 border-0 py-2 px-3 fw-semibold small" id="tab-usb-guide" data-bs-toggle="tab" data-bs-target="#panel-usb-guide" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-question-circle me-1 text-warning"></i>Guide Connexion Smartphone USB
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-0 bg-dark">
                <div class="tab-content" id="studioTabsContent">
                    
                    <!-- ==========================================
                         PANEL 1 : Caméra Directe (Webcam / USB)
                         ========================================== -->
                    <div class="tab-pane fade show active p-3 p-md-4" id="panel-direct-camera" role="tabpanel">
                        <!-- Barre d'outils et sélecteur de caméra -->
                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-md-6 col-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-secondary bg-opacity-25 text-white border-secondary border-opacity-50">
                                        <i class="bi bi-webcam text-primary"></i>
                                    </span>
                                    <select id="cameraSelect" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50" aria-label="Sélectionner la caméra">
                                        <option value="">Détection des caméras...</option>
                                    </select>
                                    <button class="btn btn-outline-secondary border-opacity-50 text-white" type="button" id="btnRefreshCameras" title="Rechercher à nouveau les caméras USB branchées">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-3 col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-secondary bg-opacity-25 text-white border-secondary border-opacity-50" title="Résolution vidéo">
                                        <i class="bi bi-badge-hd text-info"></i>
                                    </span>
                                    <select id="cameraResolutionSelect" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50">
                                        <option value="1080" selected>Full HD (1080p)</option>
                                        <option value="720">HD (720p)</option>
                                        <option value="480">Standard (480p)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-6 d-flex gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary border-opacity-50 text-white flex-grow-1" id="btnToggleMirror" title="Inverser horizontalement (effet miroir)">
                                    <i class="bi bi-symmetry-vertical me-1"></i>Miroir
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary border-opacity-50 text-white dropdown-toggle" type="button" id="btnTimerDropdown" data-bs-toggle="dropdown" title="Retardateur">
                                        <i class="bi bi-stopwatch"></i> <span id="timerLabel">0s</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                        <li><a class="dropdown-item timer-option active" href="#" data-seconds="0"><i class="bi bi-lightning-charge me-2"></i>Instantané (0s)</a></li>
                                        <li><a class="dropdown-item timer-option" href="#" data-seconds="3"><i class="bi bi-stopwatch me-2"></i>Retardateur 3 sec</a></li>
                                        <li><a class="dropdown-item timer-option" href="#" data-seconds="5"><i class="bi bi-stopwatch me-2"></i>Retardateur 5 sec</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Smartphone branché en USB -->
                        <div id="smartphoneUsbAlert" class="alert alert-success alert-dismissible fade show d-none py-2 px-3 mb-3 border-0 bg-success bg-opacity-20 text-success small" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-phone-fill fs-5 me-2"></i>
                                <div>
                                    <strong>Smartphone détecté par câble USB !</strong> La caméra smartphone a été sélectionnée avec succès en haute définition.
                                </div>
                            </div>
                        </div>

                        <!-- Zone de cadrage vidéo -->
                        <div class="position-relative mx-auto rounded-3 overflow-hidden shadow-lg video-viewport-container" 
                             style="max-width: 520px; height: 380px; background: #000; border: 2px solid rgba(255,255,255,0.1);">
                            
                            <!-- Élément Vidéo en direct -->
                            <video id="webcamVideo" autoplay playsinline muted class="w-100 h-100 object-fit-cover"></video>

                            <!-- Guide Ovale conforme photo d'identité (Ratio 3:4) -->
                            <div class="face-guide-overlay position-absolute top-0 start-0 w-100 h-100 pointer-events-none d-flex flex-column align-items-center justify-content-center">
                                <div class="face-guide-oval position-relative">
                                    <!-- Ligne repère des yeux -->
                                    <div class="guide-line-eyes position-absolute top-50 start-0 w-100 border-top border-warning border-opacity-50" style="border-style: dotted !important;"></div>
                                    <!-- Repère vertical central -->
                                    <div class="guide-line-center position-absolute top-0 start-50 h-100 border-start border-warning border-opacity-50" style="border-style: dotted !important;"></div>
                                </div>
                            </div>

                            <!-- Effet Flash photo -->
                            <div id="cameraFlashOverlay" class="camera-flash-overlay position-absolute top-0 start-0 w-100 h-100 bg-white opacity-0 pointer-events-none"></div>

                            <!-- Compte à rebours animé -->
                            <div id="cameraCountdownOverlay" class="position-absolute top-50 start-50 translate-middle text-white fw-bold display-1 d-none" style="text-shadow: 0 4px 20px rgba(0,0,0,0.8); z-index: 10;">
                                3
                            </div>

                            <!-- Badge d'information flux en bas à gauche -->
                            <div class="position-absolute bottom-0 start-0 m-2 px-2 py-1 rounded bg-black bg-opacity-75 text-white-50 small d-flex align-items-center gap-1" style="font-size: 0.72rem; z-index: 5;">
                                <i class="bi bi-broadcast text-success"></i>
                                <span id="streamResolutionInfo">Prêt</span>
                            </div>

                            <!-- Badge indicateur périphérique en haut à droite -->
                            <div class="position-absolute top-0 end-0 m-2 px-2 py-1 rounded bg-black bg-opacity-75 text-white small d-flex align-items-center gap-1" style="font-size: 0.72rem; z-index: 5;">
                                <span id="deviceTypePill"><i class="bi bi-webcam me-1"></i>Webcam</span>
                            </div>
                        </div>

                        <!-- Instructions sous la vidéo -->
                        <div class="text-center mt-3">
                            <p class="text-white-50 small mb-0">
                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                Cadrez le visage dans l'ovale (yeux sur la ligne supérieure, menton au bas de l'ovale).
                            </p>
                        </div>
                    </div>

                    <!-- ==========================================
                         PANEL 2 : Flux Smartphone USB / IP (Stream)
                         ========================================== -->
                    <div class="tab-pane fade p-3 p-md-4 text-white" id="panel-stream-camera" role="tabpanel">
                        <div class="card bg-secondary bg-opacity-10 border border-secondary border-opacity-25 p-3 mb-3">
                            <h6 class="fw-bold text-white mb-2"><i class="bi bi-hdd-network text-info me-2"></i>Flux Smartphone par Câble USB (Tethering ou ADB)</h6>
                            <p class="text-white-50 small mb-2">
                                Si vous utilisez une application comme <strong>IP Webcam</strong> ou <strong>DroidCam</strong> avec le smartphone branché par câble USB (partage de connexion USB ou port ADB), renseignez ici l'adresse du flux vidéo :
                            </p>
                            
                            <div class="row g-2 align-items-center">
                                <div class="col-md-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-dark text-white border-secondary border-opacity-50">URL du flux</span>
                                        <input type="text" id="streamUrlInput" class="form-control bg-dark text-white border-secondary border-opacity-50" placeholder="http://127.0.0.1:4747/video ou http://192.168.42.129:8080/video" value="http://127.0.0.1:4747/video">
                                    </div>
                                </div>
                                <div class="col-md-5 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-info text-dark fw-semibold flex-grow-1" id="btnConnectStream">
                                        <i class="bi bi-play-circle me-1"></i>Connecter le flux
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary border-opacity-50 text-white" id="btnStreamPresetDroidcam" title="Preset DroidCam USB">
                                        DroidCam
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary border-opacity-50 text-white" id="btnStreamPresetIpWebcam" title="Preset IP Webcam USB">
                                        IP Webcam
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Affichage du flux Stream -->
                        <div class="position-relative mx-auto rounded-3 overflow-hidden shadow-lg video-viewport-container" 
                             style="max-width: 520px; height: 350px; background: #000; border: 2px solid rgba(255,255,255,0.1);">
                            
                            <img id="streamVideoImg" src="" alt="Flux Smartphone en attente" class="w-100 h-100 object-fit-cover d-none" crossorigin="anonymous">
                            
                            <div id="streamPlaceholder" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50 p-4 text-center">
                                <i class="bi bi-phone-vibrate fs-1 text-secondary mb-2"></i>
                                <span class="fw-semibold">En attente de connexion au flux smartphone...</span>
                                <small class="text-white-50 mt-1">Cliquez sur « Connecter le flux » ci-dessus après avoir lancé l'application sur le téléphone relié par câble USB.</small>
                            </div>

                            <!-- Ovale de cadrage -->
                            <div class="face-guide-overlay position-absolute top-0 start-0 w-100 h-100 pointer-events-none d-flex flex-column align-items-center justify-content-center">
                                <div class="face-guide-oval position-relative">
                                    <div class="guide-line-eyes position-absolute top-50 start-0 w-100 border-top border-warning border-opacity-50" style="border-style: dotted !important;"></div>
                                    <div class="guide-line-center position-absolute top-0 start-50 h-100 border-start border-warning border-opacity-50" style="border-style: dotted !important;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================
                         PANEL 3 : Guide Connexion Smartphone USB
                         ========================================== -->
                    <div class="tab-pane fade p-3 p-md-4 text-white" id="panel-usb-guide" role="tabpanel">
                        <div class="row g-3">
                            <!-- Méthode 1 : Android 14+ USB Webcam Native -->
                            <div class="col-md-6">
                                <div class="card h-100 bg-secondary bg-opacity-10 border border-success border-opacity-50 p-3 rounded-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-success bg-opacity-25 p-2 text-success">
                                            <i class="bi bi-android2 fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-success mb-0">Méthode 1 : Android 14+ (Mode Natif)</h6>
                                            <small class="text-white-50">Sans aucune application supplémentaire</small>
                                        </div>
                                    </div>
                                    <ol class="small text-white-50 ps-3 mb-0" style="line-height: 1.6;">
                                        <li>Branchez votre smartphone Android au PC à l'aide d'un <strong>câble USB</strong>.</li>
                                        <li>Faites glisser la barre de notification sur le téléphone, touchez <strong>« Recharge via USB »</strong> ou « Options USB ».</li>
                                        <li>Dans la liste, choisissez <strong>« Webcam »</strong> (ou Caméra Web).</li>
                                        <li>Le smartphone apparaît instantanément dans la liste des caméras ci-dessus en <strong>Haute Définition</strong> !</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Méthode 2 : DroidCam / Iriun Webcam (Tous smartphones) -->
                            <div class="col-md-6">
                                <div class="card h-100 bg-secondary bg-opacity-10 border border-primary border-opacity-50 p-3 rounded-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-primary bg-opacity-25 p-2 text-primary">
                                            <i class="bi bi-usb-symbol fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-primary mb-0">Méthode 2 : DroidCam ou Iriun (USB)</h6>
                                            <small class="text-white-50">Pour tous smartphones Android & iPhone</small>
                                        </div>
                                    </div>
                                    <ol class="small text-white-50 ps-3 mb-0" style="line-height: 1.6;">
                                        <li>Installez <strong>DroidCam</strong> ou <strong>Iriun Webcam</strong> sur le smartphone et le logiciel client PC gratuit.</li>
                                        <li>Reliez le téléphone par <strong>câble USB</strong> (activez le Débogage USB pour DroidCam).</li>
                                        <li>Lancez l'application en mode USB : le flux vidéo est transmis à <strong>ultra-haute vitesse</strong> et sans aucun décalage.</li>
                                        <li>Sélectionnez la source <em>« DroidCam »</em> ou <em>« Iriun »</em> dans la liste des caméras.</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Méthode 3 : Windows 11 Lien avec Windows -->
                            <div class="col-md-6">
                                <div class="card h-100 bg-secondary bg-opacity-10 border border-info border-opacity-50 p-3 rounded-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-info bg-opacity-25 p-2 text-info">
                                            <i class="bi bi-windows fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-info mb-0">Méthode 3 : Windows 11 (Lien mobile)</h6>
                                            <small class="text-white-50">Fonctionnalité intégrée Windows 11</small>
                                        </div>
                                    </div>
                                    <ol class="small text-white-50 ps-3 mb-0" style="line-height: 1.6;">
                                        <li>Sur votre PC Windows 11, ouvrez <strong>Paramètres > Bluetooth et appareils > Appareils mobiles</strong>.</li>
                                        <li>Activez <strong>« Autoriser ce PC à accéder à vos appareils mobiles »</strong>.</li>
                                        <li>Cochez <strong>« Utiliser comme caméra connectée »</strong> pour votre smartphone.</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Méthode 4 : Avantages Smartphone -->
                            <div class="col-md-6">
                                <div class="card h-100 bg-secondary bg-opacity-10 border border-warning border-opacity-50 p-3 rounded-4">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-warning bg-opacity-25 p-2 text-warning">
                                            <i class="bi bi-stars fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-warning mb-0">Pourquoi filmer au Smartphone USB ?</h6>
                                            <small class="text-white-50">Qualité professionnelle garantie</small>
                                        </div>
                                    </div>
                                    <ul class="small text-white-50 ps-3 mb-0" style="line-height: 1.6;">
                                        <li>Capteur optique supérieur aux webcams intégrées d'ordinateurs portables.</li>
                                        <li>Excellente netteté des yeux et du grain de peau pour l'impression de cartes PVC.</li>
                                        <li>Le câble USB garantit <strong>zéro latence</strong> et recharge le smartphone pendant la prise de vue.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Pied de page de la modale -->
            <div class="modal-footer bg-dark border-top border-secondary border-opacity-25 px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">
                    Annuler
                </button>

                <div class="d-flex align-items-center gap-2">
                    <!-- Bouton pour basculer vers le guide si besoin -->
                    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 d-none d-sm-inline-flex align-items-center gap-1" id="btnShowUsbHelp">
                        <i class="bi bi-usb-symbol"></i>Aide USB
                    </button>

                    <!-- Bouton principal de prise de photo -->
                    <button type="button" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow d-flex align-items-center gap-2" id="btnCapturePhoto">
                        <i class="bi bi-camera-fill fs-5"></i>
                        <span>Prendre la photo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2 : Recadrage Officiel Cropper.js (Ratio Portrait 3:4) -->
<div class="modal fade" id="modalCrop" tabindex="-1" aria-labelledby="modalCropLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-crop fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCropLabel" style="font-size: 1.1rem;">
                            Ajuster et Recadrer la Photo (3:4)
                        </h5>
                        <small class="text-muted" style="font-size: 0.75rem;">Format officiel cartes d'identité (600×800 px)</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body p-3 bg-dark">
                <!-- Zone de recadrage Cropper -->
                <div style="max-height: 480px; min-height: 320px; background: #0f172a;" class="d-flex align-items-center justify-content-center rounded-3 overflow-hidden shadow-inner">
                    <img id="imageToCrop" src="" alt="Aperçu pour recadrage" style="max-width: 100%; display: block;">
                </div>

                <!-- Outils de rotation et ajustement -->
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnRotateLeft" title="Pivoter de 90° vers la gauche">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Pivoter -90°
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnRotateRight" title="Pivoter de 90° vers la droite">
                        <i class="bi bi-arrow-clockwise me-1"></i>Pivoter +90°
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnFlipCrop" title="Inverser horizontalement">
                        <i class="bi bi-symmetry-vertical me-1"></i>Inverser
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" id="btnResetCrop" title="Réinitialiser le cadrage">
                        <i class="bi bi-arrow-repeat me-1"></i>Réinitialiser
                    </button>
                </div>
            </div>

            <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    Annuler
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" id="btnValidateCrop">
                    <i class="bi bi-check2-circle fs-5"></i>
                    <span>Valider la photo (600×800)</span>
                </button>
            </div>
        </div>
    </div>
</div>

<canvas id="webcamCanvas" class="d-none"></canvas>
