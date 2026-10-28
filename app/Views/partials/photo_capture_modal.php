<!-- =========================================================================
     MBOLO - Studio de Capture Photo (Webcam)
     Composant modal partagé : Éléves, Étudiants, Personnel
     ========================================================================= -->

<!-- Modal 1 : Studio Photo Vidéo (Webcam) -->
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
                            Studio Webcam & Photo d'Identité
                        </h5>
                        <small class="text-white-50" style="font-size: 0.75rem;">Capture directe avec cadrage officiel (3:4)</small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span id="cameraStatusBadge" class="badge rounded-pill bg-secondary bg-opacity-50 text-white fw-normal px-3 py-2 d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="status-indicator-dot bg-warning"></span>
                        <span id="cameraStatusText">Initialisation...</span>
                    </span>
                    <button type="button" class="btn-close btn-close-white" id="btnCloseWebcam" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>

            <div class="modal-body p-3 p-md-4 bg-dark">
                <!-- Barre d'outils et sélecteur de caméra -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-6 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-secondary bg-opacity-25 text-white border-secondary border-opacity-50">
                                <i class="bi bi-webcam text-primary"></i>
                            </span>
                            <select id="cameraSelect" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50" aria-label="Sélectionner la caméra">
                                <option value="">Détection des webcams...</option>
                            </select>
                            <button class="btn btn-outline-secondary border-opacity-50 text-white" type="button" id="btnRefreshCameras" title="Rechercher à nouveau les webcams">
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

            <!-- Pied de page de la modale -->
            <div class="modal-footer bg-dark border-top border-secondary border-opacity-25 px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">
                    Annuler
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
