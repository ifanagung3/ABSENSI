@extends('layouts.admin')

@section('title', 'Izin & Cuti — Hadirin')
@section('page-title', 'Persetujuan Izin & Cuti')
@section('page-subtitle', 'Tinjau pengajuan ketidakhadiran pegawai.')

@section('content')
<form class="mb-5 max-w-xs">
    <label class="sr-only" for="leave-status-filter">Status pengajuan</label>
    <select class="input" id="leave-status-filter" name="status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $status => $label)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
        @endforeach
    </select>
</form>

<div class="space-y-4">
    @forelse($requests as $item)
        @php
            $statusPresentation = [
                'pending' => ['Menunggu', 'badge-warning'],
                'approved' => ['Disetujui', 'badge-success'],
                'rejected' => ['Ditolak', 'badge-danger'],
                'cancelled' => ['Dibatalkan', 'badge-neutral'],
            ][$item->status] ?? [ucfirst($item->status), 'badge-neutral'];
        @endphp
        <article class="panel p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">{{ ['permission' => 'Izin', 'sick' => 'Sakit', 'leave' => 'Cuti'][$item->request_type] ?? $item->request_type }}</p>
                    <h2 class="mt-1 text-xl font-black">{{ $item->employee->user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $item->employee->employee_number }} · {{ $item->employee->department?->name }} · {{ $item->start_date->format('d M Y') }}–{{ $item->end_date->format('d M Y') }}</p>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-700">{{ $item->reason }}</p>
                    @if($item->attachment_path)
                        <a class="mt-3 inline-flex text-sm font-bold text-brand-600" href="{{ route('admin.leave.attachment', $item) }}" target="_blank" rel="noopener noreferrer">Lihat lampiran ↗</a>
                    @endif
                    @if($item->review_notes)
                        <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm text-slate-600"><b>Catatan:</b> {{ $item->review_notes }}</p>
                    @endif
                </div>
                <span class="{{ $statusPresentation[1] }}">{{ $statusPresentation[0] }}</span>
            </div>

            @if($item->status === 'pending')
                <form method="POST" action="{{ route('admin.leave.review', $item) }}" class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')
                    <div class="flex-1">
                        <label class="label" for="leave-review-notes-{{ $item->id }}">Catatan peninjauan</label>
                        <input class="input" id="leave-review-notes-{{ $item->id }}" name="review_notes" maxlength="2000" placeholder="Tambahkan alasan keputusan bila perlu">
                    </div>
                    <button class="btn-secondary" type="submit" name="status" value="rejected" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
                    <button class="btn-primary" type="submit" name="status" value="approved" onclick="return confirm('Setujui pengajuan ini? Jadwal dan rekap akan diperbarui.')">Setujui</button>
                </form>
            @endif
        </article>
    @empty
        <div class="panel p-10 text-center text-slate-500">Tidak ada pengajuan.</div>
    @endforelse
</div>

<div class="mt-5">{{ $requests->links() }}</div>
@endsection
