@extends('layouts.admin')
@section('title', 'Dashboard Admin — Hadirin')
@section('page-title', 'Kehadiran hari ini')
@section('page-subtitle', 'Pantau kondisi absensi dan tindak lanjut hari ini.')
@section('content')
@php
    $statCards = [
        [
            'key' => 'scheduled',
            'label' => 'Pegawai terjadwal',
            'icon' => 'calendar',
            'valueTone' => 'text-stone-900',
            'iconTone' => 'bg-stone-100 text-stone-600',
        ],
        [
            'key' => 'present',
            'label' => 'Sudah hadir',
            'icon' => 'check',
            'valueTone' => 'text-emerald-700',
            'iconTone' => 'bg-emerald-50 text-emerald-700',
        ],
        [
            'key' => 'late',
            'label' => 'Terlambat',
            'icon' => 'clock',
            'valueTone' => 'text-amber-700',
            'iconTone' => 'bg-amber-50 text-amber-700',
        ],
        [
            'key' => 'leave',
            'label' => 'Izin atau sakit',
            'icon' => 'leave',
            'valueTone' => 'text-violet-700',
            'iconTone' => 'bg-violet-50 text-violet-700',
        ],
        [
            'key' => 'absent',
            'label' => 'Belum absen',
            'icon' => 'alert',
            'valueTone' => 'text-rose-700',
            'iconTone' => 'bg-rose-50 text-rose-700',
        ],
        [
            'key' => 'pending',
            'label' => 'Perlu ditinjau',
            'icon' => 'review',
            'valueTone' => 'text-orange-700',
            'iconTone' => 'bg-orange-50 text-orange-700',
        ],
    ];

    $activityStatus = [
        'present' => [
            'label' => 'Hadir',
            'badge' => 'badge-success',
        ],
        'late' => [
            'label' => 'Terlambat',
            'badge' => 'badge-warning',
        ],
        'pending_review' => [
            'label' => 'Perlu ditinjau',
            'badge' => 'badge-warning',
        ],
        'invalid' => [
            'label' => 'Tidak valid',
            'badge' => 'badge-danger',
        ],
        'absent' => [
            'label' => 'Alpa',
            'badge' => 'badge-danger',
        ],
        'permission' => [
            'label' => 'Izin',
            'badge' => 'inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-200',
        ],
        'sick' => [
            'label' => 'Sakit',
            'badge' => 'inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200',
        ],
        'leave' => [
            'label' => 'Cuti',
            'badge' => 'inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-200',
        ],
    ];
@endphp

