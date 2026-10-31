@extends('layouts.admin')

@section('title', 'Pegawai — Hadirin')
@section('page-title', 'Manajemen Pegawai')
@section('page-subtitle', 'Kelola akun, penempatan, dan akses absensi pegawai.')

@section('content')
@php
    $createEmployeeErrors = $errors->createEmployee;
    $updateEmployeeErrors = $errors->updateEmployee;
    $resetPasswordErrors = $errors->resetPassword;
    $editingEmployeeId = session('editing_employee_id');
@endphp
<div
    x-data="{ createOpen: {{ $createEmployeeErrors->any() ? 'true' : 'false' }} }"
    @keydown.escape.window="createOpen = false"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form class="flex flex-1 flex-col gap-2 sm:flex-row">
            <label class="sr-only" for="employee-search">Cari pegawai</label>
            <input
                class="input max-w-md"
                id="employee-search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari nama, email, atau nomor pegawai"
            >

            <label class="sr-only" for="employee-status-filter">Status pegawai</label>
            <select class="input max-w-44" id="employee-status-filter" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>

            <button class="btn-secondary" type="submit">Cari</button>
        </form>

        <button class="btn-primary" type="button" @click="createOpen = true">
            + Tambah Pegawai
        </button>
    </div>

    <div class="table-shell mt-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="p-4">Pegawai</th>
                        <th>Unit/Jabatan</th>
                        <th>Kontak</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th class="pr-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($employees as $item)
                        @php
                            $thisEmployeeHasUpdateErrors = $updateEmployeeErrors->any()
                                && (string) $editingEmployeeId === (string) $item->id;
                        @endphp
                        <tr
                            x-data="{
                                edit: {{ $thisEmployeeHasUpdateErrors ? 'true' : 'false' }},
                                passwordOpen: {{ session('password_employee_id') == $item->id ? 'true' : 'false' }},
                                showPassword: false
                            }"
                            @keydown.escape.window="edit = false; passwordOpen = false; showPassword = false"
                        >
                            <td class="p-4">
                                <p class="font-bold">{{ $item->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->employee_number }}</p>
                            </td>

                            <td>
                                {{ $item->department?->name ?? '-' }}
                                <br>
                                <small class="text-slate-500">{{ $item->position?->name ?? '-' }}</small>
                            </td>

                            <td>
                                {{ $item->user->email }}
                                <br>
                                <small class="text-slate-500">{{ $item->phone ?? '-' }}</small>
                            </td>

                            <td>{{ $item->defaultLocation?->name ?? '-' }}</td>

                            <td>
                                <span class="{{ $item->attendance_enabled ? 'badge-success' : 'badge-danger' }}">
                                    {{ $item->attendance_enabled ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="pr-4">
                                <div class="flex justify-end gap-2">
                                    <button class="btn-ghost" type="button" @click="edit = true">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.employees.toggle', $item) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="btn-ghost"
                                            type="submit"
                                            onclick="return confirm('Ubah status akun pegawai ini?')"
                                        >
                                            {{ $item->attendance_enabled ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <button
                                        class="btn-ghost"
                                        type="button"
                                        @click="passwordOpen = true"
                                    >
                                        Ubah Password
                                    </button>
                                </div>

                                {{-- Modal edit pegawai --}}
                                <div x-show="edit" x-cloak class="modal-backdrop">
                                    <div
                                        role="dialog"
                                        aria-modal="true"
                                        aria-labelledby="edit-employee-dialog-title-{{ $item->id }}"
                                        tabindex="-1"
                                        @click.outside="edit = false"
                                        class="modal-card max-w-2xl"
                                    >
                                        <div class="flex justify-between">
                                            <h2 id="edit-employee-dialog-title-{{ $item->id }}" class="text-2xl font-black">Edit Pegawai</h2>
                                            <button type="button" data-modal-close aria-label="Tutup formulir edit pegawai" @click="edit = false" class="h-10 w-10 rounded-full bg-slate-100">×</button>
                                        </div>

                                        @if($thisEmployeeHasUpdateErrors)
                                            <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                                <p class="font-bold">Data pegawai belum dapat diperbarui:</p>
                                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                                    @foreach($updateEmployeeErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('admin.employees.update', $item) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <label class="label" for="employee-name-{{ $item->id }}">Nama</label>
                                                <input class="input" id="employee-name-{{ $item->id }}" name="name" value="{{ $thisEmployeeHasUpdateErrors ? old('name') : $item->user->name }}" required autocomplete="name">
                                            </div>

                                            <div>
                                                <label class="label" for="employee-email-{{ $item->id }}">Email</label>
                                                <input class="input" id="employee-email-{{ $item->id }}" type="email" name="email" value="{{ $thisEmployeeHasUpdateErrors ? old('email') : $item->user->email }}" required autocomplete="off">
                                            </div>

                                            <div>
                                                <label class="label" for="employee-number-{{ $item->id }}">Nomor pegawai</label>
                                                <input class="input" id="employee-number-{{ $item->id }}" name="employee_number" value="{{ $thisEmployeeHasUpdateErrors ? old('employee_number') : $item->employee_number }}" required>
                                            </div>

                                            <div>
                                                <label class="label" for="employee-phone-{{ $item->id }}">Telepon</label>
                                                <input class="input" id="employee-phone-{{ $item->id }}" name="phone" value="{{ $thisEmployeeHasUpdateErrors ? old('phone') : $item->phone }}" autocomplete="tel">
                                            </div>

                                            <div>
                                                <label class="label" for="employee-department-{{ $item->id }}">Unit kerja</label>
                                                <select class="input" id="employee-department-{{ $item->id }}" name="department_id">
                                                    <option value="">-</option>
                                                    @foreach ($departments as $option)
                                                        <option value="{{ $option->id }}" @selected((string) ($thisEmployeeHasUpdateErrors ? old('department_id') : $item->department_id) === (string) $option->id)>
                                                            {{ $option->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label" for="employee-position-{{ $item->id }}">Jabatan</label>
                                                <select class="input" id="employee-position-{{ $item->id }}" name="position_id">
                                                    <option value="">-</option>
                                                    @foreach ($positions as $option)
                                                        <option value="{{ $option->id }}" @selected((string) ($thisEmployeeHasUpdateErrors ? old('position_id') : $item->position_id) === (string) $option->id)>
                                                            {{ $option->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label" for="employee-location-{{ $item->id }}">Lokasi</label>
                                                <select class="input" id="employee-location-{{ $item->id }}" name="default_location_id">
                                                    <option value="">-</option>
                                                    @foreach ($locations as $option)
                                                        <option value="{{ $option->id }}" @selected((string) ($thisEmployeeHasUpdateErrors ? old('default_location_id') : $item->default_location_id) === (string) $option->id)>
                                                            {{ $option->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label" for="employee-employment-status-{{ $item->id }}">Status kerja</label>
                                                <select class="input" id="employee-employment-status-{{ $item->id }}" name="employment_status">
                                                    @foreach (['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Magang', 'inactive' => 'Tidak aktif'] as $value => $label)
                                                        <option value="{{ $value }}" @selected(($thisEmployeeHasUpdateErrors ? old('employment_status') : $item->employment_status) === $value)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label" for="employee-join-date-{{ $item->id }}">Tanggal bergabung</label>
                                                <input class="input" id="employee-join-date-{{ $item->id }}" type="date" name="join_date" value="{{ $thisEmployeeHasUpdateErrors ? old('join_date') : $item->join_date?->format('Y-m-d') }}">
                                            </div>

                                            <div class="sm:col-span-2">
                                                <button class="btn-primary w-full" type="submit">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                {{-- Modal ubah password --}}
                                <div
                                    x-show="passwordOpen"
                                    x-cloak
                                    x-transition.opacity
                                    @keydown.escape.window="passwordOpen = false; showPassword = false"
                                    class="modal-backdrop"
                                >
                                    <div
                                        role="dialog"
                                        aria-modal="true"
                                        aria-labelledby="employee-password-dialog-title-{{ $item->id }}"
                                        tabindex="-1"
                                        @click.outside="passwordOpen = false; showPassword = false"
                                        class="modal-card max-w-md"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wide text-brand-600">Keamanan Akun</p>
                                                <h2 id="employee-password-dialog-title-{{ $item->id }}" class="mt-1 text-2xl font-black">Ubah Password</h2>
                                                <p class="mt-2 text-sm text-slate-500">
                                                    Buat password baru untuk <strong>{{ $item->user->name }}</strong>.
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                data-modal-close
                                                class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-xl"
                                                aria-label="Tutup formulir ubah password"
                                                @click="passwordOpen = false; showPassword = false"
                                            >
                                                ×
                                            </button>
                                        </div>

                                        @if (session('password_employee_id') == $item->id && $resetPasswordErrors->any())
                                            <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                                {{ $resetPasswordErrors->first() }}
                                            </div>
                                        @endif

                                        <form
                                            method="POST"
                                            action="{{ route('admin.employees.reset-password', $item) }}"
                                            class="mt-6 space-y-5"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <label class="label" for="password-{{ $item->id }}">Password baru</label>
                                                <input
                                                    class="input"
                                                    id="password-{{ $item->id }}"
                                                    name="password"
                                                    :type="showPassword ? 'text' : 'password'"
                                                    placeholder="Minimal 8 karakter"
                                                    minlength="8"
                                                    maxlength="72"
                                                    required
                                                    autocomplete="new-password"
                                                >
                                            </div>

                                            <div>
                                                <label class="label" for="password-confirmation-{{ $item->id }}">Konfirmasi password</label>
                                                <input
                                                    class="input"
                                                    id="password-confirmation-{{ $item->id }}"
                                                    name="password_confirmation"
                                                    :type="showPassword ? 'text' : 'password'"
                                                    placeholder="Ketik ulang password"
                                                    minlength="8"
                                                    maxlength="72"
                                                    required
                                                    autocomplete="new-password"
                                                >
                                            </div>

                                            <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600" for="show-password-{{ $item->id }}">
                                                <input
                                                    id="show-password-{{ $item->id }}"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-slate-300"
                                                    x-model="showPassword"
                                                >
                                                <span>Tampilkan password</span>
                                            </label>

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <button
                                                    type="button"
                                                    class="btn-secondary w-full"
                                                    @click="passwordOpen = false; showPassword = false"
                                                >
                                                    Batal
                                                </button>

                                                <button type="submit" class="btn-primary w-full">
                                                    Simpan Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-500">
                                Data pegawai tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $employees->links() }}</div>
    </div>

    {{-- Modal tambah pegawai --}}
    <div x-show="createOpen" x-cloak class="modal-backdrop">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-employee-dialog-title"
            tabindex="-1"
            @click.outside="createOpen = false"
            class="modal-card max-w-2xl"
        >
            <div class="flex justify-between">
                <div>
                    <p class="text-xs font-bold text-brand-600">AKUN BARU</p>
                    <h2 id="create-employee-dialog-title" class="text-2xl font-black">Tambah Pegawai</h2>
                </div>

                <button type="button" data-modal-close aria-label="Tutup formulir tambah pegawai" @click="createOpen = false" class="h-10 w-10 rounded-full bg-slate-100">×</button>
            </div>

            @if($createEmployeeErrors->any())
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-bold">Akun pegawai belum dapat dibuat:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($createEmployeeErrors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.employees.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf

                <div>
                    <label class="label" for="create-employee-name">Nama lengkap</label>
                    <input class="input" id="create-employee-name" name="name" value="{{ old('name') }}" required autocomplete="name">
                </div>

                <div>
                    <label class="label" for="create-employee-email">Email</label>
                    <input class="input" id="create-employee-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="off">
                </div>

                <div>
                    <label class="label" for="create-employee-number">Nomor pegawai</label>
                    <input class="input" id="create-employee-number" name="employee_number" value="{{ old('employee_number') }}" required>
                </div>

                <div>
                    <label class="label" for="create-employee-phone">Telepon</label>
                    <input class="input" id="create-employee-phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                </div>

                <div>
                    <label class="label" for="create-employee-department">Unit kerja</label>
                    <select class="input" id="create-employee-department" name="department_id">
                        <option value="">-</option>
                        @foreach ($departments as $option)
                            <option value="{{ $option->id }}" @selected((string) old('department_id') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="create-employee-position">Jabatan</label>
                    <select class="input" id="create-employee-position" name="position_id">
                        <option value="">-</option>
                        @foreach ($positions as $option)
                            <option value="{{ $option->id }}" @selected((string) old('position_id') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="create-employee-location">Lokasi</label>
                    <select class="input" id="create-employee-location" name="default_location_id">
                        <option value="">-</option>
                        @foreach ($locations as $option)
                            <option value="{{ $option->id }}" @selected((string) old('default_location_id') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="create-employee-employment-status">Status kerja</label>
                    <select class="input" id="create-employee-employment-status" name="employment_status">
                        <option value="permanent" @selected(old('employment_status', 'permanent') === 'permanent')>Tetap</option>
                        <option value="contract" @selected(old('employment_status') === 'contract')>Kontrak</option>
                        <option value="intern" @selected(old('employment_status') === 'intern')>Magang</option>
                    </select>
                </div>

                <div>
                    <label class="label" for="create-employee-join-date">Tanggal bergabung</label>
                    <input class="input" id="create-employee-join-date" type="date" name="join_date" value="{{ old('join_date', now()->toDateString()) }}">
                </div>

                <div class="sm:col-span-2">
                    <button class="btn-primary w-full" type="submit">Buat Akun Pegawai</button>
                    <p class="help text-center">Sistem membuat password sementara acak dan menampilkannya satu kali.</p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
