<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Portal Pegawai — Hadirin')</title>

    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('favicon.svg') }}?v=2"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="employee-background min-h-dvh"
>
@php
    $employeeNavigation = [
        [
            'label' => 'Beranda',
            'route' => 'employee.dashboard',
            'pattern' => 'employee.dashboard',
            'icon' => 'home',
            'parameters' => [],
        ],
        [
            'label' => 'Riwayat',
            'route' => 'employee.attendance.history',
            'pattern' => 'employee.attendance.history',
            'icon' => 'history',
            'parameters' => [],
        ],
        [
            'label' => $attendanceNavigationLabel,
            'route' => 'employee.attendance.capture',
            'pattern' => 'employee.attendance.capture',
            'icon' => 'attendance',
            'parameters' => ['type' => $attendanceNavigationType],
        ],
        [
            'label' => 'Izin',
            'route' => 'employee.leave.index',
            'pattern' => 'employee.leave.*',
            'icon' => 'leave',
            'parameters' => [],
        ],
        [
            'label' => 'Profil',
            'route' => 'employee.profile.edit',
            'pattern' => 'employee.profile.*',
            'icon' => 'user',
            'parameters' => [],
        ],
    ];
@endphp

<div
    class="min-h-dvh lg:grid
           lg:grid-cols-[15rem_minmax(0,1fr)]
           xl:grid-cols-[17.5rem_minmax(0,1fr)]"
