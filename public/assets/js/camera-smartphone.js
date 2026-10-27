/**
 * =========================================================================
 * MBOLO - Studio de Capture Photo & Vidéo (Webcam & Smartphone branché en USB)
 * Gère l'acquisition caméra directe HD, la détection USB temps réel,
 * le flux IP/DroidCam, l'audio de déclencheur, le compte à rebours et Cropper.js.
 * =========================================================================
 */
(function () {
    'use strict';

    class CameraSmartphoneStudio {
        constructor() {
            // Éléments du formulaire hôte
            this.photoBase64Input = document.getElementById('photoBase64Input');
            this.fileInputPhoto = document.getElementById('fileInputPhoto');
            this.previewPhoto = document.getElementById('previewPhoto') || document.getElementById('photoPreviewImg');
            this.placeholderPhoto = document.getElementById('placeholderPhoto') || document.getElementById('photoPlaceholder');
            this.badgeCropped = document.getElementById('badgeCropped');
            this.btnResetPhoto = document.getElementById('btnResetPhoto');

            // Boutons d'ouverture
            this.btnOpenWebcam = document.getElementById('btnOpenWebcam');
            this.btnOpenSmartphone = document.getElementById('btnOpenSmartphone');

            // Modales Bootstrap
            this.modalWebcamEl = document.getElementById('modalWebcam');
            this.modalCropEl = document.getElementById('modalCrop');
            this.modalWebcam = this.modalWebcamEl ? new bootstrap.Modal(this.modalWebcamEl) : null;
            this.modalCrop = this.modalCropEl ? new bootstrap.Modal(this.modalCropEl) : null;

            // Éléments Studio
            this.webcamVideo = document.getElementById('webcamVideo');
            this.webcamCanvas = document.getElementById('webcamCanvas') || document.createElement('canvas');
            this.cameraSelect = document.getElementById('cameraSelect');
            this.cameraResolutionSelect = document.getElementById('cameraResolutionSelect');
            this.btnRefreshCameras = document.getElementById('btnRefreshCameras');
            this.btnToggleMirror = document.getElementById('btnToggleMirror');
            this.btnCapturePhoto = document.getElementById('btnCapturePhoto');
            this.cameraStatusBadge = document.getElementById('cameraStatusBadge');
            this.cameraStatusText = document.getElementById('cameraStatusText');
            this.streamResolutionInfo = document.getElementById('streamResolutionInfo');
            this.deviceTypePill = document.getElementById('deviceTypePill');
            this.smartphoneUsbAlert = document.getElementById('smartphoneUsbAlert');
            this.cameraFlashOverlay = document.getElementById('cameraFlashOverlay');
            this.cameraCountdownOverlay = document.getElementById('cameraCountdownOverlay');
            this.btnShowUsbHelp = document.getElementById('btnShowUsbHelp');

            // Stream URL elements
            this.streamUrlInput = document.getElementById('streamUrlInput');
            this.btnConnectStream = document.getElementById('btnConnectStream');
            this.streamVideoImg = document.getElementById('streamVideoImg');
            this.streamPlaceholder = document.getElementById('streamPlaceholder');
            this.btnStreamPresetDroidcam = document.getElementById('btnStreamPresetDroidcam');
            this.btnStreamPresetIpWebcam = document.getElementById('btnStreamPresetIpWebcam');

            // Éléments Cropper
            this.imageToCrop = document.getElementById('imageToCrop');
            this.btnValidateCrop = document.getElementById('btnValidateCrop') || document.getElementById('btnCropApply');
            this.btnRotateLeft = document.getElementById('btnRotateLeft');
            this.btnRotateRight = document.getElementById('btnRotateRight');
            this.btnFlipCrop = document.getElementById('btnFlipCrop');
            this.btnResetCrop = document.getElementById('btnResetCrop');

            // États internes
            this.stream = null;
            this.cropper = null;
            this.isMirrored = false;
            this.isFlippedH = false;
            this.timerSeconds = 0;
            this.isCountingDown = false;
            this.availableDevices = [];
            this.activeDeviceId = null;
            this.isStreamMode = false;
            this.audioCtx = null;

            this.init();
        }

        init() {
            if (!this.modalWebcamEl || !this.modalCropEl) {
                return;
            }

            this.bindEvents();
            this.listenDeviceChanges();
        }

        bindEvents() {
            // 1. Bouton "Prendre via Webcam / Caméra USB"
            if (this.btnOpenWebcam) {
                this.btnOpenWebcam.addEventListener('click', () => {
                    this.openStudio({ preferSmartphone: false });
                });
            }

            // 2. Bouton "Filmer via Smartphone (Câble USB)"
            if (this.btnOpenSmartphone) {
                this.btnOpenSmartphone.addEventListener('click', () => {
                    this.openStudio({ preferSmartphone: true });
                });
            }

            // 3. Fermeture de la modale caméra -> arrêt du flux
            this.modalWebcamEl.addEventListener('hidden.bs.modal', () => {
                this.stopStream();
                this.disconnectStreamUrl();
            });

            // 4. Changement de caméra
            if (this.cameraSelect) {
                this.cameraSelect.addEventListener('change', () => {
                    this.startCameraStream(this.cameraSelect.value);
                });
            }

            // 5. Changement de résolution demandée
            if (this.cameraResolutionSelect) {
                this.cameraResolutionSelect.addEventListener('change', () => {
                    if (this.activeDeviceId) {
                        this.startCameraStream(this.activeDeviceId);
                    }
                });
            }

            // 6. Rafraîchir les caméras branchées
            if (this.btnRefreshCameras) {
                this.btnRefreshCameras.addEventListener('click', async () => {
                    this.btnRefreshCameras.classList.add('rotating-animation');
                    await this.enumerateVideoDevices(true);
                    setTimeout(() => {
                        this.btnRefreshCameras.classList.remove('rotating-animation');
                    }, 600);
                });
            }

            // 7. Basculer l'effet miroir
            if (this.btnToggleMirror) {
                this.btnToggleMirror.addEventListener('click', () => {
                    this.isMirrored = !this.isMirrored;
                    if (this.webcamVideo) {
                        this.webcamVideo.style.transform = this.isMirrored ? 'scaleX(-1)' : 'scaleX(1)';
                    }
                    this.btnToggleMirror.classList.toggle('active', this.isMirrored);
                });
            }

            // 8. Gestion du compte à rebours / retardateur
            document.querySelectorAll('.timer-option').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.querySelectorAll('.timer-option').forEach(el => el.classList.remove('active'));
                    item.classList.add('active');
                    this.timerSeconds = parseInt(item.getAttribute('data-seconds'), 10) || 0;
                    const timerLabel = document.getElementById('timerLabel');
                    if (timerLabel) {
                        timerLabel.textContent = this.timerSeconds === 0 ? '0s' : `${this.timerSeconds}s`;
                    }
                });
            });

            // 9. Déclencheur prise de photo
            if (this.btnCapturePhoto) {
                this.btnCapturePhoto.addEventListener('click', () => {
                    this.handleCaptureTrigger();
                });
            }

            // 10. Bouton Aide USB (bascule vers l'onglet guide)
            if (this.btnShowUsbHelp) {
                this.btnShowUsbHelp.addEventListener('click', () => {
                    const guideTabTrigger = document.getElementById('tab-usb-guide');
                    if (guideTabTrigger) {
                        bootstrap.Tab.getOrCreateInstance(guideTabTrigger).show();
                    }
                });
            }

            // 11. Flux Smartphone URL (Stream IP / DroidCam)
            if (this.btnConnectStream) {
                this.btnConnectStream.addEventListener('click', () => {
                    this.connectStreamUrl();
                });
            }
            if (this.btnStreamPresetDroidcam) {
                this.btnStreamPresetDroidcam.addEventListener('click', () => {
                    if (this.streamUrlInput) this.streamUrlInput.value = 'http://127.0.0.1:4747/video';
                    this.connectStreamUrl();
                });
            }
            if (this.btnStreamPresetIpWebcam) {
                this.btnStreamPresetIpWebcam.addEventListener('click', () => {
                    if (this.streamUrlInput) this.streamUrlInput.value = 'http://192.168.42.129:8080/video';
                    this.connectStreamUrl();
                });
            }

            // Changement d'onglet dans le studio
            const tabDirect = document.getElementById('tab-direct-camera');
            const tabStream = document.getElementById('tab-stream-camera');
            if (tabDirect) {
                tabDirect.addEventListener('shown.bs.tab', () => {
                    this.isStreamMode = false;
                    this.disconnectStreamUrl();
                    if (this.activeDeviceId) {
                        this.startCameraStream(this.activeDeviceId);
                    }
                });
            }
            if (tabStream) {
                tabStream.addEventListener('shown.bs.tab', () => {
                    this.isStreamMode = true;
                    this.stopStream();
                });
            }

            // 12. Téléversement classique de fichier image
            if (this.fileInputPhoto) {
                this.fileInputPhoto.addEventListener('change', (e) => {
                    const file = e.target.files && e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            this.openCropper(event.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 13. Réinitialiser la photo
            if (this.btnResetPhoto) {
                this.btnResetPhoto.addEventListener('click', () => {
                    this.resetPhoto();
                });
            }

            // 14. Outils de recadrage Cropper
            if (this.btnRotateLeft) {
                this.btnRotateLeft.addEventListener('click', () => this.cropper?.rotate(-90));
            }
            if (this.btnRotateRight) {
                this.btnRotateRight.addEventListener('click', () => this.cropper?.rotate(90));
            }
            if (this.btnFlipCrop) {
                this.btnFlipCrop.addEventListener('click', () => {
                    this.isFlippedH = !this.isFlippedH;
                    this.cropper?.scaleX(this.isFlippedH ? -1 : 1);
                });
            }
            if (this.btnResetCrop) {
                this.btnResetCrop.addEventListener('click', () => {
                    this.isFlippedH = false;
                    this.cropper?.reset();
                });
            }

            // 15. Validation du recadrage
            if (this.btnValidateCrop) {
                this.btnValidateCrop.addEventListener('click', () => {
                    this.applyCroppedResult();
                });
            }
        }

        /**
         * Écoute en temps réel les branchements / débranchements USB (hotplug)
         */
        listenDeviceChanges() {
            if (navigator.mediaDevices && navigator.mediaDevices.addEventListener) {
                navigator.mediaDevices.addEventListener('devicechange', async () => {
                    // Si la modale est ouverte, re-scanner immédiatement
                    if (this.modalWebcamEl.classList.contains('show')) {
                        const previousCount = this.availableDevices.length;
                        await this.enumerateVideoDevices(false);

                        // Si un nouveau smartphone a été branché
                        const currentSmartphones = this.availableDevices.filter(d => this.isSmartphoneDevice(d));
                        if (currentSmartphones.length > 0) {
                            this.showToast('📱 Smartphone détecté via USB ! Prêt à filmer.', 'success');
                            // Si la caméra active n'est pas un smartphone, basculer automatiquement
                            const currentDevice = this.availableDevices.find(d => d.deviceId === this.activeDeviceId);
                            if (!currentDevice || !this.isSmartphoneDevice(currentDevice)) {
                                const target = currentSmartphones[0];
                                if (this.cameraSelect) this.cameraSelect.value = target.deviceId;
                                this.startCameraStream(target.deviceId);
                            }
                        }
                    }
                });
            }
        }

        /**
         * Ouvre le Studio de Capture
         */
        async openStudio({ preferSmartphone = false } = {}) {
            try {
                // Activer le premier onglet par défaut
                const directTab = document.getElementById('tab-direct-camera');
                if (directTab) {
                    bootstrap.Tab.getOrCreateInstance(directTab).show();
                }

                this.modalWebcam.show();
                this.updateStatus('Recherche des caméras...', 'warning');

                // Énumération des caméras disponibles
                await this.enumerateVideoDevices(true);

                if (this.availableDevices.length === 0) {
                    this.updateStatus('Aucune caméra détectée', 'danger');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aucune caméra détectée',
                        html: `Le navigateur n'a pas détecté de caméra vidéo active.<br><br>
                               Branchez votre <strong>smartphone par câble USB</strong> et activez le mode <em>Webcam</em>, ou connectez une webcam USB.`,
                        confirmButtonText: 'Voir le guide USB'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            const guideTabTrigger = document.getElementById('tab-usb-guide');
                            if (guideTabTrigger) bootstrap.Tab.getOrCreateInstance(guideTabTrigger).show();
                        }
                    });
                    return;
                }

                // Déterminer la caméra à sélectionner
                let selectedDeviceId = null;
                const smartphoneDevice = this.availableDevices.find(d => this.isSmartphoneDevice(d));

                if (preferSmartphone) {
                    if (smartphoneDevice) {
                        selectedDeviceId = smartphoneDevice.deviceId;
                    } else {
                        // Pas encore de smartphone détecté : informer l'utilisateur avec bienveillance
                        Swal.fire({
                            icon: 'info',
                            title: 'Smartphone USB non détecté',
                            html: `Veuillez brancher votre smartphone au PC avec son <strong>câble USB</strong>.<br><br>
                                   Sur le téléphone : sélectionnez l'option <strong>« Webcam »</strong> (Android 14+) ou lancez <strong>DroidCam / Iriun</strong>.<br>
                                   Dès qu'il sera branché, la vidéo s'activera automatiquement !`,
                            confirmButtonText: 'Compris'
                        });
                        selectedDeviceId = this.availableDevices[0].deviceId;
                    }
                } else {
                    // Priorité au smartphone si déjà branché, sinon première caméra
                    selectedDeviceId = smartphoneDevice ? smartphoneDevice.deviceId : this.availableDevices[0].deviceId;
                }

                if (this.cameraSelect) {
                    this.cameraSelect.value = selectedDeviceId;
                }

                await this.startCameraStream(selectedDeviceId);

            } catch (err) {
                console.error("Erreur ouverture studio :", err);
                this.updateStatus('Erreur accès caméra', 'danger');
                Swal.fire({
                    icon: 'error',
                    title: 'Accès caméra refusé',
                    text: 'Veuillez autoriser l\'accès à la caméra dans les permissions de votre navigateur.'
                });
            }
        }

        /**
         * Énumère les périphériques vidéo et les catégorise (Smartphone USB vs Webcam)
         */
        async enumerateVideoDevices(requestPermissionFirst = false) {
            try {
                if (requestPermissionFirst && (!this.stream || !this.stream.active)) {
                    // Petite demande d'autorisation pour débloquer les labels
                    try {
                        const tempStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        tempStream.getTracks().forEach(t => t.stop());
                    } catch (e) {
                        // Continue quand même
                    }
                }

                const devices = await navigator.mediaDevices.enumerateDevices();
                this.availableDevices = devices.filter(d => d.kind === 'videoinput');

                if (!this.cameraSelect) return;

                this.cameraSelect.innerHTML = '';

                if (this.availableDevices.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.text = 'Aucune caméra trouvée';
                    this.cameraSelect.appendChild(opt);
                    return;
                }

                // Séparation Smartphones USB vs Webcams
                const smartphones = [];
                const webcams = [];

                this.availableDevices.forEach((dev, index) => {
                    const label = dev.label || `Caméra ${index + 1}`;
                    const isPhone = this.isSmartphoneDevice(dev);
                    if (isPhone) {
                        smartphones.push({ deviceId: dev.deviceId, label: label });
                    } else {
                        webcams.push({ deviceId: dev.deviceId, label: label });
                    }
                });

                // Groupe 1 : Smartphones détectés
                if (smartphones.length > 0) {
                    const groupPhone = document.createElement('optgroup');
                    groupPhone.label = '📱 Smartphones branchés en USB';
                    smartphones.forEach(dev => {
                        const opt = document.createElement('option');
                        opt.value = dev.deviceId;
                        opt.text = `📱 ${dev.label} [Smartphone USB]`;
                        groupPhone.appendChild(opt);
                    });
                    this.cameraSelect.appendChild(groupPhone);
                }

                // Groupe 2 : Webcams & autres caméras
                if (webcams.length > 0) {
                    const groupCam = document.createElement('optgroup');
                    groupCam.label = '💻 Webcams & Caméras PC';
                    webcams.forEach(dev => {
                        const opt = document.createElement('option');
                        opt.value = dev.deviceId;
                        opt.text = `📷 ${dev.label}`;
                        groupCam.appendChild(opt);
                    });
                    this.cameraSelect.appendChild(groupCam);
                }

            } catch (err) {
                console.error("Erreur énumération devices :", err);
            }
        }

        /**
         * Vérifie si le label ou le device correspond à un smartphone USB
         */
        isSmartphoneDevice(device) {
            const label = (device.label || '').toLowerCase();
            const keywords = [
                'android', 'phone', 'smartphone', 'mobile', 'droidcam', 'iriun', 
                'camo', 'epoccam', 'samsung', 'pixel', 'xiaomi', 'redmi', 'huawei', 
                'oppo', 'vivo', 'iphone', 'apple', 'uvc webcam', 'usb webcam'
            ];
            return keywords.some(k => label.includes(k));
        }

        /**
         * Démarre le flux vidéo de la caméra sélectionnée
         */
        async startCameraStream(deviceId) {
            this.stopStream();

            const targetDevice = this.availableDevices.find(d => d.deviceId === deviceId);
            const isPhone = targetDevice ? this.isSmartphoneDevice(targetDevice) : false;

            // Déterminer la résolution idéale
            const resChoice = this.cameraResolutionSelect ? this.cameraResolutionSelect.value : '1080';
            let idealW = 1920;
            let idealH = 1080;
            if (resChoice === '720') {
                idealW = 1280; idealH = 720;
            } else if (resChoice === '480') {
                idealW = 854; idealH = 480;
            }

            this.updateStatus('Connexion...', 'warning');

            const constraints = {
                video: {
                    deviceId: deviceId ? { exact: deviceId } : undefined,
                    width: { ideal: idealW, max: 3840 },
                    height: { ideal: idealH, max: 2160 },
                    frameRate: { ideal: 30 }
                },
                audio: false
            };

            try {
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                this.activeDeviceId = deviceId;
                if (this.webcamVideo) {
                    this.webcamVideo.srcObject = this.stream;
                    this.webcamVideo.onloadedmetadata = () => {
                        const width = this.webcamVideo.videoWidth;
                        const height = this.webcamVideo.videoHeight;
                        if (this.streamResolutionInfo) {
                            this.streamResolutionInfo.textContent = `${width} × ${height} px • 30 FPS`;
                        }
                    };
                }

                // Mise à jour de l'interface selon le type d'appareil
                if (isPhone) {
                    this.updateStatus('Smartphone USB Prêt', 'success');
                    if (this.deviceTypePill) {
                        this.deviceTypePill.innerHTML = '<i class="bi bi-phone-fill text-success me-1"></i>Smartphone USB';
                    }
                    if (this.smartphoneUsbAlert) {
                        this.smartphoneUsbAlert.classList.remove('d-none');
                    }
                } else {
                    this.updateStatus('Caméra active', 'primary');
                    if (this.deviceTypePill) {
                        this.deviceTypePill.innerHTML = '<i class="bi bi-webcam text-primary me-1"></i>Webcam';
                    }
                    if (this.smartphoneUsbAlert) {
                        this.smartphoneUsbAlert.classList.add('d-none');
                    }
                }

            } catch (err) {
                console.warn("Échec avec résolution idéale, repli sur contrainte basique :", err);
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: deviceId ? { deviceId: { exact: deviceId } } : true,
                        audio: false
                    });
                    this.activeDeviceId = deviceId;
                    if (this.webcamVideo) {
                        this.webcamVideo.srcObject = this.stream;
                    }
                    this.updateStatus('Caméra active', 'primary');
                } catch (fallbackErr) {
                    console.error("Échec définitif getUserMedia :", fallbackErr);
                    this.updateStatus('Erreur flux caméra', 'danger');
                }
            }
        }

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            if (this.webcamVideo) {
                this.webcamVideo.srcObject = null;
            }
        }

        /**
         * Déclenche la prise de photo (avec retardateur ou immédiat)
         */
        handleCaptureTrigger() {
            if (this.isCountingDown) return;

            if (this.timerSeconds > 0) {
                this.startCountdown(this.timerSeconds, () => {
                    this.snapPhoto();
                });
            } else {
                this.snapPhoto();
            }
        }

        /**
         * Compte à rebours animé
         */
        startCountdown(seconds, callback) {
            this.isCountingDown = true;
            let current = seconds;

            if (this.cameraCountdownOverlay) {
                this.cameraCountdownOverlay.textContent = current;
                this.cameraCountdownOverlay.classList.remove('d-none');
                this.cameraCountdownOverlay.classList.add('animate-pulse-scale');
            }

            const interval = setInterval(() => {
                current--;
                if (current > 0) {
                    if (this.cameraCountdownOverlay) {
                        this.cameraCountdownOverlay.textContent = current;
                    }
                    this.playBeep(440, 0.08); // Bip court
                } else {
                    clearInterval(interval);
                    if (this.cameraCountdownOverlay) {
                        this.cameraCountdownOverlay.classList.add('d-none');
                        this.cameraCountdownOverlay.classList.remove('animate-pulse-scale');
                    }
                    this.isCountingDown = false;
                    callback();
                }
            }, 1000);

            this.playBeep(440, 0.08);
        }

        /**
         * Prise de vue réelle : capture vidéo -> canvas -> DataURL -> Cropper
         */
        snapPhoto() {
            let dataUrl = null;

            if (this.isStreamMode) {
                // Mode Flux IP / DroidCam
                if (!this.streamVideoImg || !this.streamVideoImg.naturalWidth) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aucun flux disponible',
                        text: 'Veuillez d\'abord connecter un flux smartphone valide.'
                    });
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = this.streamVideoImg.naturalWidth;
                canvas.height = this.streamVideoImg.naturalHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this.streamVideoImg, 0, 0, canvas.width, canvas.height);
                dataUrl = canvas.toDataURL('image/jpeg', 0.95);

            } else {
                // Mode Caméra Directe
                if (!this.webcamVideo || !this.webcamVideo.videoWidth) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Caméra inactive',
                        text: 'Le flux vidéo n\'est pas encore prêt. Veuillez patienter un instant.'
                    });
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = this.webcamVideo.videoWidth;
                canvas.height = this.webcamVideo.videoHeight;
                const ctx = canvas.getContext('2d');

                // Si effet miroir actif
                if (this.isMirrored) {
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                }

                ctx.drawImage(this.webcamVideo, 0, 0, canvas.width, canvas.height);
                dataUrl = canvas.toDataURL('image/jpeg', 0.95);
            }

            // Animation Flash & Son Déclencheur
            this.playShutterEffect();

            // Fermer le studio et ouvrir le recadreur
            setTimeout(() => {
                this.stopStream();
                this.disconnectStreamUrl();
                this.modalWebcam.hide();
                this.openCropper(dataUrl);
            }, 250);
        }

        /**
         * Connecte un flux MJPEG / HTTP Smartphone (IP Webcam / DroidCam USB)
         */
        connectStreamUrl() {
            const url = this.streamUrlInput ? this.streamUrlInput.value.trim() : '';
            if (!url) {
                Swal.fire('URL requise', 'Veuillez saisir l\'adresse du flux vidéo smartphone.', 'warning');
                return;
            }

            if (this.streamPlaceholder) this.streamPlaceholder.classList.add('d-none');
            if (this.streamVideoImg) {
                this.streamVideoImg.classList.remove('d-none');
                this.streamVideoImg.src = url;
                this.streamVideoImg.onerror = () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connexion au flux échouée',
                        html: `Impossible de charger le flux à l'adresse <code>${url}</code>.<br><br>
                               Assurez-vous que l'application (IP Webcam ou DroidCam) est bien démarrée sur le smartphone branché par câble USB.`
                    });
                    this.disconnectStreamUrl();
                };
            }
        }

        disconnectStreamUrl() {
            if (this.streamVideoImg) {
                this.streamVideoImg.src = '';
                this.streamVideoImg.classList.add('d-none');
            }
            if (this.streamPlaceholder) {
                this.streamPlaceholder.classList.remove('d-none');
            }
        }

        /**
         * Ouvre la modale de recadrage Cropper.js
         */
        openCropper(imageSrc) {
            if (!this.imageToCrop || !this.modalCrop) return;

            this.imageToCrop.src = imageSrc;
            this.isFlippedH = false;
            this.modalCrop.show();

            const initCropper = () => {
                if (this.cropper) {
                    this.cropper.destroy();
                }
                this.cropper = new Cropper(this.imageToCrop, {
                    aspectRatio: 3 / 4, // Ratio portrait officiel carte d'identité (600×800)
                    viewMode: 1,
                    autoCropArea: 0.85,
                    responsive: true,
                    background: false,
                    movable: true,
                    zoomable: true,
                    rotatable: true,
                    scalable: true
                });
                this.modalCropEl.removeEventListener('shown.bs.modal', initCropper);
            };

            this.modalCropEl.addEventListener('shown.bs.modal', initCropper);
        }

        /**
         * Applique le recadrage au format 600×800 px et enregistre dans le formulaire
         */
        applyCroppedResult() {
            if (!this.cropper) return;

            const croppedCanvas = this.cropper.getCroppedCanvas({
                width: 600,
                height: 800,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            const croppedDataUrl = croppedCanvas.toDataURL('image/jpeg', 0.88);

            // 1. Mettre à jour l'input caché
            if (this.photoBase64Input) {
                this.photoBase64Input.value = croppedDataUrl;
            }

            // 2. Mettre à jour la prévisualisation
            if (this.previewPhoto) {
                this.previewPhoto.src = croppedDataUrl;
                this.previewPhoto.classList.remove('d-none');
            }
            if (this.placeholderPhoto) {
                this.placeholderPhoto.classList.add('d-none');
            }
            if (this.badgeCropped) {
                this.badgeCropped.classList.remove('d-none');
            }
            if (this.btnResetPhoto) {
                this.btnResetPhoto.classList.remove('d-none');
            }

            this.modalCrop.hide();
            this.showToast('Photo d\'identité validée avec succès (600×800 px) !', 'success');
        }

        /**
         * Réinitialise la photo
         */
        resetPhoto() {
            if (this.photoBase64Input) this.photoBase64Input.value = '';
            if (this.fileInputPhoto) this.fileInputPhoto.value = '';
            if (this.previewPhoto) {
                this.previewPhoto.src = '';
                this.previewPhoto.classList.add('d-none');
            }
            if (this.placeholderPhoto) {
                this.placeholderPhoto.classList.remove('d-none');
            }
            if (this.badgeCropped) {
                this.badgeCropped.classList.add('d-none');
            }
            if (this.btnResetPhoto) {
                this.btnResetPhoto.classList.add('d-none');
            }
        }

        /**
         * Synthèse audio du bruit de déclencheur mécanique de l'appareil photo
         */
        playShutterEffect() {
            // Effet visuel flash
            if (this.cameraFlashOverlay) {
                this.cameraFlashOverlay.classList.add('camera-flash-active');
                setTimeout(() => {
                    this.cameraFlashOverlay.classList.remove('camera-flash-active');
                }, 200);
            }

            // Synthèse audio Web Audio API
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                if (!this.audioCtx) this.audioCtx = new AudioCtx();

                const ctx = this.audioCtx;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }

                const t = ctx.currentTime;
                // Bruit blanc filtré pour simuler le cliquetis du rideau d'obturateur
                const bufferSize = ctx.sampleRate * 0.05;
                const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    data[i] = Math.random() * 2 - 1;
                }

                const noise = ctx.createBufferSource();
                noise.buffer = buffer;

                const filter = ctx.createBiquadFilter();
                filter.type = 'bandpass';
                filter.frequency.setValueAtTime(1200, t);
                filter.Q.setValueAtTime(3, t);

                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0.7, t);
                gain.gain.exponentialRampToValueAtTime(0.01, t + 0.05);

                noise.connect(filter);
                filter.connect(gain);
                gain.connect(ctx.destination);

                noise.start(t);
                noise.stop(t + 0.05);

            } catch (e) {
                // Audio facultatif si non supporté
            }
        }

        playBeep(freq = 440, duration = 0.1) {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                if (!this.audioCtx) this.audioCtx = new AudioCtx();
                const ctx = this.audioCtx;
                if (ctx.state === 'suspended') ctx.resume();

                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);

                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + duration);
            } catch (e) {}
        }

        updateStatus(text, colorType = 'secondary') {
            if (this.cameraStatusText) {
                this.cameraStatusText.textContent = text;
            }
            if (this.cameraStatusBadge) {
                const dot = this.cameraStatusBadge.querySelector('.status-indicator-dot');
                if (dot) {
                    dot.className = `status-indicator-dot bg-${colorType}`;
                }
            }
        }

        showToast(message, icon = 'info') {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true
                });
                Toast.fire({ icon: icon, title: message });
            }
        }
    }

    // Instanciation au chargement du DOM
    document.addEventListener('DOMContentLoaded', () => {
        window.cameraSmartphoneStudio = new CameraSmartphoneStudio();
    });
})();
