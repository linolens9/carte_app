/**
 * =========================================================================
 * MBOLO - Studio de Capture Photo Webcam & Recadrage Officiel (3:4)
 * Gère l'acquisition caméra directe HD, le compte à rebours, l'effet miroir,
 * l'obturateur audio/visuel et le recadrage Cropper.js (600×800 px).
 * =========================================================================
 */
(function () {
    'use strict';

    class WebcamCaptureStudio {
        constructor() {
            // Éléments du formulaire hôte
            this.photoBase64Input = document.getElementById('photoBase64Input');
            this.fileInputPhoto = document.getElementById('fileInputPhoto');
            this.previewPhoto = document.getElementById('previewPhoto') || document.getElementById('photoPreviewImg');
            this.placeholderPhoto = document.getElementById('placeholderPhoto') || document.getElementById('photoPlaceholder');
            this.badgeCropped = document.getElementById('badgeCropped');
            this.btnResetPhoto = document.getElementById('btnResetPhoto');

            // Bouton d'ouverture Webcam
            this.btnOpenWebcam = document.getElementById('btnOpenWebcam');

            // Modales Bootstrap
            this.modalWebcamEl = document.getElementById('modalWebcam');
            this.modalCropEl = document.getElementById('modalCrop');
            this.modalWebcam = this.modalWebcamEl ? new bootstrap.Modal(this.modalWebcamEl) : null;
            this.modalCrop = this.modalCropEl ? new bootstrap.Modal(this.modalCropEl) : null;

            // Éléments Studio Webcam
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
            this.cameraFlashOverlay = document.getElementById('cameraFlashOverlay');
            this.cameraCountdownOverlay = document.getElementById('cameraCountdownOverlay');

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
            // 1. Bouton "Prendre via Webcam"
            if (this.btnOpenWebcam) {
                this.btnOpenWebcam.addEventListener('click', () => {
                    this.openStudio();
                });
            }

            // 2. Fermeture de la modale caméra -> arrêt du flux
            this.modalWebcamEl.addEventListener('hidden.bs.modal', () => {
                this.stopStream();
            });

            // 3. Changement de caméra
            if (this.cameraSelect) {
                this.cameraSelect.addEventListener('change', () => {
                    this.startCameraStream(this.cameraSelect.value);
                });
            }

            // 4. Changement de résolution demandée
            if (this.cameraResolutionSelect) {
                this.cameraResolutionSelect.addEventListener('change', () => {
                    if (this.activeDeviceId) {
                        this.startCameraStream(this.activeDeviceId);
                    }
                });
            }

            // 5. Rafraîchir les webcams branchées
            if (this.btnRefreshCameras) {
                this.btnRefreshCameras.addEventListener('click', async () => {
                    this.btnRefreshCameras.classList.add('rotating-animation');
                    await this.enumerateVideoDevices(true);
                    setTimeout(() => {
                        this.btnRefreshCameras.classList.remove('rotating-animation');
                    }, 600);
                });
            }

            // 6. Basculer l'effet miroir
            if (this.btnToggleMirror) {
                this.btnToggleMirror.addEventListener('click', () => {
                    this.isMirrored = !this.isMirrored;
                    if (this.webcamVideo) {
                        this.webcamVideo.style.transform = this.isMirrored ? 'scaleX(-1)' : 'scaleX(1)';
                    }
                    this.btnToggleMirror.classList.toggle('active', this.isMirrored);
                });
            }

            // 7. Gestion du compte à rebours / retardateur
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

            // 8. Déclencheur prise de photo
            if (this.btnCapturePhoto) {
                this.btnCapturePhoto.addEventListener('click', () => {
                    this.handleCaptureTrigger();
                });
            }

            // 9. Téléversement classique de fichier image
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

            // 10. Réinitialiser la photo
            if (this.btnResetPhoto) {
                this.btnResetPhoto.addEventListener('click', () => {
                    this.resetPhoto();
                });
            }

            // 11. Outils de recadrage Cropper
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

            // 12. Validation du recadrage
            if (this.btnValidateCrop) {
                this.btnValidateCrop.addEventListener('click', () => {
                    this.applyCroppedResult();
                });
            }
        }

        /**
         * Écoute en temps réel les branchements / débranchements de webcam
         */
        listenDeviceChanges() {
            if (navigator.mediaDevices && navigator.mediaDevices.addEventListener) {
                navigator.mediaDevices.addEventListener('devicechange', async () => {
                    if (this.modalWebcamEl.classList.contains('show')) {
                        await this.enumerateVideoDevices(false);
                    }
                });
            }
        }

        /**
         * Ouvre le Studio de Capture Webcam
         */
        async openStudio() {
            try {
                this.modalWebcam.show();
                this.updateStatus('Recherche des webcams...', 'warning');

                // Énumération des caméras disponibles
                await this.enumerateVideoDevices(true);

                if (this.availableDevices.length === 0) {
                    this.updateStatus('Aucune webcam détectée', 'danger');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aucune webcam détectée',
                        text: 'Le navigateur n\'a détecté aucune webcam connectée à cet ordinateur.'
                    });
                    return;
                }

                const selectedDeviceId = this.availableDevices[0].deviceId;
                if (this.cameraSelect) {
                    this.cameraSelect.value = selectedDeviceId;
                }

                await this.startCameraStream(selectedDeviceId);

            } catch (err) {
                console.error("Erreur ouverture webcam :", err);
                this.updateStatus('Erreur accès webcam', 'danger');
                Swal.fire({
                    icon: 'error',
                    title: 'Accès webcam refusé',
                    text: 'Veuillez autoriser l\'accès à la webcam dans les autorisations de votre navigateur.'
                });
            }
        }

        /**
         * Énumère les périphériques vidéo
         */
        async enumerateVideoDevices(requestPermissionFirst = false) {
            try {
                if (requestPermissionFirst && (!this.stream || !this.stream.active)) {
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
                    opt.text = 'Aucune webcam trouvée';
                    this.cameraSelect.appendChild(opt);
                    return;
                }

                this.availableDevices.forEach((dev, index) => {
                    const opt = document.createElement('option');
                    opt.value = dev.deviceId;
                    opt.text = `📷 ${dev.label || 'Webcam ' + (index + 1)}`;
                    this.cameraSelect.appendChild(opt);
                });

            } catch (err) {
                console.error("Erreur énumération webcams :", err);
            }
        }

        /**
         * Démarre le flux vidéo de la caméra sélectionnée
         */
        async startCameraStream(deviceId) {
            this.stopStream();

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

                this.updateStatus('Webcam active', 'success');
                if (this.deviceTypePill) {
                    this.deviceTypePill.innerHTML = '<i class="bi bi-webcam text-success me-1"></i>Webcam';
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
                    this.updateStatus('Webcam active', 'success');
                } catch (fallbackErr) {
                    console.error("Échec définitif getUserMedia :", fallbackErr);
                    this.updateStatus('Erreur flux webcam', 'danger');
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
                    this.playBeep(440, 0.08);
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
         * Prise de vue : capture vidéo -> canvas -> DataURL -> Cropper
         */
        snapPhoto() {
            if (!this.webcamVideo || !this.webcamVideo.videoWidth) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Webcam inactive',
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
            const dataUrl = canvas.toDataURL('image/jpeg', 0.95);

            // Animation Flash & Son Déclencheur
            this.playShutterEffect();

            // Fermer le studio webcam et ouvrir le recadreur
            setTimeout(() => {
                this.stopStream();
                this.modalWebcam.hide();
                this.openCropper(dataUrl);
            }, 250);
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
         * Synthèse audio du bruit de déclencheur mécanique
         */
        playShutterEffect() {
            if (this.cameraFlashOverlay) {
                this.cameraFlashOverlay.classList.add('camera-flash-active');
                setTimeout(() => {
                    this.cameraFlashOverlay.classList.remove('camera-flash-active');
                }, 200);
            }

            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                if (!this.audioCtx) this.audioCtx = new AudioCtx();

                const ctx = this.audioCtx;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }

                const t = ctx.currentTime;
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

            } catch (e) {}
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
        window.webcamCaptureStudio = new WebcamCaptureStudio();
    });
})();