<div data-admin-dashboard>
    <section
        data-admin-stats
        aria-label="Ringkasan kehadiran hari ini"
        class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6"
    >
        @foreach($statCards as $card)
            <article
                class="rounded-2xl border border-stone-200 bg-white p-4
                       shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-medium leading-5 text-stone-500">
                            {{ $card['label'] }}
                        </p>
                        <p
                            class="mt-2 text-2xl font-bold
                                   {{ $card['valueTone'] }}"
                        >
                            {{ $stats[$card['key']] }}
                        </p>
                    </div>

                    <span
                        class="grid h-9 w-9 shrink-0 place-items-center
                               rounded-xl {{ $card['iconTone'] }}"
                    >
                        <x-employee-icon
                            :name="$card['icon']"
                            class="h-4.5 w-4.5"
                        />
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <div
        class="mt-6 grid gap-6
               xl:grid-cols-[minmax(0,1.5fr)_minmax(18rem,.7fr)]"
    >
        <section
            data-admin-activity
            class="table-shell order-2 xl:order-1"
        >
            <div
                class="flex flex-col gap-3 border-b border-stone-200 p-5
                       sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-stone-500">
                        Hari ini
                    </p>
                    <h2 class="mt-0.5 text-lg font-bold text-stone-900">
                        Aktivitas absensi terbaru
                    </h2>
                </div>

                <a
                    class="inline-flex items-center gap-1 text-sm
                           font-semibold text-violet-700 hover:text-violet-900"
                    href="{{ route('admin.attendance.index', [
                        'date' => now(config('app.timezone'))->toDateString(),
                    ]) }}"
                >
                    Lihat semua
                    <x-employee-icon
                        name="chevron-right"
                        class="h-4 w-4"
                    />
                </a>
            </div>

            {{-- Ringkasan berbentuk daftar untuk layar kecil. --}}
            <div
                data-admin-activity-mobile
                class="divide-y divide-stone-100 md:hidden"
            >
                @forelse($recent as $item)
                    @php
                        $itemStatus = $activityStatus[$item->status]
                            ?? [
                                'label' => ucfirst(str_replace('_', ' ', $item->status)),
                                'badge' => 'badge-neutral',
                            ];
                    @endphp

                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-stone-900">
                                    {{ $item->employee->user->name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-stone-500">
                                    {{ $item->employee->department?->name ?? 'Tanpa unit kerja' }}
                                </p>
                            </div>
                            <span class="shrink-0 {{ $itemStatus['badge'] }}">
                                {{ $itemStatus['label'] }}
                            </span>
                        </div>

                        <div
                            class="mt-3 grid grid-cols-2 divide-x
                                   divide-stone-200 rounded-xl bg-stone-50 p-3"
                        >
                            <div class="pr-3">
                                <p class="text-xs text-stone-500">Masuk</p>
                                <p class="mt-1 text-sm font-semibold">
                                    {{ $item->checked_in_at?->format('H:i') ?? 'Belum tercatat' }}
                                </p>
                            </div>
                            <div class="pl-3">
                                <p class="text-xs text-stone-500">Pulang</p>
                                <p class="mt-1 text-sm font-semibold">
                                    {{ $item->checked_out_at?->format('H:i') ?? 'Belum tercatat' }}
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 flex items-center gap-2 text-xs text-stone-500">
                            <x-employee-icon name="pin" class="h-4 w-4" />
                            <span class="truncate">
                                {{ $item->schedule?->workLocation?->name ?? 'Lokasi tidak tersedia' }}
                            </span>
                        </p>
                    </article>
                @empty
                    <p class="p-8 text-center text-sm text-stone-500">
                        Belum ada aktivitas hari ini.
                    </p>
                @endforelse
            </div>

            {{-- Tabel tetap dipertahankan untuk tablet dan desktop. --}}
            <div
                data-admin-activity-table
                class="hidden overflow-x-auto md:block"
            >
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="p-4">Pegawai</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($recent as $item)
                            @php
                                $itemStatus = $activityStatus[$item->status]
                                    ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $item->status)),
                                        'badge' => 'badge-neutral',
                                    ];
                            @endphp
                            <tr class="hover:bg-stone-50">
                                <td class="p-4">
                                    <p class="font-semibold text-stone-900">
                                        {{ $item->employee->user->name }}
                                    </p>
                                    <p class="text-xs text-stone-500">
                                        {{ $item->employee->department?->name ?? 'Tanpa unit kerja' }}
                                    </p>
                                </td>
                                <td>
                                    {{ $item->checked_in_at?->format('H:i') ?? 'Belum' }}
                                </td>
                                <td>
                                    {{ $item->checked_out_at?->format('H:i') ?? 'Belum' }}
                                </td>
                                <td>
                                    {{ $item->schedule?->workLocation?->name ?? 'Tidak tersedia' }}
                                </td>
                                <td>
                                    <span class="{{ $itemStatus['badge'] }}">
                                        {{ $itemStatus['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="p-10 text-center text-stone-500"
                                >
                                    Belum ada aktivitas hari ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="order-1 space-y-4 xl:order-2">
            <section
                data-admin-action
                class="rounded-2xl border border-stone-200 bg-white p-5
                       shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="grid h-10 w-10 shrink-0 place-items-center
                               rounded-xl bg-amber-50 text-amber-700"
                    >
                        <x-employee-icon name="review" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-stone-900">
                            Perlu tindakan
                        </p>
                        <p class="mt-1 text-sm leading-6 text-stone-600">
                            <strong>{{ $stats['pending_leave'] }} pengajuan</strong>
                            menunggu keputusan Anda.
                        </p>
                    </div>
                </div>

                <a
                    href="{{ route('admin.leave.index', ['status' => 'pending']) }}"
                    class="btn-secondary mt-4 w-full text-sm"
                >
                    Tinjau pengajuan
                </a>
            </section>

            <section
                data-admin-location
                class="rounded-2xl border border-stone-800 bg-stone-900
                       p-5 text-white shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="grid h-10 w-10 shrink-0 place-items-center
                               rounded-xl bg-white/10 text-violet-200"
                    >
                        <x-employee-icon name="pin" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-stone-300">
                            Lokasi utama
                        </p>
                        <h2 class="mt-1 break-words text-lg font-bold">
                            {{ $primaryLocation?->name ?? 'Belum ada lokasi aktif' }}
                        </h2>
                    </div>
                </div>

                <p class="mt-3 text-sm leading-6 text-stone-400">
                    {{ $primaryLocation
                        ? 'Pegawai dapat absen dalam radius '.$primaryLocation->radius_meters.' m.'
                        : 'Aktifkan lokasi kerja sebelum absensi digunakan.' }}
                </p>

                <a
                    class="mt-4 inline-flex items-center gap-1 text-sm
                           font-semibold text-violet-200 hover:text-white"
                    href="{{ route('admin.locations.index') }}"
                >
                    Kelola lokasi
                    <x-employee-icon
                        name="chevron-right"
                        class="h-4 w-4"
                    />
                </a>
            </section>
        </aside>
    </div>

    <section
        data-admin-weekly
        class="panel mt-6 p-5 sm:p-6"
    >
        <div>
            <p class="text-sm font-medium text-stone-500">
                7 hari terakhir
            </p>
            <h2 class="mt-0.5 text-lg font-bold text-stone-900">
                Kehadiran harian
            </h2>
        </div>

        <div class="mt-6 flex h-48 items-end gap-2 sm:gap-3">
            @php
                $maxAttendance = max(1, (int)$weekly->max('total'));
            @endphp

            @foreach($weekly as $day)
                @php
                    $dayDate = \Carbon\Carbon::parse(
                        $day->attendance_date
                    )->locale('id');
                @endphp
                <div
                    class="flex min-w-0 flex-1 flex-col items-center gap-2"
                    aria-label="{{ $dayDate->translatedFormat('l, d F') }}: {{ $day->total }} hadir"
                    title="{{ $dayDate->translatedFormat('l, d F') }}: {{ $day->total }} hadir"
                >
                    <span class="text-xs font-semibold text-stone-700">
                        {{ $day->total }}
                    </span>
                    <div
                        class="w-full rounded-t-sm bg-violet-600"
                        style="height: {{ max(
                            8,
                            ($day->total / $maxAttendance) * 130
                        ) }}px"
                    ></div>
                    <span class="truncate text-[10px] text-stone-500 sm:text-xs">
                        {{ $dayDate->translatedFormat('D') }}
                    </span>
                </div>
            @endforeach

            @if($weekly->isEmpty())
                <p class="m-auto text-sm text-stone-500">
                    Belum ada catatan kehadiran dalam 7 hari terakhir.
                </p>
            @endif
        </div>
    </section>
</div>
@endsection
