@extends('layouts.employee')
@section('title', 'Beranda — Hadirin')
@section('page-title', 'Beranda')
@section('page-subtitle', 'Ringkasan kehadiran Anda hari ini.')
@section('content-width', 'max-w-none')
@section('content')
@php
    $statusLabel = match($attendance?->status) {
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'pending_review' => 'Perlu Ditinjau',
        'permission' => 'Izin',
        'sick' => 'Sakit',
        'leave' => 'Cuti',
        'absent' => 'Alpa',
        default => 'Belum Absen',
    };

    $statusBadgeTone = match($attendance?->status) {
        'present' => 'bg-emerald-400/15 text-emerald-100 ring-emerald-300/30',
        'late', 'pending_review' => 'bg-amber-400/15 text-amber-100 ring-amber-300/30',
        'permission', 'leave' => 'bg-violet-400/15 text-violet-100 ring-violet-300/30',
        'sick', 'absent' => 'bg-rose-400/15 text-rose-100 ring-rose-300/30',
        default => 'bg-white/10 text-slate-200 ring-white/15',
    };

    $localNow = now(config('app.timezone'))->locale('id');
    $greeting = match(true) {
        $localNow->hour < 11 => 'Selamat pagi',
        $localNow->hour < 15 => 'Selamat siang',
        $localNow->hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $positionName = $employee->position?->name ?? 'Pegawai';
    $departmentName = $employee->department?->name;
    $roleDescription = $positionName;

    if (
        $departmentName
        && !\Illuminate\Support\Str::contains(
            \Illuminate\Support\Str::lower($positionName),
            \Illuminate\Support\Str::lower($departmentName)
        )
    ) {
        $roleDescription .= ' · '.$departmentName;
    }

    $isExcusedAbsence = in_array(
        $attendance?->status,
        ['permission', 'sick', 'leave', 'absent'],
        true
    );

    $attendanceAction = null;

    if (
        !$isExcusedAbsence
        && $schedule
        && $schedule->status === 'working'
    ) {
        if (!$attendance?->checked_in_at) {
            $attendanceAction = [
                'label' => 'Absen Masuk',
                'title' => 'Siap memulai hari kerja?',
                'description' => 'Verifikasi lokasi dan foto untuk mencatat waktu masuk.',
                'url' => route('employee.attendance.capture', [
                    'type' => 'check_in',
                ]),
            ];
        } elseif (!$attendance?->checked_out_at) {
            $attendanceAction = [
                'label' => 'Absen Pulang',
                'title' => 'Pekerjaan hari ini selesai?',
                'description' => 'Catat waktu pulang sebelum meninggalkan lokasi kerja.',
                'url' => route('employee.attendance.capture', [
                    'type' => 'check_out',
                ]),
            ];
        } else {
            $attendanceAction = ['complete' => true];
        }
    }
@endphp

<div
    data-employee-dashboard
    class="grid gap-4 lg:grid-cols-12 lg:items-start lg:gap-6"
>
    {{-- Kolom utama desktop. Wrapper menjadi transparan pada mobile. --}}
    <div
        class="contents lg:col-span-7 lg:block lg:space-y-6
               xl:col-span-8"
    >
        <section
            data-dashboard-hero
            class="relative order-1 overflow-hidden rounded-2xl
                   border border-stone-800 bg-stone-900 p-6 text-white
                   shadow-sm lg:flex lg:min-h-[17rem] lg:flex-col lg:p-7"
        >
            <div
                class="flex items-start justify-between gap-4"
            >
                <div class="min-w-0">
                    <p class="text-sm text-stone-300 lg:hidden">
                        {{ $localNow->translatedFormat('l, d F Y') }}
                    </p>

                    <p
                        class="hidden text-sm font-medium text-stone-300
                               lg:block"
                    >
                        Ringkasan kehadiran hari ini
                    </p>

                    <h1
                        class="mt-2 text-2xl font-bold tracking-tight
                               lg:text-3xl"
                    >
                        {{ $greeting }},
                        {{ explode(' ', $employee->user->name)[0] }}
                    </h1>

                    <p
                        class="mt-1 break-words text-sm leading-6
                               text-stone-300"
                    >
                        {{ $roleDescription }}
                    </p>

                    <span
                        data-dashboard-status
                        class="mt-3 inline-flex items-center rounded-full
                               px-3 py-1 text-xs font-semibold ring-1
                               ring-inset {{ $statusBadgeTone }}"
                    >
                        Status: {{ $statusLabel }}
                    </span>
                </div>

                <div class="shrink-0">
                    <span
                        class="rounded-full bg-white/10 px-3 py-1
                               text-xs font-medium text-stone-200 ring-1
                               ring-inset ring-white/15"
                    >
                        WIB
                    </span>
                </div>
            </div>

            <div
                class="mt-8 lg:mt-auto lg:pt-8"
            >
                <div
                    x-data="{ time: '--:--:--' }"
                    x-init="
                        const tick = () => time = new Intl.DateTimeFormat(
                            'id-ID',
                            {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false,
                                timeZone: 'Asia/Jakarta'
                            }
                        ).format(new Date());
                        tick();
                        setInterval(tick, 1000);
                    "
                >
                    <p
                        class="text-sm font-medium text-stone-300"
                    >
                        Waktu sekarang
                    </p>

                    <p
                        aria-live="off"
                        class="mt-1 text-4xl font-bold tracking-tight
                               tabular-nums lg:text-5xl"
                        x-text="time"
                    >
                        --:--:--
                    </p>
                </div>

            </div>
        </section>

        @if($isExcusedAbsence)
            <section
                class="order-2 rounded-2xl border border-stone-200
                       bg-white p-4 shadow-sm lg:p-5"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="grid h-9 w-9 shrink-0 place-items-center
                               rounded-xl bg-emerald-50 text-emerald-700"
                    >
                        <x-employee-icon name="check" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-stone-900">
                            Hari ini tercatat {{ strtolower($statusLabel) }}.
                        </p>
                        <p class="mt-1 text-sm leading-6 text-stone-600">
                            Anda tidak perlu melakukan absensi masuk atau pulang.
                        </p>
                    </div>
                </div>
            </section>
        @endif

        @if($attendanceAction)
            <section class="order-2">
                @if(isset($attendanceAction['complete']))
                    <div
                        class="rounded-2xl border border-emerald-200
                               bg-emerald-50
                               p-4 text-center text-sm font-bold
                               text-emerald-800 lg:p-5"
                    >
                        Absensi hari ini sudah lengkap.
                    </div>
                @else
                    <div
                        class="lg:flex lg:items-center lg:justify-between
                               lg:gap-6 lg:rounded-2xl lg:border
                               lg:border-stone-200 lg:bg-white lg:p-5
                               lg:shadow-sm"
                    >
                        <div class="hidden lg:block">
                            <p class="font-semibold text-stone-900">
                                {{ $attendanceAction['title'] }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $attendanceAction['description'] }}
                            </p>
                        </div>

                        <a
                            href="{{ $attendanceAction['url'] }}"
                            class="btn-primary w-full rounded-xl bg-violet-700
                                   py-3.5 text-base shadow-sm
                                   hover:translate-y-0 hover:bg-violet-800
                                   lg:w-auto lg:min-w-48 lg:shrink-0"
                        >
                            <x-employee-icon name="attendance" />
                            {{ $attendanceAction['label'] }}
                        </a>
                    </div>
                @endif
            </section>
        @endif

        @if($recent->isNotEmpty())
            <section class="order-6">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-stone-900">
                            Aktivitas terakhir
                        </h2>
                        <p class="mt-0.5 hidden text-sm text-stone-500 lg:block">
                            Catatan kehadiran terbaru Anda.
                        </p>
                    </div>

                    <a
                        class="shrink-0 text-sm font-semibold text-violet-700
                               transition hover:text-violet-900"
                        href="{{ route('employee.attendance.history') }}"
                    >
                        Lihat semua
                    </a>
                </div>

                <div
                    class="divide-y divide-stone-100 overflow-hidden
                           rounded-2xl border border-stone-200 bg-white
                           shadow-sm"
                >
                    @foreach($recent as $item)
                        @php
                            $recentBadgeClass = match($item->status) {
                                'late' => 'badge-warning',
                                'invalid', 'absent' => 'badge-danger',
                                'permission', 'leave' => 'inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-200',
                                'sick' => 'inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200',
                                'pending_review' => 'badge-warning',
                                default => 'badge-success',
                            };
                            $recentDotClass = match($item->status) {
                                'late' => 'bg-amber-400',
                                'invalid', 'absent' => 'bg-red-400',
                                'permission', 'leave' => 'bg-violet-400',
                                'sick' => 'bg-rose-400',
                                'pending_review' => 'bg-amber-400',
                                default => 'bg-emerald-400',
                            };
                            $recentStatusLabel = match($item->status) {
                                'present' => 'Hadir',
                                'late' => 'Terlambat',
                                'permission' => 'Izin',
                                'sick' => 'Sakit',
                                'leave' => 'Cuti',
                                'absent' => 'Alpa',
                                'pending_review' => 'Perlu Ditinjau',
                                default => ucfirst(str_replace('_', ' ', $item->status)),
                            };
                        @endphp

                        <div
                            class="flex items-center justify-between gap-4 p-4
                                   transition hover:bg-stone-50 lg:px-5 lg:py-4"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <span
                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full
                                           {{ $recentDotClass }}"
                                    aria-hidden="true"
                                ></span>

                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-stone-900">
                                        {{ $item->attendance_date->locale('id')->translatedFormat('d M Y') }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-stone-500">
                                        @if($item->checked_in_at || $item->checked_out_at)
                                            {{ $item->checked_in_at?->format('H:i') ?? 'Belum masuk' }}–{{ $item->checked_out_at?->format('H:i') ?? 'Belum pulang' }}
                                        @else
                                            Tidak ada pencatatan jam
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <span
                                class="shrink-0 {{ $recentBadgeClass }}"
                            >
                                {{ $recentStatusLabel }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Ringkasan desktop. Pada mobile item kembali ke urutan semula. --}}
    <aside
        class="contents lg:col-span-5 lg:block lg:space-y-6
               xl:col-span-4"
    >
        <section
            data-dashboard-schedule
            class="order-3 rounded-2xl border border-stone-200 bg-white
                   p-5 shadow-sm lg:p-6"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="text-sm font-semibold text-stone-500"
                    >
                        Jadwal hari ini
                    </p>

                    <h2 class="mt-1 break-words text-xl font-bold text-stone-900">
                        {{ $schedule?->shift?->name ?? 'Tidak ada jadwal' }}
                    </h2>
                </div>

                <span
                    class="grid h-10 w-10 shrink-0 place-items-center
                           rounded-xl bg-violet-50 text-violet-700"
                    aria-hidden="true"
                >
                    <x-employee-icon name="clock" />
                </span>
            </div>

            @if($schedule)
                <div
                    class="mt-4 grid grid-cols-2 divide-x divide-stone-200
                           overflow-hidden rounded-xl border border-stone-200
                           bg-stone-50"
                >
                    <div
                        class="min-w-0 p-4"
                    >
                        <p class="text-xs font-medium text-stone-500">
                            Jam kerja
                        </p>
                        <p class="mt-1 truncate font-bold text-stone-900">
                            {{ substr($schedule->shift->start_time, 0, 5) }}–{{ substr($schedule->shift->end_time, 0, 5) }}
                        </p>
                    </div>

                    <div
                        class="min-w-0 p-4"
                    >
                        <p class="text-xs font-medium text-stone-500">
                            Radius absensi
                        </p>
                        <p class="mt-1 truncate font-bold text-stone-900">
                            {{ $schedule->workLocation->radius_meters }} m
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 flex items-start gap-3 border-t
                           border-stone-200 pt-4"
                >
                    <span
                        class="grid h-9 w-9 shrink-0 place-items-center
                               rounded-xl bg-violet-50 text-violet-700"
                        aria-hidden="true"
                    >
                        <x-employee-icon name="pin" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0">
                        <p class="break-words font-semibold text-stone-900">
                            {{ $schedule->workLocation->name }}
                        </p>
                        <p
                            class="mt-1 break-words text-xs leading-5
                                   text-stone-600"
                        >
                            {{ $schedule->workLocation->address }}
                        </p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Hubungi administrator apabila seharusnya Anda memiliki
                    jadwal kerja.
                </p>
            @endif
        </section>

        <section
            class="order-4 grid grid-cols-2 divide-x divide-stone-200
                   overflow-hidden rounded-2xl border border-stone-200
                   bg-white shadow-sm"
        >
            <div
                data-stat-tone="check-in"
                class="min-w-0 p-4 lg:p-5"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                        aria-hidden="true"
                    ></span>
                    <p class="text-xs font-medium text-stone-600">
                        Waktu masuk
                    </p>
                </div>
                <p class="mt-2 text-lg font-bold text-stone-900">
                    {{ $attendance?->checked_in_at?->format('H:i') ?? 'Belum tercatat' }}
                </p>
            </div>

            <div
                data-stat-tone="check-out"
                class="min-w-0 p-4 lg:p-5"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-violet-500"
                        aria-hidden="true"
                    ></span>
                    <p class="text-xs font-medium text-stone-600">
                        Waktu pulang
                    </p>
                </div>
                <p class="mt-2 text-lg font-bold text-stone-900">
                    {{ $attendance?->checked_out_at?->format('H:i') ?? 'Belum tercatat' }}
                </p>
            </div>
        </section>

        <div class="order-5">
            <div
                class="mb-3 hidden items-end justify-between gap-4 lg:flex"
            >
                <div>
                    <h2 class="text-lg font-bold text-stone-900">
                        Ringkasan bulan ini
                    </h2>
                </div>
            </div>

            <section
                class="grid grid-cols-3 divide-x divide-stone-200
                       overflow-hidden rounded-2xl border border-stone-200
                       bg-white text-center shadow-sm lg:grid-cols-1
                       lg:divide-x-0 lg:divide-y lg:text-left"
            >
                <div
                    data-monthly-tone="present"
                    class="p-4 lg:flex lg:items-center
                           lg:justify-between lg:py-3.5"
                >
                    <p
                        class="text-2xl font-bold text-emerald-700
                               lg:order-2 lg:text-xl"
                    >
                        {{ (int)($monthly->present_count ?? 0) }}
                    </p>
                    <p
                        class="mt-1 text-[11px] text-slate-600
                               lg:order-1 lg:mt-0 lg:text-xs"
                    >
                        Hadir bulan ini
                    </p>
                </div>

                <div
                    data-monthly-tone="late"
                    class="p-4 lg:flex lg:items-center
                           lg:justify-between lg:py-3.5"
                >
                    <p
                        class="text-2xl font-bold text-amber-700
                               lg:order-2 lg:text-xl"
                    >
                        {{ (int)($monthly->late_count ?? 0) }}
                    </p>
                    <p
                        class="mt-1 text-[11px] text-slate-600
                               lg:order-1 lg:mt-0 lg:text-xs"
                    >
                        Terlambat
                    </p>
                </div>

                <div
                    data-monthly-tone="leave"
                    class="p-4 lg:flex lg:items-center
                           lg:justify-between lg:py-3.5"
                >
                    <p
                        class="text-2xl font-bold text-violet-700
                               lg:order-2 lg:text-xl"
                    >
                        {{ $pendingLeave }}
                    </p>
                    <p
                        class="mt-1 text-[11px] text-slate-600
                               lg:order-1 lg:mt-0 lg:text-xs"
                    >
                        Izin diproses
                    </p>
                </div>
            </section>
        </div>
    </aside>
</div>
@endsection
