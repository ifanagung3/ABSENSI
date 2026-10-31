@extends('layouts.admin')

@section('title', 'Shift — Hadirin')
@section('page-title', 'Pengaturan Shift')
@section('page-subtitle', 'Atur jam kerja dan jendela waktu absensi.')

@section('content')
@php
    $createShiftErrors = $errors->createShift;
    $updateShiftErrors = $errors->updateShift;
    $editingShiftId = session('editing_shift_id');
@endphp

<div
    x-data="{ createOpen: {{ $createShiftErrors->any() ? 'true' : 'false' }} }"
    @keydown.escape.window="createOpen = false"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Atur jam kerja, toleransi terlambat, dan waktu pembukaan absensi.</p>
        <button class="btn-primary shrink-0" type="button" @click="createOpen = true">+ Tambah Shift</button>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        @forelse($shifts as $item)
            @php $thisShiftHasErrors = $updateShiftErrors->any() && (string) $editingShiftId === (string) $item->id; @endphp
            <article class="panel p-5" x-data="{ edit: {{ $thisShiftHasErrors ? 'true' : 'false' }} }" @keydown.escape.window="edit = false">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black">{{ $item->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ substr($item->start_time, 0, 5) }}–{{ substr($item->end_time, 0, 5) }} {{ $item->crosses_midnight ? '· lintas hari' : '' }}</p>
                    </div>
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-xl" aria-hidden="true">◷</span>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Buka masuk</p><p class="font-black">-{{ $item->check_in_open_minutes }} m</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Toleransi</p><p class="font-black">{{ $item->late_tolerance_minutes }} m</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Digunakan</p><p class="font-black">{{ $item->schedules_count }}</p></div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button class="btn-secondary flex-1" type="button" @click="edit = true">Edit Shift</button>
                    <form method="POST" action="{{ route('admin.shifts.destroy', $item) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondary text-red-600" type="submit" onclick="return confirm('Hapus shift ini?')">Hapus</button>
                    </form>
                </div>

                <div x-show="edit" x-cloak class="modal-backdrop">
                    <form
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="edit-shift-dialog-title-{{ $item->id }}"
                        tabindex="-1"
                        @click.outside="edit = false"
                        method="POST"
                        action="{{ route('admin.shifts.update', $item) }}"
                        class="modal-card max-w-xl space-y-4"
                    >
                        @csrf
                        @method('PATCH')
                        <div class="flex justify-between gap-4">
                            <h2 id="edit-shift-dialog-title-{{ $item->id }}" class="text-2xl font-black">Edit Shift</h2>
                            <button type="button" data-modal-close aria-label="Tutup formulir edit shift" @click="edit = false" class="h-10 w-10 shrink-0 rounded-full bg-slate-100">×</button>
                        </div>

                        @if($thisShiftHasErrors)
                            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">@foreach($updateShiftErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif

                        @include('admin.shifts.partials.form', ['shift' => $item, 'shiftFormKey' => 'edit-'.$item->id])
                        <button class="btn-primary w-full" type="submit">Simpan Perubahan</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel p-10 text-center text-slate-500">Belum ada shift.</div>
        @endforelse
    </div>

    <div class="mt-5">{{ $shifts->links() }}</div>

    <div x-show="createOpen" x-cloak class="modal-backdrop">
        <form
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-shift-dialog-title"
            tabindex="-1"
            @click.outside="createOpen = false"
            method="POST"
            action="{{ route('admin.shifts.store') }}"
            class="modal-card max-w-xl space-y-4"
        >
            @csrf
            <div class="flex justify-between gap-4">
                <h2 id="create-shift-dialog-title" class="text-2xl font-black">Tambah Shift</h2>
                <button type="button" data-modal-close aria-label="Tutup formulir tambah shift" @click="createOpen = false" class="h-10 w-10 shrink-0 rounded-full bg-slate-100">×</button>
            </div>

            @if($createShiftErrors->any())
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">@foreach($createShiftErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @include('admin.shifts.partials.form', ['shift' => null, 'shiftFormKey' => 'create'])
            <button class="btn-primary w-full" type="submit">Simpan Shift</button>
        </form>
    </div>
</div>
@endsection
