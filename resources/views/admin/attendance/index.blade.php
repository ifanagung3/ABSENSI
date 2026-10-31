@extends('layouts.admin')

@section('title', 'Absensi — Hadirin')
@section('page-title', 'Data Absensi')
@section('page-subtitle', 'Tinjau catatan masuk, pulang, dan status kehadiran.')

@section('content')
<form class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:grid-cols-4">
    <div>
        <label class="sr-only" for="attendance-search">Cari pegawai</label>
        <input class="input" id="attendance-search" name="q" value="{{ request('q') }}" placeholder="Nama / nomor pegawai">
    </div>
    <div>
        <label class="sr-only" for="attendance-date">Tanggal absensi</label>
        <input class="input" id="attendance-date" type="date" name="date" value="{{ request('date') }}">
    </div>
    <div>
        <label class="sr-only" for="attendance-status">Status absensi</label>
        <select class="input" id="attendance-status" name="status">
            <option value="">Semua status</option>
            @foreach([
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'absent' => 'Alpa',
                'permission' => 'Izin',
                'sick' => 'Sakit',
                'leave' => 'Cuti',
                'pending_review' => 'Perlu ditinjau',
                'invalid' => 'Tidak valid',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-primary" type="submit">Terapkan Filter</button>
</form>

<div class="table-shell mt-5">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[920px] text-left text-sm">
            <thead class="table-head">
                <tr>
                    <th class="p-4">Tanggal</th>
                    <th>Pegawai</th>
                    <th>Masuk/Pulang</th>
                    <th>Lokasi</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($records as $item)
                    @php
                        $statusPresentation = match($item->status) {
                            'present' => ['Hadir', 'badge-success'],
                            'late' => ['Terlambat', 'badge-warning'],
                            'absent' => ['Alpa', 'badge-danger'],
                            'permission' => ['Izin', 'badge-info'],
                            'sick' => ['Sakit', 'badge-warning'],
                            'leave' => ['Cuti', 'badge-info'],
                            'pending_review' => ['Perlu ditinjau', 'badge-info'],
                            'invalid' => ['Tidak valid', 'badge-danger'],
                            default => [ucfirst(str_replace('_', ' ', $item->status)), 'badge-neutral'],
                        };
                    @endphp
                    <tr>
                        <td class="p-4">{{ $item->attendance_date->format('d-m-Y') }}</td>
                        <td>
                            <b>{{ $item->employee->user->name }}</b><br>
                            <small class="text-slate-500">{{ $item->employee->employee_number }} · {{ $item->employee->department?->name }}</small>
                        </td>
                        <td>{{ $item->checked_in_at?->format('H:i') ?? '--:--' }} / {{ $item->checked_out_at?->format('H:i') ?? '--:--' }}</td>
                        <td>{{ $item->schedule?->workLocation?->name ?? '-' }}</td>
                        <td>{{ $item->events->count() }} foto</td>
                        <td><span class="{{ $statusPresentation[1] }}">{{ $statusPresentation[0] }}</span></td>
                        <td><a class="font-bold text-brand-600" href="{{ route('admin.attendance.show', $item) }}">Detail →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-slate-500">Data tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
