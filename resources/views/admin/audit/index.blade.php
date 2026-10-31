@extends('layouts.admin')

@section('title', 'Audit Log — Hadirin')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Telusuri aktivitas penting yang dilakukan administrator.')

@section('content')
    @php
        $fieldLabels = [
            'role' => 'Hak akses',
            'name' => 'Nama',
            'email' => 'Alamat email',
            'phone' => 'Nomor telepon',
            'employee_number' => 'Nomor pegawai',
            'is_active' => 'Status akun',
            'attendance_enabled' => 'Izin absensi',
            'radius_meters' => 'Radius absensi',
            'maximum_accuracy' => 'Batas akurasi GPS',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'attendance_status' => 'Status absensi',
            'approval_status' => 'Status persetujuan',
            'schedule_status' => 'Status jadwal',
            'work_date' => 'Tanggal kerja',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
            'reason' => 'Alasan',
            'notes' => 'Catatan',
            'password' => 'Password',
        ];

        $formatAuditValue = static function (string $key, mixed $value): string {
            if ($value === null || $value === '') {
                return 'Belum ada';
            }

            if ($key === 'password') {
                return '••••••••';
            }

            if ($key === 'role') {
                return match ((string) $value) {
                    'admin' => 'Administrator',
                    'employee' => 'Pegawai',
                    default => ucfirst((string) $value),
                };
            }

            if (in_array($key, ['is_active', 'attendance_enabled'], true)) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? 'Aktif'
                    : 'Tidak aktif';
            }

            if ($key === 'attendance_status') {
                return match ((string) $value) {
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'absent' => 'Tidak hadir',
                    'permission' => 'Izin',
                    'sick' => 'Sakit',
                    'leave' => 'Cuti',
                    'invalid' => 'Tidak valid',
                    'pending_review' => 'Perlu ditinjau',
                    default => ucfirst(str_replace('_', ' ', (string) $value)),
                };
            }

            if ($key === 'approval_status') {
                return match ((string) $value) {
                    'pending' => 'Menunggu',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst(str_replace('_', ' ', (string) $value)),
                };
            }

            if (is_bool($value)) {
                return $value ? 'Ya' : 'Tidak';
            }

            if (is_array($value)) {
                $parts = [];

                foreach ($value as $itemKey => $item) {
                    if (is_bool($item)) {
                        $item = $item ? 'Ya' : 'Tidak';
                    } elseif ($item === null || $item === '') {
                        $item = 'Belum ada';
                    } elseif (! is_scalar($item)) {
                        $item = 'Data tambahan';
                    }

                    $parts[] = is_string($itemKey)
                        ? \Illuminate\Support\Str::headline($itemKey).': '.$item
                        : (string) $item;
                }

                return $parts !== [] ? implode(', ', $parts) : 'Tidak ada';
            }

            return (string) $value;
        };
    @endphp

    {{-- Filter --}}
    <form
        method="GET"
        class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4
               sm:grid-cols-[1fr_220px_auto]"
    >
        <label class="sr-only" for="audit-search">Cari aktivitas atau pengguna</label>
        <input
            class="input"
            id="audit-search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari aktivitas atau pengguna"
        >

        <label class="sr-only" for="audit-date">Tanggal aktivitas</label>
        <input
            class="input"
            id="audit-date"
            type="date"
            name="date"
            value="{{ request('date') }}"
        >

        <button class="btn-primary" type="submit">
            Cari
        </button>
    </form>

    {{-- Tabel --}}
    <div class="table-shell mt-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-left text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="p-4">Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Entitas</th>
                        <th>IP</th>
                        <th class="min-w-[340px]">Perubahan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        @php
                            $before = is_array($log->old_values)
                                ? $log->old_values
                                : [];

                            $after = is_array($log->new_values)
                                ? $log->new_values
                                : [];

                            $changeKeys = collect([
                                ...array_keys($before),
                                ...array_keys($after),
                            ])->unique()->values();
                        @endphp

                        <tr class="align-top">
                            <td class="whitespace-nowrap p-4">
                                {{ $log->created_at->format('d-m-Y H:i:s') }}
                            </td>

                            <td class="px-3 py-4">
                                <p class="font-semibold text-slate-800">
                                    {{ $log->user?->name ?? 'Sistem' }}
                                </p>

                                @if ($log->user?->email)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $log->user->email }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-3 py-4">
                                <span class="badge-info">
                                    {{ \Illuminate\Support\Str::headline($log->action) }}
                                </span>
                            </td>

                            <td class="max-w-xs px-3 py-4">
                                @if ($log->entity_type)
                                    <p class="font-medium text-slate-700">
                                        {{ \Illuminate\Support\Str::headline(
                                            class_basename($log->entity_type)
                                        ) }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        ID #{{ $log->entity_id }}
                                    </p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-4">
                                {{ $log->ip_address ?? '—' }}
                            </td>

                            <td class="px-3 py-4">
                                @if ($changeKeys->isEmpty())
                                    <div
                                        class="rounded-2xl border border-slate-200
                                               bg-slate-50 px-4 py-3
                                               text-sm text-slate-500"
                                    >
                                        Tidak ada rincian perubahan.
                                    </div>
                                @else
                                    <details class="group">
                                        <summary
                                            class="flex cursor-pointer list-none
                                                   items-center gap-2 font-bold
                                                   text-blue-600"
                                        >
                                            <span
                                                class="transition-transform
                                                       group-open:rotate-90"
                                            >
                                                ▶
                                            </span>

                                            <span>Lihat perubahan</span>
                                        </summary>

                                        <div class="mt-3 space-y-3">
                                            @foreach ($changeKeys as $key)
                                                @php
                                                    $oldValue = $before[$key] ?? null;
                                                    $newValue = $after[$key] ?? null;

                                                    $label = $fieldLabels[$key]
                                                        ?? \Illuminate\Support\Str::headline($key);
                                                @endphp

                                                <div
                                                    class="rounded-2xl border
                                                           border-slate-200
                                                           bg-slate-50 p-3"
                                                >
                                                    <p
                                                        class="text-xs font-bold
                                                               uppercase tracking-wide
                                                               text-slate-500"
                                                    >
                                                        {{ $label }}
                                                    </p>

                                                    <div
                                                        class="mt-3 grid gap-2
                                                               sm:grid-cols-[1fr_auto_1fr]
                                                               sm:items-center"
                                                    >
                                                        <div
                                                            class="rounded-xl bg-white
                                                                   px-3 py-2"
                                                        >
                                                            <span
                                                                class="block text-xs
                                                                       text-slate-400"
                                                            >
                                                                Sebelum
                                                            </span>

                                                            <strong
                                                                class="mt-1 block break-words
                                                                       text-sm text-slate-700"
                                                            >
                                                                {{ $formatAuditValue(
                                                                    $key,
                                                                    $oldValue
                                                                ) }}
                                                            </strong>
                                                        </div>

                                                        <span
                                                            class="text-center
                                                                   font-bold text-blue-500"
                                                        >
                                                            →
                                                        </span>

                                                        <div
                                                            class="rounded-xl bg-blue-50
                                                                   px-3 py-2"
                                                        >
                                                            <span
                                                                class="block text-xs
                                                                       text-blue-400"
                                                            >
                                                                Sesudah
                                                            </span>

                                                            <strong
                                                                class="mt-1 block break-words
                                                                       text-sm text-blue-700"
                                                            >
                                                                {{ $formatAuditValue(
                                                                    $key,
                                                                    $newValue
                                                                ) }}
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="p-10 text-center text-slate-500"
                            >
                                Belum ada audit log.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
@endsection
