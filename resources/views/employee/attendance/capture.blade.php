@extends('layouts.employee')
@section('title', ($type === 'check_in' ? 'Absen Masuk' : 'Absen Pulang').' — Hadirin')
@section('page-title', $type === 'check_in' ? 'Absen Masuk' : 'Absen Pulang')
@section('page-subtitle', 'Verifikasi wajah dan lokasi kerja Anda.')
@section('content-width', 'mx-auto max-w-lg')
@section('content')
@php $blocked = isset($pageError) || !$schedule || $schedule->status !== 'working'; @endphp
<div x-data="attendanceCamera({
    url: '{{ route('employee.attendance.store') }}',
    type: '{{ $type }}',
    latitude: {{ $schedule?->workLocation?->latitude ?? 'null' }},
    longitude: {{ $schedule?->workLocation?->longitude ?? 'null' }},
    radius: {{ $schedule?->workLocation?->radius_meters ?? 100 }},
    maximumAccuracy: {{ $schedule?->workLocation?->maximum_accuracy_meters ?? 100 }},
    blocked: {{ $blocked ? 'true' : 'false' }}
})" x-init="init()" @beforeunload.window="stopCamera()">
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('employee.dashboard') }}" class="grid h-10 w-10 place-items-center rounded-2xl bg-white shadow-sm">←</a>
        <div><p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Verifikasi kehadiran</p><h1 class="text-2xl font-black">{{ $type === 'check_in' ? 'Absen Masuk' : 'Absen Pulang' }}</h1></div>
    </div>

    @if(isset($pageError) || !$schedule)
        <div role="alert" class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $pageError ?? 'Jadwal kerja tidak ditemukan.' }}</div>
    @endif

    <div class="overflow-hidden rounded-[2rem] bg-slate-950 shadow-2xl">
        <div class="relative aspect-[3/4]">
            <video x-ref="video" autoplay playsinline muted class="h-full w-full object-cover -scale-x-100" x-show="!photoUrl"></video>
            <img :src="photoUrl" alt="Foto selfie" class="h-full w-full object-cover" x-show="photoUrl">
            <canvas x-ref="canvas" class="hidden"></canvas>
            <div class="pointer-events-none absolute inset-0 grid place-items-center" x-show="!photoUrl"><div class="h-[66%] w-[73%] rounded-[45%] border-2 border-dashed border-white/80 shadow-[0_0_0_9999px_rgba(2,6,23,.18)]"></div></div>
            <div class="absolute left-4 right-4 top-4 flex justify-between"><span class="rounded-full bg-slate-950/60 px-3 py-2 text-xs font-bold text-white backdrop-blur">Kamera depan</span><span class="rounded-full px-3 py-2 text-xs font-bold backdrop-blur" :class="cameraState === 'ready' ? 'bg-emerald-500/80 text-white' : (cameraState === 'error' ? 'bg-red-500/90 text-white' : 'bg-amber-400/80 text-slate-950')" x-text="cameraState === 'ready' ? 'Siap' : (cameraState === 'error' ? 'Gagal' : 'Menunggu')"></span></div>
            <div class="absolute bottom-5 left-5 right-5 rounded-2xl bg-slate-950/60 p-3 text-center text-xs font-semibold text-white backdrop-blur" x-show="!photoUrl">
                <p x-show="cameraState === 'ready'">Posisikan wajah di tengah bingkai dan pastikan pencahayaan cukup.</p>
                <p x-show="cameraState === 'idle'">Klik tombol di bawah untuk aktifkan kamera.</p>
                <p x-show="cameraState === 'loading'">Menunggu izin kamera...</p>
                <p x-show="cameraState === 'error'">{{ 'Error kamera: ' }}<span x-text="error"></span></p>
            </div>
            <div class="absolute inset-0 grid place-items-center bg-slate-950/80 z-10 px-6" x-show="cameraState !== 'ready' && !photoUrl" x-cloak>
                <div class="max-w-xs rounded-3xl bg-slate-900/95 p-6 text-center shadow-2xl backdrop-blur">
                    <p class="mb-4 text-sm font-semibold text-slate-200" x-text="cameraPermissionMessage"></p>
                    <button type="button" class="btn-primary w-full py-3 text-base font-bold" @click.stop="startCamera()" :disabled="cameraState === 'loading'" x-text="cameraState === 'error' ? 'Muat Ulang Kamera' : (cameraState === 'loading' ? 'Memuat…' : 'Buka Kamera')"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4" x-show="cameraState !== 'ready' && !photoUrl" x-cloak>
        <button type="button" class="btn-primary w-full py-4" @click="startCamera()">Buka Kamera</button>
    </div>

    <div class="glass-card mt-4 rounded-[2rem] p-5">
        <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-50 text-xl">⌖</span><div class="min-w-0 flex-1"><p class="text-sm font-extrabold">{{ $schedule?->workLocation?->name ?? 'Lokasi kerja' }}</p><p class="truncate text-xs text-slate-500">{{ $schedule?->workLocation?->address ?? 'Jadwal belum tersedia' }}</p></div><span class="h-3 w-3 rounded-full" :class="locationState === 'ready' && insideRadius ? 'bg-emerald-500' : 'bg-amber-400'"></span></div>
        <div class="mt-4 grid grid-cols-2 gap-2 text-sm"><div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">Jarak</p><p class="font-black" x-text="distance === null ? 'Menghitung…' : `${distance} m`"></p></div><div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">Akurasi</p><p class="font-black" x-text="accuracy === null ? 'Menunggu…' : `±${accuracy} m`"></p></div></div>
        <div
            class="mt-2 flex items-center justify-between gap-3 rounded-2xl px-4 py-3 text-sm"
            :class="insideRadius ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
            :aria-busy="locationInProgress.toString()"
        >
            <span class="font-bold" aria-live="polite" x-text="locationLabel"></span>
            <button
                type="button"
                class="shrink-0 font-bold underline disabled:cursor-not-allowed disabled:opacity-60"
                @click="getLocation()"
                :disabled="locationInProgress"
                x-text="locationInProgress ? 'Mencari…' : 'Perbarui'"
            ></button>
        </div>
    </div>

    <div x-show="error" x-cloak x-transition role="alert" aria-live="assertive" class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" x-text="error"></div>
    <div x-show="locationWarning" x-cloak x-transition role="alert" aria-live="assertive" class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-700" x-text="locationWarning"></div>
    <button
        type="button"
        x-show="cameraState === 'error'"
        x-cloak
        class="btn-secondary mt-3 w-full"
        @click="startCamera()"
    >
        Coba Buka Kamera Lagi
    </button>
    <div x-show="success" x-cloak x-transition role="status" aria-live="polite" class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700" x-text="success"></div>
    <div class="mt-5 grid grid-cols-2 gap-3" x-show="photoUrl"><button type="button" class="btn-secondary" @click="retake()">Foto Ulang</button><button type="button" class="btn-primary" @click="submit()" :disabled="submitting || !canSubmit"><span x-text="submitting ? 'Mengirim…' : 'Kirim Absensi'"></span></button></div>
    <button type="button" class="btn-primary mt-5 w-full py-4" @click="capture()" x-show="!photoUrl" :disabled="cameraState !== 'ready' || config.blocked"><span class="text-xl">◉</span> Ambil Foto</button>
</div>
@endsection
