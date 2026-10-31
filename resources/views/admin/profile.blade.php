@extends('layouts.admin')

@section('title', 'Profil Administrator — Hadirin')
@section('page-title', 'Profil Administrator')
@section('page-subtitle', 'Perbarui identitas dan keamanan akun Anda.')

@section('content')
<div class="grid max-w-5xl gap-6 xl:grid-cols-2">
    <section class="panel p-6">
        <div class="flex items-center gap-4">
            <div class="grid h-16 w-16 place-items-center rounded-3xl bg-slate-900 text-2xl font-black text-white" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div class="min-w-0"><h2 class="truncate text-xl font-black">{{ $user->name }}</h2><p class="truncate text-sm text-slate-500">Administrator · {{ $user->email }}</p></div>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="label" for="admin-profile-name">Nama</label>
                <input class="input" id="admin-profile-name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
            </div>
            <div>
                <label class="label" for="admin-profile-email">Email</label>
                <input class="input" id="admin-profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            </div>
            <button class="btn-primary w-full" type="submit">Simpan Profil</button>
        </form>
    </section>

    <section class="panel p-6">
        <h2 class="text-xl font-black">Keamanan Akun</h2>
        <p class="mt-1 text-sm text-slate-500" id="admin-password-help">Gunakan password unik minimal delapan karakter.</p>
        <form method="POST" action="{{ route('admin.profile.password') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="label" for="admin-current-password">Password saat ini</label>
                <input class="input" id="admin-current-password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div>
                <label class="label" for="admin-new-password">Password baru</label>
                <input class="input" id="admin-new-password" type="password" name="password" required autocomplete="new-password" aria-describedby="admin-password-help">
            </div>
            <div>
                <label class="label" for="admin-password-confirmation">Konfirmasi password</label>
                <input class="input" id="admin-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn-primary w-full" type="submit">Ubah Password</button>
        </form>
    </section>
</div>
@endsection
