@extends('layouts.admin')

@section('title', 'Unit & Jabatan — Hadirin')
@section('page-title', 'Unit Kerja & Jabatan')
@section('page-subtitle', 'Kelola referensi organisasi untuk data pegawai.')

@section('content')
@php
    $createDepartmentErrors = $errors->createDepartment;
    $updateDepartmentErrors = $errors->updateDepartment;
    $createPositionErrors = $errors->createPosition;
    $updatePositionErrors = $errors->updatePosition;
    $editingDepartmentId = session('editing_department_id');
    $editingPositionId = session('editing_position_id');
@endphp

<div class="grid gap-6 xl:grid-cols-2">
    <section
        class="panel p-5"
        x-data="{ open: {{ $createDepartmentErrors->any() ? 'true' : 'false' }} }"
        @keydown.escape.window="open = false"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Referensi</p>
                <h2 class="text-xl font-black">Unit Kerja</h2>
            </div>
            <button class="btn-primary px-4 py-2 text-sm" type="button" @click="open = true">+ Tambah</button>
        </div>

        <div class="mt-5 space-y-3">
            @forelse($departments as $item)
                @php
                    $thisDepartmentHasErrors = $updateDepartmentErrors->any()
                        && (string) $editingDepartmentId === (string) $item->id;
                @endphp
                <article class="rounded-2xl border border-slate-200 p-4" x-data="{ edit: {{ $thisDepartmentHasErrors ? 'true' : 'false' }} }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-bold">{{ $item->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $item->description ?: 'Tanpa deskripsi' }} · {{ $item->employees_count }} pegawai</p>
                        </div>
                        <div class="flex gap-1">
                            <button class="btn-ghost" type="button" @click="edit = true">Edit</button>
                            <form method="POST" action="{{ route('admin.master-data.departments.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost text-red-600" type="submit" onclick="return confirm('Hapus unit kerja ini?')">Hapus</button>
                            </form>
                        </div>
                    </div>

                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.master-data.departments.update', $item) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        @if($thisDepartmentHasErrors)
                            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">@foreach($updateDepartmentErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div>
                            <label class="label" for="department-name-{{ $item->id }}">Nama unit</label>
                            <input class="input" id="department-name-{{ $item->id }}" name="name" value="{{ $thisDepartmentHasErrors ? old('name') : $item->name }}" required>
                        </div>
                        <div>
                            <label class="label" for="department-description-{{ $item->id }}">Deskripsi</label>
                            <textarea class="input" id="department-description-{{ $item->id }}" name="description">{{ $thisDepartmentHasErrors ? old('description') : $item->description }}</textarea>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn-primary px-4 py-2 text-sm" type="submit">Simpan</button>
                            <button type="button" class="btn-secondary px-4 py-2 text-sm" @click="edit = false">Batal</button>
                        </div>
                    </form>
                </article>
            @empty
                <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada unit kerja.</p>
            @endforelse
        </div>

        <div x-show="open" x-cloak class="modal-backdrop">
            <form
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-department-dialog-title"
                tabindex="-1"
                @click.outside="open = false"
                method="POST"
                action="{{ route('admin.master-data.departments.store') }}"
                class="modal-card max-w-md space-y-4"
            >
                @csrf
                <div class="flex justify-between gap-4">
                    <h2 id="create-department-dialog-title" class="text-xl font-black">Tambah Unit Kerja</h2>
                    <button type="button" data-modal-close aria-label="Tutup formulir tambah unit kerja" @click="open = false" class="h-10 w-10 rounded-full bg-slate-100">×</button>
                </div>
                @if($createDepartmentErrors->any())
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">@foreach($createDepartmentErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div>
                    <label class="label" for="create-department-name">Nama unit</label>
                    <input class="input" id="create-department-name" name="name" value="{{ $createDepartmentErrors->any() ? old('name') : '' }}" required>
                </div>
                <div>
                    <label class="label" for="create-department-description">Deskripsi</label>
                    <textarea class="input" id="create-department-description" name="description">{{ $createDepartmentErrors->any() ? old('description') : '' }}</textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan</button>
            </form>
        </div>
    </section>

    <section
        class="panel p-5"
        x-data="{ open: {{ $createPositionErrors->any() ? 'true' : 'false' }} }"
        @keydown.escape.window="open = false"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Referensi</p>
                <h2 class="text-xl font-black">Jabatan</h2>
            </div>
            <button class="btn-primary px-4 py-2 text-sm" type="button" @click="open = true">+ Tambah</button>
        </div>

        <div class="mt-5 space-y-3">
            @forelse($positions as $item)
                @php
                    $thisPositionHasErrors = $updatePositionErrors->any()
                        && (string) $editingPositionId === (string) $item->id;
                @endphp
                <article class="rounded-2xl border border-slate-200 p-4" x-data="{ edit: {{ $thisPositionHasErrors ? 'true' : 'false' }} }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-bold">{{ $item->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $item->description ?: 'Tanpa deskripsi' }} · {{ $item->employees_count }} pegawai</p>
                        </div>
                        <div class="flex gap-1">
                            <button class="btn-ghost" type="button" @click="edit = true">Edit</button>
                            <form method="POST" action="{{ route('admin.master-data.positions.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost text-red-600" type="submit" onclick="return confirm('Hapus jabatan ini?')">Hapus</button>
                            </form>
                        </div>
                    </div>

                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.master-data.positions.update', $item) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        @if($thisPositionHasErrors)
                            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">@foreach($updatePositionErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div>
                            <label class="label" for="position-name-{{ $item->id }}">Nama jabatan</label>
                            <input class="input" id="position-name-{{ $item->id }}" name="name" value="{{ $thisPositionHasErrors ? old('name') : $item->name }}" required>
                        </div>
                        <div>
                            <label class="label" for="position-description-{{ $item->id }}">Deskripsi</label>
                            <textarea class="input" id="position-description-{{ $item->id }}" name="description">{{ $thisPositionHasErrors ? old('description') : $item->description }}</textarea>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn-primary px-4 py-2 text-sm" type="submit">Simpan</button>
                            <button type="button" class="btn-secondary px-4 py-2 text-sm" @click="edit = false">Batal</button>
                        </div>
                    </form>
                </article>
            @empty
                <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada jabatan.</p>
            @endforelse
        </div>

        <div x-show="open" x-cloak class="modal-backdrop">
            <form
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-position-dialog-title"
                tabindex="-1"
                @click.outside="open = false"
                method="POST"
                action="{{ route('admin.master-data.positions.store') }}"
                class="modal-card max-w-md space-y-4"
            >
                @csrf
                <div class="flex justify-between gap-4">
                    <h2 id="create-position-dialog-title" class="text-xl font-black">Tambah Jabatan</h2>
                    <button type="button" data-modal-close aria-label="Tutup formulir tambah jabatan" @click="open = false" class="h-10 w-10 rounded-full bg-slate-100">×</button>
                </div>
                @if($createPositionErrors->any())
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">@foreach($createPositionErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div>
                    <label class="label" for="create-position-name">Nama jabatan</label>
                    <input class="input" id="create-position-name" name="name" value="{{ $createPositionErrors->any() ? old('name') : '' }}" required>
                </div>
                <div>
                    <label class="label" for="create-position-description">Deskripsi</label>
                    <textarea class="input" id="create-position-description" name="description">{{ $createPositionErrors->any() ? old('description') : '' }}</textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan</button>
            </form>
        </div>
    </section>
</div>
@endsection
