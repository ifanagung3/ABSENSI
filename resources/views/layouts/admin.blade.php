<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Panel Administrator — Hadirin')</title>

    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('favicon.svg') }}?v=2"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-background admin-shell min-h-dvh">
@php
    $adminItems = [
        [
            'route' => 'admin.dashboard',
            'pattern' => 'admin.dashboard',
            'icon' => 'dashboard',
            'label' => 'Dashboard',
        ],
        [
            'route' => 'admin.employees.index',
            'pattern' => 'admin.employees.*',
            'icon' => 'users',
            'label' => 'Pegawai',
        ],
        [
            'route' => 'admin.master-data.index',
            'pattern' => 'admin.master-data.*',
            'icon' => 'organization',
            'label' => 'Unit & Jabatan',
        ],
        [
            'route' => 'admin.locations.index',
            'pattern' => 'admin.locations.*',
            'icon' => 'pin',
            'label' => 'Lokasi Kerja',
        ],
        [
            'route' => 'admin.shifts.index',
            'pattern' => 'admin.shifts.*',
            'icon' => 'clock',
            'label' => 'Shift',
        ],
        [
            'route' => 'admin.schedules.index',
            'pattern' => 'admin.schedules.*',
            'icon' => 'calendar',
            'label' => 'Jadwal',
        ],
        [
            'route' => 'admin.attendance.index',
            'pattern' => 'admin.attendance.*',
            'icon' => 'clipboard-check',
            'label' => 'Absensi',
        ],
        [
            'route' => 'admin.leave.index',
            'pattern' => 'admin.leave.*',
            'icon' => 'leave',
            'label' => 'Izin & Cuti',
        ],
        [
            'route' => 'admin.reports.index',
            'pattern' => 'admin.reports.*',
            'icon' => 'chart',
            'label' => 'Laporan',
        ],
        [
            'route' => 'admin.audit.index',
            'pattern' => 'admin.audit.*',
            'icon' => 'audit',
            'label' => 'Audit Log',
        ],
    ];
@endphp

<div
    x-data="{ navigationOpen: false }"
    x-effect="document.body.classList.toggle('overflow-hidden', navigationOpen)"
    @keydown.escape.window="navigationOpen = false"
    class="min-h-dvh lg:grid lg:grid-cols-[15rem_minmax(0,1fr)]
           xl:grid-cols-[17.5rem_minmax(0,1fr)]"
