@extends('layouts.admin')
@section('title', 'Lokasi Kerja — Hadirin')
@section('page-title', 'Lokasi & Geofence')
@section('page-subtitle', 'Kelola titik kerja, radius, dan akurasi GPS.')
@section('content')
@php
    $createLocationErrors = $errors->createLocation;
    $updateLocationErrors = $errors->updateLocation;
    $editingLocationId = session('editing_location_id');
@endphp
<div
    x-data="{
        createOpen: {{ $createLocationErrors->any() ? 'true' : 'false' }}
    }"
    @keydown.escape.window="createOpen = false"
>
    <div
        class="flex flex-col gap-3 sm:flex-row
               sm:items-center sm:justify-between"
    >
        <p class="text-sm text-slate-500">
            Backend menghitung ulang jarak dari koordinat perangkat.
        </p>

        <button
            type="button"
            class="btn-primary w-full sm:w-auto"
            @click="createOpen = true"
        >
            + Tambah Lokasi
        </button>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        @forelse($locations as $item)
            <article
                class="panel min-w-0 p-5"
                x-data="{
                    edit: {{ $updateLocationErrors->any()
                        && (string) $editingLocationId === (string) $item->getKey()
                            ? 'true'
                            : 'false' }}
                }"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="break-words text-xl font-black">
                                {{ $item->name }}
                            </h2>

                            <span
                                class="{{ $item->is_active
                                    ? 'badge-success'
                                    : 'badge-danger' }}"
                            >
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <p class="mt-2 break-words text-sm leading-6 text-slate-500">
                            {{ $item->address }}
                        </p>
                    </div>

                    <span
                        class="grid h-11 w-11 shrink-0 place-items-center
                               rounded-2xl bg-brand-50 text-xl"
                    >
                        ⌖
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-center sm:gap-3">
                    <div class="min-w-0 rounded-2xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Radius</p>
                        <p class="truncate font-black">
                            {{ $item->radius_meters }} m
                        </p>
                    </div>

                    <div class="min-w-0 rounded-2xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Akurasi</p>
                        <p class="truncate font-black">
                            ±{{ $item->maximum_accuracy_meters }} m
                        </p>
                    </div>

                    <div class="min-w-0 rounded-2xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Jadwal</p>
                        <p class="truncate font-black">
                            {{ $item->schedules_count }}
                        </p>
                    </div>
                </div>

                <p
                    class="mt-3 break-all rounded-2xl border
                           border-slate-200 p-3 font-mono text-xs
                           text-slate-600"
                >
                    {{ $item->latitude }}, {{ $item->longitude }}
                </p>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <button
                        type="button"
                        class="btn-secondary w-full sm:flex-1"
                        @click="edit = true"
                    >
                        Edit Lokasi
                    </button>

                    <form
                        method="POST"
                        action="{{ route('admin.locations.toggle', $item) }}"
                        class="w-full sm:w-auto"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            class="btn-secondary w-full"
                            onclick="return confirm('Ubah status lokasi?')"
                        >
                            {{ $item->is_active
                                ? 'Nonaktifkan'
                                : 'Aktifkan' }}
                        </button>
                    </form>
                </div>

                <template x-teleport="body">
                    <div
                        x-show="edit"
                        x-cloak
                        x-transition.opacity.duration.200ms
                        class="modal-backdrop location-modal-backdrop"
                        @click.self="edit = false"
                        @keydown.escape.window="edit = false"
                    >
                        <form
                            method="POST"
                            action="{{ route('admin.locations.update', $item) }}"
                            class="location-modal-card"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-location-title-{{ $item->getKey() }}"
                            tabindex="-1"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="location-modal-header">
                                <div class="min-w-0">
                                    <p
                                        class="text-xs font-bold uppercase
                                               tracking-wider text-brand-600"
                                    >
                                        Lokasi kerja
                                    </p>

                                    <h2
                                        id="edit-location-title-{{ $item->getKey() }}"
                                        class="truncate text-xl font-black sm:text-2xl"
                                    >
                                        Edit {{ $item->name }}
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    data-modal-close
                                    class="location-modal-close"
                                    aria-label="Tutup formulir edit lokasi"
                                    @click="edit = false"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="location-modal-body">
                                @if($updateLocationErrors->any() && (string) $editingLocationId === (string) $item->getKey())
                                    <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                        <p class="font-bold">Lokasi belum dapat diperbarui:</p>
                                        <ul class="mt-2 list-disc space-y-1 pl-5">
                                            @foreach($updateLocationErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif

                                @include('admin.locations.partials.form', [
                                    'location' => $item,
                                ])
                            </div>

                            <div class="location-modal-footer">
                                <button class="btn-primary w-full">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </article>
        @empty
            <div class="panel p-10 text-center text-slate-500">
                Belum ada lokasi kerja.
            </div>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $locations->links() }}
    </div>

    <template x-teleport="body">
        <div
            x-show="createOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="modal-backdrop location-modal-backdrop"
            @click.self="createOpen = false"
            @keydown.escape.window="createOpen = false"
        >
            <form
                method="POST"
                action="{{ route('admin.locations.store') }}"
                class="location-modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-location-title"
                tabindex="-1"
            >
                @csrf

                <div class="location-modal-header">
                    <div>
                        <p
                            class="text-xs font-bold uppercase
                                   tracking-wider text-brand-600"
                        >
                            Lokasi kerja
                        </p>

                        <h2
                            id="create-location-title"
                            class="text-xl font-black sm:text-2xl"
                        >
                            Tambah Lokasi
                        </h2>
                    </div>

                    <button
                        type="button"
                        data-modal-close
                        class="location-modal-close"
                        aria-label="Tutup formulir tambah lokasi"
                        @click="createOpen = false"
                    >
                        ×
                    </button>
                </div>

                <div class="location-modal-body">
                    @if($createLocationErrors->any())
                        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-bold">Lokasi belum dapat disimpan:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach($createLocationErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    @include('admin.locations.partials.form', [
                        'location' => null,
                    ])
                </div>

                <div class="location-modal-footer">
                    <button class="btn-primary w-full">
                        Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </template>
