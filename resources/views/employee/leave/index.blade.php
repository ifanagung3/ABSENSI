@extends('layouts.employee')

@section('title', 'Izin & Cuti — Hadirin')
@section('page-title', 'Izin & Cuti')
@section('page-subtitle', 'Ajukan dan pantau ketidakhadiran Anda.')

@section('content')
<div
    x-data="{ open: {{ (is_object($errors) && $errors->any()) ? 'true' : 'false' }} }"
    @keydown.escape.window="open = false"
>
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Ketidakhadiran</p>
            <h1 class="mt-1 text-3xl font-black">Izin & Cuti</h1>
        </div>
        <button class="btn-primary px-4 py-2.5 text-sm" type="button" @click="open = true">
            + Ajukan
        </button>
    </div>

    <div class="mt-5 space-y-3">
        @forelse($requests as $item)
            @php
                $requestStatus = [
                    'pending' => ['label' => 'Menunggu', 'class' => 'badge-warning'],
                    'approved' => ['label' => 'Disetujui', 'class' => 'badge-success'],
                    'rejected' => ['label' => 'Ditolak', 'class' => 'badge-danger'],
                    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'badge-neutral'],
                ][$item->status] ?? ['label' => ucfirst($item->status), 'class' => 'badge-neutral'];
            @endphp
            <article class="glass-card rounded-3xl p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.15em] text-slate-400">
                            {{ ['permission' => 'Izin', 'sick' => 'Sakit', 'leave' => 'Cuti'][$item->request_type] ?? $item->request_type }}
                        </p>
                        <h2 class="mt-1 font-extrabold">
                            {{ $item->start_date->translatedFormat('d M Y') }}–{{ $item->end_date->translatedFormat('d M Y') }}
                        </h2>
                    </div>
                    <span class="{{ $requestStatus['class'] }}">{{ $requestStatus['label'] }}</span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item->reason }}</p>

                @if($item->review_notes)
                    <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600">
                        <b>Catatan admin:</b> {{ $item->review_notes }}
                    </p>
                @endif

                <div class="mt-4 flex items-center gap-4">
                    @if($item->attachment_path)
                        <a class="text-sm font-bold text-brand-600" href="{{ route('employee.leave.attachment', $item) }}" target="_blank" rel="noopener noreferrer">
                            Lihat lampiran
                        </a>
                    @endif

                    @if($item->status === 'pending')
                        <form method="POST" action="{{ route('employee.leave.cancel', $item) }}">
                            @csrf
                            @method('PATCH')
                            <button class="text-sm font-bold text-red-600" type="submit" onclick="return confirm('Batalkan pengajuan ini?')">
                                Batalkan
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="glass-card rounded-3xl p-8 text-center text-sm text-slate-500">
                Belum ada pengajuan.
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $requests->links() }}</div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="modal-backdrop"
    >
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="leave-request-dialog-title"
            tabindex="-1"
            @click.outside="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-[.985]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-[.985]"
            class="modal-card max-w-lg"
        >
            <div class="flex justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-brand-600">FORMULIR</p>
                    <h2 id="leave-request-dialog-title" class="text-2xl font-black">Ajukan Izin/Cuti</h2>
                </div>
                <button
                    type="button"
                    data-modal-close
                    aria-label="Tutup formulir pengajuan izin"
                    @click="open = false"
                    class="h-10 w-10 shrink-0 rounded-full bg-slate-100"
                >
                    ×
                </button>
            </div>

            @if(is_object($errors) && $errors->any())
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Pengajuan belum dapat dikirim:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('employee.leave.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="label" for="leave-request-type">Jenis</label>
                    <select class="input" id="leave-request-type" name="request_type" required>
                        <option value="permission" @selected(old('request_type', 'permission') === 'permission')>Izin</option>
                        <option value="sick" @selected(old('request_type') === 'sick')>Sakit</option>
                        <option value="leave" @selected(old('request_type') === 'leave')>Cuti</option>
                    </select>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="leave-start-date">Mulai</label>
                        <input class="input" id="leave-start-date" type="date" name="start_date" value="{{ old('start_date') }}" min="{{ now()->toDateString() }}" required>
                    </div>
                    <div>
                        <label class="label" for="leave-end-date">Selesai</label>
                        <input class="input" id="leave-end-date" type="date" name="end_date" value="{{ old('end_date') }}" min="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div>
                    <label class="label" for="leave-reason">Alasan</label>
                    <textarea class="input min-h-28" id="leave-reason" name="reason" minlength="10" required>{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label class="label" for="leave-attachment">Lampiran opsional</label>
                    <input class="input" id="leave-attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" aria-describedby="leave-attachment-help">
                    <p class="help" id="leave-attachment-help">JPG, JPEG, PNG, atau PDF maksimal 5 MB.</p>
                </div>

                <button class="btn-primary w-full" type="submit">Kirim Pengajuan</button>
            </form>
        </div>
    </div>
</div>
@endsection