>
    {{-- Sidebar desktop --}}
    <aside
        data-admin-sidebar
        class="sticky top-0 hidden h-dvh min-h-0 flex-col overflow-hidden
               border-r border-slate-800 bg-slate-950 text-white lg:flex"
    >
        <div class="shrink-0 px-5 pb-4 pt-5">
            <a
                href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 border-b border-white/10 pb-5"
            >
                <span
                    class="grid h-11 w-11 shrink-0 place-items-center
                           rounded-xl bg-violet-600 text-lg font-bold text-white"
                >
                    H
                </span>

                <span class="min-w-0">
                    <span class="block truncate text-lg font-bold">
                        Hadirin
                    </span>
                    <span class="block truncate text-xs text-slate-400">
                        SMKN 4 Pariaman
                    </span>
                </span>
            </a>

            <div
                class="mt-5 flex items-center gap-3 rounded-2xl border
                       border-slate-800 bg-slate-900 p-3.5"
            >
                <span
                    class="grid h-10 w-10 shrink-0 place-items-center
                           rounded-xl bg-violet-600 text-sm font-bold"
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="mt-0.5 block text-xs text-slate-400">
                        Administrator
                    </span>
                </span>
            </div>
        </div>

        <nav
            aria-label="Navigasi utama administrator"
            class="min-h-0 flex-1 space-y-1 overflow-y-auto
                   overscroll-contain px-5 py-2 text-sm font-semibold
                   [scrollbar-color:rgba(148,163,184,.45)_transparent]
                   [scrollbar-width:thin]"
        >
            @foreach($adminItems as $item)
                @php
                    $isActiveAdminItem = request()->routeIs(
                        $item['pattern']
                    );
                @endphp

                <a
                    href="{{ route($item['route']) }}"
                    @if($isActiveAdminItem) aria-current="page" @endif
                    class="group flex min-h-11 items-center gap-3 rounded-xl
                           px-3 py-2.5 transition focus-visible:outline-none
                           focus-visible:ring-2 focus-visible:ring-violet-300
                           {{ $isActiveAdminItem
                                ? 'bg-white/10 text-white ring-1 ring-inset ring-white/10'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                >
                    <span
                        class="grid h-5 w-5 shrink-0 place-items-center
                               {{ $isActiveAdminItem
                                    ? 'text-violet-300'
                                    : 'text-slate-400 group-hover:text-slate-200' }}"
                    >
                        <x-employee-icon :name="$item['icon']" />
                    </span>
                    <span class="whitespace-nowrap">
                        {{ $item['label'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-white/10 px-5 py-4">
            <a
                href="{{ route('admin.profile.edit') }}"
                @if(request()->routeIs('admin.profile.*'))
                    aria-current="page"
                @endif
                class="flex min-h-11 items-center gap-3 rounded-xl px-3
                       py-2.5 text-sm font-semibold transition
                       {{ request()->routeIs('admin.profile.*')
                            ? 'bg-white/10 text-white ring-1 ring-inset ring-white/10'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
            >
                <x-employee-icon name="user" class="h-5 w-5" />
                Profil Admin
            </a>

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="mt-1"
                onsubmit="return confirm('Apakah Anda yakin ingin keluar dari sistem?')"
            >
                @csrf
                <button
                    type="submit"
                    class="flex min-h-11 w-full items-center gap-3 rounded-xl
                           px-3 py-2.5 text-left text-sm font-semibold
                           text-slate-300 transition hover:bg-red-500/10
                           hover:text-red-200 focus-visible:outline-none
                           focus-visible:ring-2 focus-visible:ring-red-300"
                >
                    <x-employee-icon name="logout" class="h-5 w-5" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="min-w-0">
        <header
            data-admin-header
            class="sticky top-0 z-30 h-16 border-b border-slate-200
                   bg-white/95 px-4 backdrop-blur-xl sm:px-6 lg:px-8"
        >
            <div class="flex h-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="grid h-11 w-11 shrink-0 place-items-center
                               rounded-xl border border-slate-200 bg-white
                               text-slate-700 lg:hidden"
                        aria-label="Buka navigasi administrator"
                        aria-controls="admin-mobile-menu"
                        :aria-expanded="navigationOpen.toString()"
                        @click="navigationOpen = true"
                    >
                        <x-employee-icon name="menu" />
                    </button>

                    <div class="min-w-0">
                        <h1
                            class="truncate text-base font-bold text-slate-900
                                   sm:text-lg"
                        >
                            @yield('page-title', 'Dashboard')
                        </h1>
                        <p class="hidden truncate text-sm text-slate-500 sm:block">
                            @yield('page-subtitle', 'Kelola operasional kehadiran sekolah.')
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right md:block">
                        <p class="max-w-48 truncate text-sm font-semibold">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-xs text-slate-500">Administrator</p>
                    </div>

                    <a
                        href="{{ route('admin.profile.edit') }}"
                        class="grid h-11 w-11 place-items-center rounded-full
                               bg-slate-900 text-sm font-semibold text-white
                               focus-visible:outline-none focus-visible:ring-4
                               focus-visible:ring-violet-100"
                        aria-label="Buka profil administrator"
                        title="Profil administrator"
                    >
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </a>
                </div>
            </div>
        </header>

        <main
            data-admin-main
            class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8 xl:px-10"
        >
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
                    class="relative z-[60] mb-5 flex items-center justify-between gap-4 rounded-xl border border-emerald-200
                           bg-emerald-50 p-4 text-sm font-semibold
                           text-emerald-700"
                >
                    <span>{{ session('success') }}</span>
                    <button type="button" class="shrink-0 text-lg leading-none" @click="show = false" aria-label="Tutup notifikasi">&times;</button>
                </div>
            @endif

            @if(session('warning'))
                <div
                    role="alert"
                    class="relative z-[60] mb-5 rounded-xl border border-amber-200
                           bg-amber-50 p-4 text-sm font-semibold text-amber-800"
                >
                    <span class="font-bold">Perhatian:</span>
                    {{ session('warning') }}
                </div>
            @endif

            @if(is_object($errors) && $errors->any())
                <div
                    role="alert"
                    class="relative z-[60] mb-5 rounded-xl border border-red-200 bg-red-50
                           p-4 text-sm font-semibold text-red-700"
                >
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Drawer navigasi mobile --}}
    <div
        x-show="navigationOpen"
        x-cloak
        id="admin-mobile-menu"
        data-admin-mobile-nav
        class="fixed inset-0 z-50 lg:hidden"
    >
        <button
            type="button"
            class="absolute inset-0 h-full w-full bg-slate-950/55"
            aria-label="Tutup navigasi administrator"
            @click="navigationOpen = false"
        ></button>

        <aside
            x-show="navigationOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex w-[min(20rem,88vw)]
                   flex-col bg-slate-950 text-white shadow-2xl"
        >
            <div
                class="flex shrink-0 items-center justify-between border-b
                       border-white/10 px-5 py-4"
            >
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3"
                    @click="navigationOpen = false"
                >
                    <span
                        class="grid h-10 w-10 place-items-center rounded-xl
                               bg-violet-600 font-bold"
                    >
                        H
                    </span>
                    <span>
                        <span class="block font-bold">Hadirin</span>
                        <span class="block text-xs text-slate-400">
                            Panel Administrator
                        </span>
                    </span>
                </a>

                <button
                    type="button"
                    class="grid h-11 w-11 place-items-center rounded-xl
                           bg-white/10 text-slate-200"
                    aria-label="Tutup navigasi"
                    @click="navigationOpen = false"
                >
                    <x-employee-icon name="close" />
                </button>
            </div>

            <nav
                aria-label="Navigasi mobile administrator"
                class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 py-4"
            >
                @foreach($adminItems as $item)
                    @php
                        $isActiveMobileAdminItem = request()->routeIs(
                            $item['pattern']
                        );
                    @endphp

                    <a
                        href="{{ route($item['route']) }}"
                        @if($isActiveMobileAdminItem)
                            aria-current="page"
                        @endif
                        class="flex min-h-12 items-center gap-3 rounded-xl
                               px-3 py-3 text-sm font-semibold
                               {{ $isActiveMobileAdminItem
                                    ? 'bg-white/10 text-white ring-1 ring-inset ring-white/10'
                                    : 'text-slate-300' }}"
                        @click="navigationOpen = false"
                    >
                        <x-employee-icon
                            :name="$item['icon']"
                            class="h-5 w-5 text-violet-300"
                        />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div
                class="shrink-0 border-t border-white/10 px-4 pt-3
                       pb-[max(1rem,env(safe-area-inset-bottom))]"
            >
                <a
                    href="{{ route('admin.profile.edit') }}"
                    class="flex min-h-12 items-center gap-3 rounded-xl px-3
                           py-3 text-sm font-semibold text-slate-300"
                    @click="navigationOpen = false"
                >
                    <x-employee-icon name="user" />
                    Profil Admin
                </a>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    onsubmit="return confirm('Apakah Anda yakin ingin keluar?')"
                >
                    @csrf
                    <button
                        type="submit"
                        class="flex min-h-12 w-full items-center gap-3
                               rounded-xl px-3 py-3 text-left text-sm
                               font-semibold text-red-300"
                    >
                        <x-employee-icon name="logout" />
                        Keluar
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

@stack('scripts')
</body>
</html>