</div>
<script>
function initializeGPS() {
    document
        .querySelectorAll('[data-get-device-location]')
        .forEach(function (button) {
            if (button.dataset.gpsInitialized === 'true') {
                return;
            }

            const form = button.closest('form');

            if (!form) {
                return;
            }

            const buttonText = button.querySelector(
                '[data-location-button-text]'
            );
            const statusBox = form.querySelector(
                '[data-location-status]'
            );
            const latitudeInput = form.querySelector(
                '[name="latitude"]'
            );
            const longitudeInput = form.querySelector(
                '[name="longitude"]'
            );
            const accuracyInput = form.querySelector(
                '[name="detected_accuracy"]'
            );
            const accuracyInformation = form.querySelector(
                '[data-accuracy-information]'
            );
            const accuracyValue = form.querySelector(
                '[data-accuracy-value]'
            );
            const maximumAccuracyInput = form.querySelector(
                '[name="maximum_accuracy_meters"]'
            );

            if (
                !buttonText ||
                !statusBox ||
                !latitudeInput ||
                !longitudeInput ||
                !accuracyInput ||
                !accuracyInformation ||
                !accuracyValue ||
                !maximumAccuracyInput
            ) {
                return;
            }

            button.dataset.gpsInitialized = 'true';

            function showStatus(type, message) {
                statusBox.classList.remove(
                    'hidden',
                    'border-blue-200',
                    'bg-blue-50',
                    'text-blue-700',
                    'border-emerald-200',
                    'bg-emerald-50',
                    'text-emerald-700',
                    'border-amber-200',
                    'bg-amber-50',
                    'text-amber-700',
                    'border-red-200',
                    'bg-red-50',
                    'text-red-700'
                );

                const styles = {
                    loading: [
                        'border-blue-200',
                        'bg-blue-50',
                        'text-blue-700'
                    ],
                    success: [
                        'border-emerald-200',
                        'bg-emerald-50',
                        'text-emerald-700'
                    ],
                    warning: [
                        'border-amber-200',
                        'bg-amber-50',
                        'text-amber-700'
                    ],
                    error: [
                        'border-red-200',
                        'bg-red-50',
                        'text-red-700'
                    ],
                    info: [
                        'border-blue-200',
                        'bg-blue-50',
                        'text-blue-700'
                    ]
                };

                statusBox.classList.add(...styles[type]);
                statusBox.textContent = message;
            }

            function setLoading(isLoading) {
                button.disabled = isLoading;
                buttonText.textContent = isLoading
                    ? 'Mencari lokasi terbaik...'
                    : 'Gunakan Lokasi Perangkat';
            }

            function savePosition(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = Math.round(
                    position.coords.accuracy
                );

                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);
                accuracyInput.value = accuracy;

                accuracyValue.textContent = `±${accuracy} meter`;
                accuracyInformation.classList.remove('hidden');

                const maximumAccuracy = Number(
                    maximumAccuracyInput.value || 100
                );

                if (accuracy <= maximumAccuracy) {
                    showStatus(
                        'success',
                        `Lokasi berhasil diperoleh dengan akurasi ±${accuracy} meter.`
                    );
                } else {
                    showStatus(
                        'warning',
                        `Lokasi diperoleh, tetapi ketidakpastian perangkat masih ±${accuracy} meter. ` +
                        `Nilai ini lebih besar dari batas akurasi absensi ±${maximumAccuracy} meter. ` +
                        `Laptop atau Wi-Fi sering kurang presisi. Coba ponsel dengan Lokasi Presisi aktif ` +
                        `di area terbuka, atau isi koordinat manual dari peta.`
                    );
                }
            }

            function getErrorMessage(error) {
                switch (error.code) {
                    case 1:
                        return 'Izin lokasi ditolak. Izinkan akses lokasi pada pengaturan browser.';

                    case 2:
                        return 'Lokasi tidak tersedia. Aktifkan GPS atau layanan lokasi perangkat.';

                    case 3:
                        return 'Pencarian lokasi terlalu lama. Pastikan GPS aktif lalu coba kembali.';

                    default:
                        return 'Lokasi gagal diperoleh. Silakan coba kembali.';
                }
            }

            function markCoordinatesAsManual() {
                accuracyInput.value = '';
                accuracyValue.textContent = '-';
                accuracyInformation.classList.add('hidden');

                showStatus(
                    'info',
                    'Koordinat sedang diisi manual. Pastikan titik disalin dari peta dan sudah diverifikasi.'
                );
            }

            latitudeInput.addEventListener(
                'input',
                markCoordinatesAsManual
            );
            longitudeInput.addEventListener(
                'input',
                markCoordinatesAsManual
            );

            button.addEventListener('click', function () {
                if (!window.isSecureContext) {
                    showStatus(
                        'error',
                        'Lokasi perangkat membutuhkan HTTPS. Aktifkan SSL pada virtual host Laragon, lalu buka ulang aplikasi melalui alamat HTTPS.'
                    );

                    return;
                }

                if (!navigator.geolocation) {
                    showStatus(
                        'error',
                        'Browser atau perangkat ini tidak mendukung fitur lokasi.'
                    );

                    return;
                }

                setLoading(true);

                accuracyInput.value = '';
                accuracyValue.textContent = '-';
                accuracyInformation.classList.add('hidden');

                showStatus(
                    'loading',
                    'Sedang mencari sinyal lokasi terbaik hingga 30 detik. Pastikan GPS dan Lokasi Presisi aktif.'
                );

                let bestPosition = null;
                let watchId = null;
                let timeoutId = null;
                let finished = false;
                const searchTimeout = 30000;
                const configuredAccuracy = Number(
                    maximumAccuracyInput.value || 100
                );
                const targetAccuracy = Number.isFinite(
                    configuredAccuracy
                )
                    ? Math.min(configuredAccuracy, 20)
                    : 20;

                const stopWatching = function () {
                    if (timeoutId !== null) {
                        window.clearTimeout(timeoutId);
                        timeoutId = null;
                    }

                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                };

                const finish = function () {
                    if (finished) {
                        return;
                    }

                    finished = true;
                    stopWatching();
                    setLoading(false);

                    if (bestPosition) {
                        savePosition(bestPosition);
                    } else {
                        showStatus(
                            'error',
                            'Lokasi belum berhasil diperoleh. Silakan coba kembali.'
                        );
                    }
                };

                const fail = function (error) {
                    if (finished) {
                        return;
                    }

                    if (bestPosition) {
                        finish();
                        return;
                    }

                    finished = true;
                    stopWatching();
                    setLoading(false);

                    showStatus(
                        'error',
                        getErrorMessage(error)
                    );
                };

                timeoutId = window.setTimeout(
                    finish,
                    searchTimeout
                );

                try {
                    watchId = navigator.geolocation.watchPosition(
                        function (position) {
                            if (finished) {
                                return;
                            }

                            if (
                                !bestPosition ||
                                position.coords.accuracy <
                                bestPosition.coords.accuracy
                            ) {
                                bestPosition = position;

                                showStatus(
                                    'loading',
                                    `Lokasi ditemukan. Akurasi sementara ±${Math.round(
                                        position.coords.accuracy
                                    )} meter.`
                                );
                            }

                            if (
                                position.coords.accuracy <=
                                targetAccuracy
                            ) {
                                finish();
                            }
                        },
                        fail,
                        {
                            enableHighAccuracy: true,
                            timeout: searchTimeout,
                            maximumAge: 0
                        }
                    );

                    if (finished && watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                    }
                } catch (error) {
                    fail(error);
                }
            });
        });
}

// Initialize when DOM is ready or if already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGPS);
} else {
    initializeGPS();
}
</script>
@endsection
