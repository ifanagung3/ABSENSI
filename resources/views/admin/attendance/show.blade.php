@extends('layouts.admin')

@section('title', 'Detail Absensi — Hadirin')
@section('page-title', 'Detail Absensi')
@section('page-subtitle', 'Periksa bukti, lokasi, dan hasil validasi absensi.')

@section('content')
@php
    $reviewable = $attendanceRecord->status === 'pending_review'
        && $attendanceRecord->events->contains('validation_status', 'review');
@endphp

<a href="{{ route('admin.attendance.index') }}" class="text-sm font-bold text-brand-600">← Kembali</a>

<div class="mt-5 grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
    <section class="panel p-6">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Pegawai</p>
        <h2 class="mt-2 text-2xl font-black">{{ $attendanceRecord->employee->user->name }}</h2>
        <p class="text-slate-500">{{ $attendanceRecord->employee->employee_number }} · {{ $attendanceRecord->employee->department?->name }}</p>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-slate-50 p-4">
                <small>Masuk</small>
                <b class="mt-1 block text-xl">{{ $attendanceRecord->checked_in_at?->format('H:i:s') ?? '--' }}</b>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <small>Pulang</small>
                <b class="mt-1 block text-xl">{{ $attendanceRecord->checked_out_at?->format('H:i:s') ?? '--' }}</b>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 p-4 text-sm">
            <p><b>Status:</b> {{ ucfirst(str_replace('_', ' ', $attendanceRecord->status)) }}</p>
            <p class="mt-2"><b>Terlambat:</b> {{ $attendanceRecord->late_minutes }} menit</p>
            <p class="mt-2"><b>Pulang cepat:</b> {{ $attendanceRecord->early_leave_minutes }} menit</p>
            <p class="mt-2"><b>Durasi:</b> {{ intdiv($attendanceRecord->work_duration_minutes, 60) }} jam {{ $attendanceRecord->work_duration_minutes % 60 }} menit</p>
            @if($attendanceRecord->notes)
                <p class="mt-2"><b>Catatan:</b> {{ $attendanceRecord->notes }}</p>
            @endif
        </div>

        @if($reviewable)
            <form
                method="POST"
                action="{{ route('admin.attendance.review', $attendanceRecord) }}"
                class="mt-5 space-y-3 rounded-2xl bg-slate-50 p-4"
                x-data="{ reviewStatus: @js(old('status', $attendanceRecord->status)) }"
            >
                @csrf
                @method('PATCH')

                <div>
                    <label class="label" for="attendance-review-status">Hasil pemeriksaan</label>
                    <select class="input" id="attendance-review-status" name="status" x-model="reviewStatus" required>
                        @foreach([
                            'present' => 'Valid / Hadir',
                            'late' => 'Valid / Terlambat',
                            'pending_review' => 'Tetap Ditinjau',
                            'invalid' => 'Tidak Valid',
                        ] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="reviewStatus === 'late'" x-cloak>
                    <label class="label" for="attendance-late-minutes">Jumlah menit terlambat</label>
                    <input
                        class="input"
                        id="attendance-late-minutes"
                        type="number"
                        name="late_minutes"
                        value="{{ old('late_minutes', $attendanceRecord->late_minutes) }}"
                        min="0"
                        max="1440"
                        :disabled="reviewStatus !== 'late'"
                        :required="reviewStatus === 'late'"
                    >
                </div>

                <div>
                    <label class="label" for="attendance-review-notes">Catatan pemeriksaan</label>
                    <textarea class="input" id="attendance-review-notes" name="notes" rows="4" maxlength="2000">{{ old('notes', $attendanceRecord->notes) }}</textarea>
                </div>

                <button class="btn-primary w-full" type="submit">Simpan Pemeriksaan</button>
            </form>
        @else
            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Status ini berasal dari jadwal ketidakhadiran atau belum memiliki bukti absensi, sehingga tidak dapat diperiksa melalui formulir ini.
            </div>
        @endif
    </section>

    <section class="space-y-4" aria-label="Bukti event absensi">
        @forelse($attendanceRecord->events as $event)
            <article class="panel p-6">
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase text-brand-600">{{ $event->event_type === 'check_in' ? 'Absen Masuk' : 'Absen Pulang' }}</p>
                        <h3 class="mt-1 text-xl font-black">{{ $event->server_time->format('d M Y, H:i:s') }}</h3>
                    </div>
                    <span class="{{ $event->validation_status === 'valid' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($event->validation_status) }}</span>
                </div>

                @if($event->rejection_reason)
                    <p class="mt-3 rounded-2xl bg-amber-50 p-3 text-sm text-amber-800">{{ $event->rejection_reason }}</p>
                @endif

                <div class="mt-5 grid gap-4 md:grid-cols-[190px_1fr]">
                    <img class="h-64 w-full rounded-2xl object-cover" src="{{ route('admin.attendance.photo', $event) }}" alt="Bukti selfie {{ $event->event_type === 'check_in' ? 'absen masuk' : 'absen pulang' }}">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-slate-50 p-4"><small>Jarak</small><b class="block">{{ number_format($event->distance_meters, 1) }} m</b></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><small>Akurasi GPS</small><b class="block">±{{ number_format($event->location_accuracy, 0) }} m</b></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><small>Latitude</small><b class="block break-all">{{ $event->latitude }}</b></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><small>Longitude</small><b class="block break-all">{{ $event->longitude }}</b></div>
                        <div class="col-span-2 rounded-2xl bg-slate-50 p-4"><small>Jam perangkat</small><b class="block">{{ $event->device_time?->setTimezone(config('app.timezone'))->format('d-m-Y H:i:s') ?? '-' }}</b></div>
                        <div class="col-span-2 rounded-2xl bg-slate-50 p-4"><small>IP / perangkat</small><b class="block break-all">{{ $event->ip_address }} · {{ \Illuminate\Support\Str::limit($event->user_agent, 100) }}</b></div>
                        <a class="col-span-2 text-sm font-bold text-brand-600" href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=19/{{ $event->latitude }}/{{ $event->longitude }}" target="_blank" rel="noopener noreferrer">Buka titik di peta ↗</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="panel p-10 text-center text-slate-500">Belum ada event absensi.</div>
        @endforelse
    </section>
</div>
@endsection
