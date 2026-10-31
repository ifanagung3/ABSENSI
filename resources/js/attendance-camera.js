export function attendanceCamera(config) {
    return {
        config,
        stream: null,
        photoBlob: null,
        photoUrl: null,
        latitude: null,
        longitude: null,
        accuracy: null,
        distance: null,
        insideRadius: false,
        locationLabel: 'Menunggu lokasi…',
        locationState: 'idle',
        locationInProgress: false,
        locationWatchId: null,
        cameraPermissionState: null,
        cameraState: 'idle',
        submitting: false,
        error: null,
        success: null,

        get canSubmit() {
            return !this.config.blocked
                && this.photoBlob !== null
                && this.locationState === 'ready'
                && this.insideRadius
                && this.accuracy <= this.config.maximumAccuracy;
        },

        get locationWarning() {
            if (this.locationState !== 'ready') return null;
            if (this.accuracy > Number(this.config.maximumAccuracy)) {
                return `Akurasi GPS belum cukup (maks. ±${this.config.maximumAccuracy} m).`;
            }
            if (!this.insideRadius) {
                return `Anda berada di luar radius ${this.config.radius} meter dari titik absensi.`;
            }
            return null;
        },

        async init() {
            if (this.config.blocked) {
                this.cameraState = 'error';
                this.error = 'Kamera tidak dapat dibuka karena absensi tidak tersedia untuk jadwal ini.';
                return;
            }

            this.cameraState = 'idle';
            await this.checkCameraPermission();
            await this.getLocation();
        },

        async startCamera() {
            if (this.config.blocked) {
                this.cameraState = 'error';
                this.error = 'Kamera tidak dapat dibuka karena absensi tidak tersedia untuk jadwal ini.';
                return;
            }

            await this.checkCameraPermission();
            this.error = null;
            this.cameraState = 'loading';

            if (!window.isSecureContext) {
                this.cameraState = 'error';
                this.error = 'Kamera membutuhkan HTTPS. Buka aplikasi melalui alamat HTTPS atau gunakan localhost.';
                return;
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                this.cameraState = 'error';
                this.error = 'Browser tidak mendukung akses kamera.';
                return;
            }

            const constraints = [
                {
                    video: {
                        facingMode: { ideal: 'user' },
                        width: { ideal: 1280 },
                        height: { ideal: 960 },
                    },
                    audio: false,
                },
                {
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 960 },
                    },
                    audio: false,
                },
                { video: true, audio: false },
            ];

            let lastError = null;

            for (const constraint of constraints) {
                try {
                    this.stream = await this.getUserMediaWithTimeout(constraint);
                    const video = this.$refs.video;
                    video.autoplay = true;
                    video.playsInline = true;
                    video.muted = true;
                    video.srcObject = this.stream;
                    try {
                        await video.play();
                    } catch (playError) {
                        console.debug('Video play did not resolve cleanly.', playError);
                    }
                    this.cameraState = 'ready';
                    return;
                } catch (error) {
                    lastError = error;
                    console.error('getUserMedia failed', error);
                    this.stopCameraStream();

                    // Jika permintaan benar-benar menggantung, jangan memicu
                    // permission prompt berkali-kali pada perangkat yang sama.
                    if (error.name === 'TimeoutError') break;
                }
            }

            if (this.cameraState !== 'ready' && lastError?.name === 'NotAllowedError') {
                this.error = 'Izin kamera ditolak. Pastikan browser meminta akses kamera dan izinkan permintaan tersebut.';
            }

            this.cameraState = 'error';
            this.error = this.cameraErrorMessage(lastError);
        },

        async checkCameraPermission() {
            if (!navigator.permissions) {
                this.cameraPermissionState = null;
                return;
            }

            try {
                const status = await navigator.permissions.query({ name: 'camera' });
                this.cameraPermissionState = status.state;
                status.onchange = () => {
                    this.cameraPermissionState = status.state;
                };
            } catch {
                this.cameraPermissionState = null;
            }
        },

        get cameraPermissionMessage() {
            if (this.cameraPermissionState === 'granted') {
                return 'Izin kamera sudah diberikan. Jika kamera masih tidak tampil, periksa aplikasi lain yang sedang menggunakan kamera atau perangkat yang tersedia.';
            }
            if (this.cameraPermissionState === 'denied') {
                return 'Izin kamera ditolak di browser. Reset permissions atau beri izin ulang.';
            }
            if (this.cameraPermissionState === 'prompt') {
                return 'Browser akan meminta izin kamera saat Anda menekan Buka Kamera.';
            }
            return 'Akses kamera diperlukan untuk absensi.';
        },

        getUserMediaWithTimeout(constraints) {
            return new Promise((resolve, reject) => {
                let settled = false;
                const timer = window.setTimeout(() => {
                    settled = true;
                    const error = new Error('Permintaan kamera melewati batas waktu.');
                    error.name = 'TimeoutError';
                    reject(error);
                }, 12000);

                navigator.mediaDevices.getUserMedia(constraints).then((stream) => {
                    if (settled) {
                        stream.getTracks().forEach((track) => track.stop());
                        return;
                    }

                    settled = true;
                    window.clearTimeout(timer);
                    resolve(stream);
                }).catch((error) => {
                    if (settled) return;
                    settled = true;
                    window.clearTimeout(timer);
                    reject(error);
                });
            });
        },

        async getLocation() {
            if (this.locationWatchId !== null || this.locationInProgress) return;

            this.locationInProgress = true;
            this.error = null;
            this.locationState = 'loading';
            this.locationLabel = 'Mencari posisi GPS…';
            this.latitude = null;
            this.longitude = null;
            this.accuracy = null;
            this.distance = null;
            this.insideRadius = false;

            if (!navigator.geolocation) {
                this.locationState = 'error';
                this.error = 'Perangkat tidak mendukung geolokasi.';
                this.locationInProgress = false;
                return;
            }

            this.locationWatchId = navigator.geolocation.watchPosition(
                (position) => {
                    this.applyLocationPosition(position);
                    this.locationInProgress = false;
                },
                (error) => {
                    if (this.locationWatchId !== null) {
                        navigator.geolocation.clearWatch(this.locationWatchId);
                        this.locationWatchId = null;
                    }
                    this.locationState = 'error';
                    this.insideRadius = false;
                    this.error = this.locationErrorMessage(error);
                    this.locationLabel = 'Lokasi gagal diperoleh';
                    this.locationInProgress = false;
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );
        },

        applyLocationPosition(position) {
            this.latitude = position.coords.latitude;
            this.longitude = position.coords.longitude;
            this.accuracy = Math.round(position.coords.accuracy);
            this.distance = Math.round(this.haversine(
                this.latitude, this.longitude,
                Number(this.config.latitude), Number(this.config.longitude)
            ));
            this.insideRadius = this.distance <= Number(this.config.radius);
            this.locationState = 'ready';
            if (this.accuracy > Number(this.config.maximumAccuracy)) {
                this.locationLabel = `Akurasi GPS belum cukup (maks. ±${this.config.maximumAccuracy} m)`;
            } else if (!this.insideRadius) {
                this.locationLabel = `Di luar radius ${this.config.radius} meter`;
            } else {
                this.locationLabel = 'Posisi berada di area absensi';
            }
        },

        capture() {
            if (this.cameraState !== 'ready') return;
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const targetWidth = 720;
            const ratio = video.videoHeight / video.videoWidth || 4 / 3;
            canvas.width = targetWidth;
            canvas.height = Math.round(targetWidth * ratio);
            const context = canvas.getContext('2d');
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    this.error = 'Foto gagal dibuat. Silakan coba lagi.';
                    return;
                }
                this.photoBlob = blob;
                this.photoUrl = URL.createObjectURL(blob);
            }, 'image/jpeg', 0.82);
        },

        retake() {
            if (this.photoUrl) URL.revokeObjectURL(this.photoUrl);
            this.photoBlob = null;
            this.photoUrl = null;
            this.error = null;
        },

        async submit() {
            if (!this.canSubmit) {
                if (this.locationState === 'ready' && !this.insideRadius) {
                    this.error = 'Anda berada di luar radius absensi. Pindah ke area yang ditentukan lalu coba lagi.';
                } else if (this.locationState === 'ready' && this.accuracy > Number(this.config.maximumAccuracy)) {
                    this.error = 'Akurasi GPS belum cukup. Tunggu hingga akurasi membaik.';
                } else {
                    this.error = 'Pastikan foto, lokasi, radius, dan akurasi GPS sudah valid.';
                }
                return;
            }
            this.submitting = true;
            this.error = null;
            const form = new FormData();
            form.append('event_type', this.config.type);
            form.append('photo', this.photoBlob, `${this.config.type}.jpg`);
            form.append('latitude', String(this.latitude));
            form.append('longitude', String(this.longitude));
            form.append('location_accuracy', String(this.accuracy));
            form.append('device_time', new Date().toISOString());
            form.append('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone);
            form.append('device_id', this.deviceId());
            try {
                const response = await window.axios.post(this.config.url, form);
                this.success = response.data.message;
                this.stopCamera();
                window.setTimeout(() => { window.location.href = response.data.redirect; }, 700);
            } catch (error) {
                const errors = error.response?.data?.errors;
                this.error = errors ? Object.values(errors).flat().join(' ') : (error.response?.data?.message || 'Absensi gagal dikirim.');
            } finally {
                this.submitting = false;
            }
        },

        haversine(latA, lonA, latB, lonB) {
            if (![latA, lonA, latB, lonB].every(Number.isFinite)) return Number.POSITIVE_INFINITY;
            const toRad = value => value * Math.PI / 180;
            const dLat = toRad(latB - latA);
            const dLon = toRad(lonB - lonA);
            const raw = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(latA)) * Math.cos(toRad(latB)) * Math.sin(dLon / 2) ** 2;
            const a = Math.min(1, Math.max(0, raw));
            return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        deviceId() {
            try {
                let id = localStorage.getItem('attendance_device_id');
                if (!id) {
                    id = globalThis.crypto?.randomUUID?.() || `device-${Date.now()}-${Math.random().toString(16).slice(2)}`;
                    localStorage.setItem('attendance_device_id', id);
                }
                return id;
            } catch {
                return globalThis.crypto?.randomUUID?.() || `device-${Date.now()}`;
            }
        },

        stopCameraStream() {
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            if (this.$refs?.video) this.$refs.video.srcObject = null;
        },

        stopLocationWatch() {
            if (this.locationWatchId !== null) {
                navigator.geolocation.clearWatch(this.locationWatchId);
                this.locationWatchId = null;
            }
        },

        stopCamera() {
            this.stopCameraStream();
        },

        cameraErrorMessage(error) {
            if (!error) return 'Kamera tidak dapat dibuka. Coba lagi.';
            if (error.name === 'NotAllowedError') return 'Izin kamera ditolak. Aktifkan izin kamera pada browser.';
            if (error.name === 'NotFoundError') return 'Perangkat kamera tidak ditemukan.';
            if (error.name === 'NotReadableError') return 'Kamera sedang digunakan aplikasi lain.';
            if (error.name === 'SecurityError') return 'Akses kamera diblokir oleh keamanan browser. Pastikan halaman menggunakan HTTPS.';
            if (error.name === 'TimeoutError') return 'Kamera tidak merespons. Periksa izin kamera lalu coba lagi.';
            return error.message || 'Kamera tidak dapat dibuka. Coba lagi.';
        },

        locationErrorMessage(error) {
            if (error.code === 1) return 'Izin lokasi ditolak. Aktifkan GPS dan izin lokasi browser.';
            if (error.code === 2) return 'Lokasi tidak tersedia. Pindah ke area terbuka lalu coba lagi.';
            if (error.code === 3) return 'Pengambilan lokasi terlalu lama. Coba ulang.';
            return 'Lokasi gagal diperoleh.';
        },
    };
}
