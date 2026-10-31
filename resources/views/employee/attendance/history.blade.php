@extends('layouts.employee')

@section('title', 'Riwayat — Hadirin')
@section('page-title', 'Riwayat Absensi')
@section('page-subtitle', 'Lihat catatan dan ringkasan kehadiran Anda.')

@section('content')
<div>
    <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Kehadiran</p>
    <h1 class="mt-1 text-3xl font-black">Riwayat Absensi</h1>
</div>

<form class="glass-card mt-5 rounded-3xl p-4">
    <label class="label" for="attendance-history-month">Pilih bulan</label>
    <div class="flex gap-2">
        <input class="input" id="attendance-history-month" type="month" name="month" value="{{ $month }}">
        <button class="btn-primary px-4" type="submit">Terapkan</button>
    </div>
</form>

<div class="mt-4 grid grid-cols-3 gap-3 text-center">
    <div class="stat-card p-4"><p class="text-xl font-black">{{ (int)($summary->present ?? 0) }}</p><p class="text-[10px] text-slate-500">Hadir</p></div>
    <div class="stat-card p-4"><p class="text-xl font-black">{{ (int)($summary->late ?? 0) }}</p><p class="text-[10px] text-slate-500">Terlambat</p></div>
    <div class="stat-card p-4"><p class="text-xl font-black">{{ (int)($summary->late_minutes ?? 0) }}</p><p class="text-[10px] text-slate-500">Menit telat</p></div>
</div>

<div class="mt-5 space-y-3">
    @forelse($records as $record)
        @php
            $statusPresentation = match($record->status) {
                'present' => ['Hadir', 'badge-success'],
                'late' => ['Terlambat '.$record->late_minutes.' mnt', 'badge-warning'],
                'absent' => ['Alpa', 'badge-danger'],
                'invalid' => ['Tidak valid', 'badge-danger'],
                'permission' => ['Izin', 'badge-info'],
                'sick' => ['Sakit', 'badge-warning'],
                'leave' => ['Cuti', 'badge-info'],
                'pending_review' => ['Perlu ditinjau', 'badge-warning'],
                default => [ucfirst(str_replace('_', ' ', $record->status)), 'badge-neutral'],
            };
        @endphp
        <article class="glass-card rounded-3xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.15em] text-slate-400">{{ $record->attendance_date->translatedFormat('l') }}</p>
                    <h2 class="mt-1 font-extrabold">{{ $record->attendance_date->translatedFormat('d F Y') }}</h2>
                </div>
                <span class="{{ $statusPresentation[1] }}">{{ $statusPresentation[0] }}</span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3 rounded-2xl bg-slate-50 p-4 text-center">
                <div><p class="text-xs text-slate-500">Masuk</p><p class="mt-1 font-black">{{ $record->checked_in_at?->format('H:i') ?? '--:--' }}</p></div>
                <div><p class="text-xs text-slate-500">Pulang</p><p class="mt-1 font-black">{{ $record->checked_out_at?->format('H:i') ?? '--:--' }}</p></div>
                <div><p class="text-xs text-slate-500">Durasi</p><p class="mt-1 font-black">{{ intdiv($record->work_duration_minutes, 60) }}j {{ $record->work_duration_minutes % 60 }}m</p></div>
            </div>

            <p class="mt-3 truncate text-xs text-slate-500">⌖ {{ $record->schedule?->workLocation?->name ?? 'Lokasi tidak tersedia' }}</p>
            @if($record->notes)
                <p class="mt-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">{{ $record->notes }}</p>
            @endif
        </article>
    @empty
        <div class="glass-card rounded-3xl p-8 text-center text-sm text-slate-500">Belum ada data absensi pada bulan ini.</div>
    @endforelse
</div>

<div class="mt-5">{{ $records->links() }}</div>
@endsection
