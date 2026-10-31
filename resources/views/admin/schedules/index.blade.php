@extends('layouts.admin')

@section('title', 'Jadwal — Hadirin')
@section('page-title', 'Jadwal Pegawai')
@section('page-subtitle', 'Atur shift dan lokasi kerja harian pegawai.')

@section('content')
@php
    $singleScheduleErrors = $errors->singleSchedule;
    $bulkScheduleErrors = $errors->bulkSchedule;
@endphp

<div
    x-data="{
        single: {{ $singleScheduleErrors->any() ? 'true' : 'false' }},
        bulk: {{ $bulkScheduleErrors->any() ? 'true' : 'false' }}
    }"
    @keydown.escape.window="single = false; bulk = false"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form>
            <label class="sr-only" for="schedule-date-filter">Tanggal jadwal</label>
            <input class="input" id="schedule-date-filter" type="date" name="date" value="{{ $date }}" onchange="this.form.submit()">
        </form>
        <div class="flex flex-wrap gap-2">
            <button class="btn-secondary" type="button" @click="bulk = true">Jadwal Massal</button>
            <button class="btn-primary" type="button" @click="single = true">+ Satu Jadwal</button>
        </div>
    </div>

    <div class="table-shell mt-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="table-head">
                    <tr><th class="p-4">Pegawai</th><th>Shift</th><th>Lokasi</th><th>Status</th><th>Catatan</th><th><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $item)
                        <tr>
                            <td class="p-4"><b>{{ $item->employee->user->name }}</b><br><small class="text-slate-500">{{ $item->employee->employee_number }}</small></td>
                            <td>{{ $item->shift->name }}<br><small class="text-slate-500">{{ substr($item->shift->start_time, 0, 5) }}–{{ substr($item->shift->end_time, 0, 5) }}</small></td>
                            <td>{{ $item->workLocation->name }}</td>
                            <td><span class="{{ $item->status === 'working' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($item->status) }}</span></td>
                            <td class="max-w-xs truncate">{{ $item->notes ?: '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.schedules.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost text-red-600" type="submit" onclick="return confirm('Hapus jadwal ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">Belum ada jadwal pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $schedules->links() }}</div>
    </div>

    <div x-show="single" x-cloak class="modal-backdrop">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="single-schedule-dialog-title"
            tabindex="-1"
            @click.outside="single = false"
            class="modal-card max-w-xl"
        >
            <div class="flex justify-between gap-4">
                <h2 id="single-schedule-dialog-title" class="text-2xl font-black">Tetapkan Jadwal</h2>
                <button type="button" data-modal-close aria-label="Tutup formulir jadwal" @click="single = false" class="h-10 w-10 shrink-0 rounded-full bg-slate-100">×</button>
            </div>

            @if($singleScheduleErrors->any())
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Jadwal belum dapat disimpan:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($singleScheduleErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.schedules.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="single-schedule-employee">Pegawai</label>
                    <select class="input" id="single-schedule-employee" name="employee_id" required>
                        @foreach($employees as $item)
                            <option value="{{ $item->id }}" @selected((string) old('employee_id') === (string) $item->id)>{{ $item->employee_number }} — {{ $item->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="single-schedule-date">Tanggal</label>
                        <input class="input" id="single-schedule-date" type="date" name="work_date" value="{{ old('work_date', $date) }}" required>
                    </div>
                    <div>
                        <label class="label" for="single-schedule-status">Status</label>
                        <select class="input" id="single-schedule-status" name="status" required>
                            @foreach(['working' => 'Bekerja', 'off' => 'Libur', 'leave' => 'Cuti', 'holiday' => 'Hari libur'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'working') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label" for="single-schedule-shift">Shift</label>
                    <select class="input" id="single-schedule-shift" name="shift_id" required>
                        @foreach($shifts as $item)<option value="{{ $item->id }}" @selected((string) old('shift_id') === (string) $item->id)>{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="single-schedule-location">Lokasi</label>
                    <select class="input" id="single-schedule-location" name="work_location_id" required>
                        @foreach($locations as $item)<option value="{{ $item->id }}" @selected((string) old('work_location_id') === (string) $item->id)>{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="single-schedule-notes">Catatan</label>
                    <textarea class="input" id="single-schedule-notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan Jadwal</button>
            </form>
        </div>
    </div>

    <div x-show="bulk" x-cloak class="modal-backdrop">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="bulk-schedule-dialog-title"
            tabindex="-1"
            @click.outside="bulk = false"
            class="modal-card max-w-2xl"
        >
            <div class="flex justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-brand-600">MASSAL</p>
                    <h2 id="bulk-schedule-dialog-title" class="text-2xl font-black">Jadwal Massal</h2>
                </div>
                <button type="button" data-modal-close aria-label="Tutup formulir jadwal massal" @click="bulk = false" class="h-10 w-10 shrink-0 rounded-full bg-slate-100">×</button>
            </div>

            @if($bulkScheduleErrors->any())
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Jadwal massal belum dapat disimpan:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($bulkScheduleErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.schedules.bulk-store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="bulk-schedule-employees">Pilih pegawai</label>
                    <select class="input min-h-40" id="bulk-schedule-employees" name="employee_ids[]" multiple required aria-describedby="bulk-schedule-employees-help">
                        @foreach($employees as $item)
                            <option value="{{ $item->id }}" @selected(in_array((string) $item->id, array_map('strval', old('employee_ids', [])), true))>{{ $item->employee_number }} — {{ $item->user->name }}</option>
                        @endforeach
                    </select>
                    <p class="help" id="bulk-schedule-employees-help">
                        <span class="sm:hidden">Ketuk nama yang diperlukan pada pemilih ponsel, lalu konfirmasi pilihan.</span>
                        <span class="hidden sm:inline">Tahan Ctrl (Windows) atau Command (Mac) untuk memilih lebih dari satu pegawai.</span>
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="bulk-schedule-start-date">Tanggal mulai</label>
                        <input class="input" id="bulk-schedule-start-date" type="date" name="start_date" value="{{ old('start_date', $date) }}" required>
                    </div>
                    <div>
                        <label class="label" for="bulk-schedule-end-date">Tanggal selesai</label>
                        <input class="input" id="bulk-schedule-end-date" type="date" name="end_date" value="{{ old('end_date', $date) }}" required>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="bulk-schedule-shift">Shift</label>
                        <select class="input" id="bulk-schedule-shift" name="shift_id" required>
                            @foreach($shifts as $item)<option value="{{ $item->id }}" @selected((string) old('shift_id') === (string) $item->id)>{{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="bulk-schedule-location">Lokasi</label>
                        <select class="input" id="bulk-schedule-location" name="work_location_id" required>
                            @foreach($locations as $item)<option value="{{ $item->id }}" @selected((string) old('work_location_id') === (string) $item->id)>{{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label" for="bulk-schedule-status">Status</label>
                    <select class="input" id="bulk-schedule-status" name="status" required>
                        @foreach(['working' => 'Bekerja', 'off' => 'Libur', 'leave' => 'Cuti', 'holiday' => 'Hari libur'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'working') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold" for="bulk-schedule-skip-weekends">
                    <input id="bulk-schedule-skip-weekends" type="checkbox" name="skip_weekends" value="1" @checked(old('skip_weekends', '1'))>
                    Lewati Sabtu dan Minggu
                </label>

                <div>
                    <label class="label" for="bulk-schedule-notes">Catatan</label>
                    <textarea class="input" id="bulk-schedule-notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan Jadwal Massal</button>
            </form>
        </div>
    </div>
</div>
@endsection