>
    {{-- Navigasi khusus desktop --}}
    <aside
        data-desktop-sidebar
        class="sticky top-0 hidden h-dvh flex-col border-r
               border-slate-800 bg-slate-950 p-5 text-white lg:flex"
    >
        <a
            href="{{ route('employee.dashboard') }}"
            class="flex items-center gap-3 rounded-xl px-2 py-2"
        >
            <span
                class="grid h-11 w-11 place-items-center rounded-xl
                       bg-violet-600 text-lg font-bold text-white"
            >
                H
            </span>

            <span class="min-w-0">
                <span class="block text-lg font-bold tracking-tight">
                    Hadirin
                </span>
                <span class="block truncate text-xs text-slate-300">
                    SMKN 4 Pariaman
                </span>
            </span>
        </a>

        <div
            class="mt-6 rounded-2xl border border-slate-800
                   bg-slate-900 p-3.5 text-white"
        >
            <div class="flex items-center gap-3">
                <span
                    class="grid h-10 w-10 shrink-0 place-items-center
                           rounded-xl bg-violet-600 font-bold"
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-300">
                        Portal Pegawai
                    </p>
                </div>
            </div>
        </div>

        <nav
            aria-label="Navigasi utama pegawai"
            class="mt-6 space-y-1.5"
        >
            @foreach($employeeNavigation as $item)
                @php
                    $isActiveNavigation = request()->routeIs(
                        $item['pattern']
                    );
                @endphp

                <a
                    href="{{ route($item['route'], $item['parameters']) }}"
                    @if($isActiveNavigation) aria-current="page" @endif
                    class="group flex items-center gap-3 rounded-xl
                           px-3 py-3 text-sm font-semibold transition
                           focus-visible:outline-none focus-visible:ring-2
                           focus-visible:ring-violet-300
                           {{ $isActiveNavigation
                                ? 'bg-white/10 text-white ring-1 ring-inset ring-white/10'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                >
                    <span
                        class="grid h-5 w-5 shrink-0 place-items-center
                               transition
                               {{ $isActiveNavigation
                                    ? 'text-violet-300'
                                    : 'text-slate-400 group-hover:text-slate-200' }}"
                        aria-hidden="true"
                    >
                        <x-employee-icon :name="$item['icon']" />
                    </span>

                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-white/10 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center gap-3 rounded-xl
                           px-3 py-3 text-left text-sm font-semibold
                           text-slate-300 transition hover:bg-red-500/10
                           hover:text-red-200 focus-visible:outline-none
                           focus-visible:ring-2 focus-visible:ring-red-300"
                >
                    <span
                        class="grid h-5 w-5 place-items-center"
                        aria-hidden="true"
                    >
                        <x-employee-icon name="logout" />
                    </span>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="min-w-0">
        {{-- Header mobile dan tablet --}}
        <header
            data-mobile-header
            class="sticky top-0 z-30 border-b border-slate-200
                   bg-white/95 backdrop-blur-xl lg:hidden"
        >
            <div
                class="mx-auto flex max-w-lg items-center justify-between
                       px-5 py-3.5 md:max-w-3xl md:px-6"
            >
                <a
                    href="{{ route('employee.dashboard') }}"
                    class="flex min-w-0 items-center gap-3"
                >
                    <span
                        class="grid h-10 w-10 shrink-0 place-items-center
                               rounded-xl bg-violet-600 text-base font-bold
                               text-white"
                    >
                        H
                    </span>

                    <span class="min-w-0">
                        <span class="block font-bold tracking-tight">
                            Hadirin
                        </span>
                        <span class="block truncate text-xs text-slate-500">
                            SMKN 4 Pariaman
                        </span>
                    </span>
                </a>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('employee.profile.edit') }}"
                        class="grid h-11 w-11 place-items-center rounded-full
                               bg-slate-900 text-sm font-semibold text-white"
                        aria-label="Buka profil"
                        title="Profil"
                    >
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            aria-label="Keluar dari sistem"
                            title="Keluar"
                            class="grid h-11 w-11 place-items-center
                                   rounded-xl border border-slate-200
                                   bg-white text-slate-500 transition
                                   hover:bg-rose-50 hover:text-rose-800
                                   focus-visible:outline-none
                                   focus-visible:ring-4 focus-visible:ring-rose-100"
                        >
                            <x-employee-icon name="logout" />
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Topbar khusus desktop --}}
        <header
            data-desktop-topbar
            class="sticky top-0 z-20 hidden h-16 items-center
                   justify-between border-b border-slate-200 bg-white/95
                   px-8 backdrop-blur-xl lg:flex xl:px-10"
        >
            <div>
                <p class="text-base font-bold text-slate-900">
                    @yield('page-title', 'Portal Pegawai')
                </p>
                <p class="mt-0.5 text-sm text-slate-500">
                    @yield('page-subtitle', 'Kelola kehadiran dan akun Anda.')
                </p>
            </div>

            <div class="text-right">
                <p class="text-sm font-semibold text-slate-800">
                    {{ now(config('app.timezone'))->locale('id')->translatedFormat('l, d F Y') }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500">
                    Waktu Indonesia Barat
                </p>
            </div>
        </header>

        <main
            data-employee-main
            class="mx-auto w-full max-w-7xl px-5 py-5 pb-28
                   md:px-6 md:py-6 lg:px-8 lg:py-8 lg:pb-10 xl:px-10"
        >
            <div class="@yield('content-width', 'mx-auto max-w-3xl')">
                @if(session('success'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 4000)"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="-translate-y-3 opacity-0"
                        x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="translate-y-0 opacity-100"
                        x-transition:leave-end="-translate-y-3 opacity-0"
                        role="status"
                        class="relative z-[60] mb-4 flex items-center justify-between gap-4 rounded-2xl border
                               border-emerald-200 bg-emerald-50 p-4 text-sm
                               font-semibold text-emerald-700"
                    >
                        <span>{{ session('success') }}</span>
                        <button type="button" class="shrink-0 text-lg leading-none" @click="show = false" aria-label="Tutup notifikasi">&times;</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div
                        role="alert"
                        class="relative z-[60] mb-4 rounded-2xl border
                               border-amber-200 bg-amber-50 p-4 text-sm
                               font-semibold text-amber-800"
                    >
                        <span class="font-bold">Perhatian:</span>
                        {{ session('warning') }}
                    </div>
                @endif

                @if(is_object($errors) && $errors->any())
                    <div
                        role="alert"
                        class="relative z-[60] mb-4 rounded-2xl border
                               border-red-200 bg-red-50 p-4 text-sm
                               font-semibold text-red-700"
                    >
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    {{-- Navigasi khusus mobile dan tablet --}}
    <nav
        data-mobile-navigation
        aria-label="Navigasi bawah pegawai"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200
               bg-white/95 pb-[max(.75rem,env(safe-area-inset-bottom))]
               pt-2 shadow-[0_-8px_24px_-20px_rgba(15,23,42,.3)]
               backdrop-blur-xl lg:hidden"
    >
        <div
            class="mx-auto grid max-w-lg grid-cols-5 gap-1 px-3
                   text-center text-[11px] font-medium md:max-w-3xl"
        >
            <a
                href="{{ route('employee.dashboard') }}"
                @if(request()->routeIs('employee.dashboard'))
                    aria-current="page"
                @endif
                class="rounded-xl px-1 py-2
                       {{ request()->routeIs('employee.dashboard')
                            ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-100'
                            : 'text-slate-500' }}"
            >
                <x-employee-icon name="home" class="mx-auto mb-1 h-5 w-5" />
                Beranda
            </a>

            <a
                href="{{ route('employee.attendance.history') }}"
                @if(request()->routeIs('employee.attendance.history'))
                    aria-current="page"
                @endif
                class="rounded-xl px-1 py-2
                       {{ request()->routeIs('employee.attendance.history')
                            ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-100'
                            : 'text-slate-500' }}"
            >
                <x-employee-icon name="history" class="mx-auto mb-1 h-5 w-5" />
                Riwayat
            </a>

            <a
                href="{{ route('employee.attendance.capture', ['type' => $attendanceNavigationType]) }}"
                @if(request()->routeIs('employee.attendance.capture'))
                    aria-current="page"
                @endif
                class="-mt-3 rounded-xl px-1 py-1 text-violet-700"
            >
                <span
                    class="mx-auto grid h-12 w-12 place-items-center
                           rounded-2xl bg-violet-700 text-white shadow-md
                           shadow-violet-700/20 ring-4 ring-white"
                    aria-hidden="true"
                >
                    <x-employee-icon name="attendance" class="h-6 w-6" />
                </span>
                <span class="mt-1 block">{{ $attendanceNavigationLabel }}</span>
            </a>

            <a
                href="{{ route('employee.leave.index') }}"
                @if(request()->routeIs('employee.leave.*'))
                    aria-current="page"
                @endif
                class="rounded-xl px-1 py-2
                       {{ request()->routeIs('employee.leave.*')
                            ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-100'
                            : 'text-slate-500' }}"
            >
                <x-employee-icon name="leave" class="mx-auto mb-1 h-5 w-5" />
                Izin
            </a>

            <a
                href="{{ route('employee.profile.edit') }}"
                @if(request()->routeIs('employee.profile.*'))
                    aria-current="page"
                @endif
                class="rounded-xl px-1 py-2
                       {{ request()->routeIs('employee.profile.*')
                            ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-100'
                            : 'text-slate-500' }}"
            >
                <x-employee-icon name="user" class="mx-auto mb-1 h-5 w-5" />
                Profil
            </a>
        </div>
    </nav>
</div>
</body>
</html>
