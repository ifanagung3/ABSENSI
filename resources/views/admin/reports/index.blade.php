@extends('layouts.admin')

@section('title', 'Laporan — Hadirin')
@section('page-title', 'Laporan Kehadiran')
@section('page-subtitle', 'Ringkas dan ekspor kehadiran bulanan pegawai.')

@section('content')
<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <form>
        <label class="label" for="report-month">Bulan laporan</label>
        <div class="flex gap-2">
            <input class="input" id="report-month" type="month" name="month" value="{{ $selectedMonth }}">
            <button class="btn-secondary" type="submit">Tampilkan</button>
        </div>
    </form>
    <a class="btn-primary" href="{{ route('admin.reports.csv', ['month' => $selectedMonth]) }}">⇩ Unduh Laporan CSV</a>
</div>

<div class="table-shell mt-5">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[880px] text-left text-sm">
            <thead class="table-head">
                <tr><th class="p-4">Pegawai</th><th>Unit</th><th>Terjadwal</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Cuti</th><th>Alpa/invalid</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($summary as $item)
                    <tr>
                        <td class="p-4"><b>{{ $item->user->name }}</b><br><small class="text-slate-500">{{ $item->employee_number }}</small></td>
                        <td>{{ $item->department?->name ?? '-' }}</td>
                        <td>{{ $item->scheduled_count }}</td>
                        <td>{{ $item->present_count }}</td>
                        <td>{{ $item->late_count }}</td>
                        <td>{{ $item->permission_count }}</td>
                        <td>{{ $item->sick_count }}</td>
                        <td>{{ $item->leave_count }}</td>
                        <td>{{ $item->absent_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-10 text-center text-slate-500">Belum ada data pada bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $summary->links() }}</div>
</div>
@endsection
