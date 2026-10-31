@extends('layouts.employee')

@section('title', 'Profil — Hadirin')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Perbarui informasi pribadi dan keamanan akun.')

@section('content')
<div><p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Akun</p><h1 class="mt-1 text-3xl font-black">Profil Saya</h1></div>

<section class="glass-card mt-5 rounded-[2rem] p-5">
    <div class="flex items-center gap-4">
        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-3xl bg-slate-900 text-2xl font-black text-white" aria-hidden="true">{{ strtoupper(substr($employee->user->name, 0, 1)) }}</div>
        <div class="min-w-0"><h2 class="truncate text-xl font-black">{{ $employee->user->name }}</h2><p class="truncate text-sm text-slate-500">{{ $employee->employee_number }} · {{ $employee->position?->name ?? 'Pegawai' }}</p></div>
    </div>
    <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Unit kerja</p><p class="mt-1 font-bold">{{ $employee->department?->name ?? '-' }}</p></div>
        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Lokasi</p><p class="mt-1 font-bold">{{ $employee->defaultLocation?->name ?? '-' }}</p></div>
    </div>
</section>

<section class="glass-card mt-5 rounded-[2rem] p-5">
    <h2 class="text-lg font-black">Informasi pribadi</h2>
    <form method="POST" action="{{ route('employee.profile.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('PATCH')
        <div>
            <label class="label" for="employee-profile-name">Nama lengkap</label>
            <input class="input" id="employee-profile-name" name="name" value="{{ old('name', $employee->user->name) }}" required autocomplete="name">
        </div>
        <div>
            <label class="label" for="employee-profile-email">Email</label>
            <input class="input bg-slate-50" id="employee-profile-email" type="email" value="{{ $employee->user->email }}" disabled autocomplete="username" aria-describedby="employee-email-help">
            <p class="help" id="employee-email-help">Email hanya dapat diubah oleh administrator.</p>
        </div>
        <div>
            <label class="label" for="employee-profile-phone">Nomor telepon</label>
            <input class="input" id="employee-profile-phone" name="phone" value="{{ old('phone', $employee->phone) }}" autocomplete="tel">
        </div>
        <button class="btn-primary w-full" type="submit">Simpan Profil</button>
    </form>
</section>

<section class="glass-card mt-5 rounded-[2rem] p-5">
    <h2 class="text-lg font-black">Ubah password</h2>
    <p class="help" id="employee-password-help">Minimal 8 karakter, berisi huruf dan angka.</p>
    <form method="POST" action="{{ route('employee.profile.password') }}" class="mt-4 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="label" for="employee-current-password">Password saat ini</label>
            <input class="input" id="employee-current-password" type="password" name="current_password" required autocomplete="current-password">
        </div>
        <div>
            <label class="label" for="employee-new-password">Password baru</label>
            <input class="input" id="employee-new-password" type="password" name="password" required autocomplete="new-password" aria-describedby="employee-password-help">
        </div>
        <div>
            <label class="label" for="employee-password-confirmation">Konfirmasi password</label>
            <input class="input" id="employee-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button class="btn-primary w-full" type="submit">Perbarui Password</button>
    </form>
</section>
@endsection
