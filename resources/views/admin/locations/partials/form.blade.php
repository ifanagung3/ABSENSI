@php
    $locationFormKey = $location?->getKey()
        ? 'edit-'.$location->getKey()
        : 'create';
    $isSubmittedLocationForm = old('_location_form') === $locationFormKey;
    $locationFormValue = static fn (string $field, mixed $fallback = null) =>
        $isSubmittedLocationForm ? old($field) : $fallback;
@endphp

<input
    type="hidden"
    name="_location_form"
    value="{{ $locationFormKey }}"
>

<div>
    <label class="label" for="name-{{ $locationFormKey }}">
        Nama lokasi
    </label>

    <input
        id="name-{{ $locationFormKey }}"
        class="input"
        name="name"
        value="{{ $locationFormValue('name', $location?->name) }}"
        placeholder="Contoh: Kantor Cabang Jakarta"
        required
    >
</div>

<div>
    <label class="label" for="address-{{ $locationFormKey }}">
        Alamat
    </label>

    <textarea
        id="address-{{ $locationFormKey }}"
        class="input min-h-24"
        name="address"
        placeholder="Masukkan alamat lengkap lokasi kerja"
        required
    >{{ $locationFormValue('address', $location?->address) }}</textarea>
</div>

{{-- Pengambilan lokasi perangkat --}}
<div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
    <div
        class="flex min-w-0 flex-col gap-4 md:flex-row
               md:items-center md:justify-between"
    >
        <div class="min-w-0">
            <p class="font-bold text-slate-800">
                Titik lokasi absensi
            </p>

            <p class="mt-1 text-sm leading-6 text-slate-500">
                Gunakan GPS jika Anda sedang berada di lokasi yang akan
                didaftarkan. Jika lokasinya berbeda, isi koordinat dari peta.
            </p>
        </div>

        <button
            id="get-device-location-{{ $locationFormKey }}"
            data-get-device-location
            type="button"
            class="inline-flex min-h-12 w-full items-center
                   justify-center gap-2 whitespace-normal rounded-2xl
                   bg-blue-600 px-5 py-3 text-center font-bold
                   text-white transition md:w-auto md:shrink-0
                   hover:bg-blue-700 disabled:cursor-not-allowed
                   disabled:opacity-60"
        >
            <span aria-hidden="true">⌖</span>
            <span data-location-button-text>
                Gunakan Lokasi Perangkat
            </span>
        </button>
    </div>

    <div
        id="location-status-{{ $locationFormKey }}"
        data-location-status
        role="status"
        aria-live="polite"
        class="mt-4 hidden break-words rounded-xl border px-4 py-3 text-sm"
    ></div>
</div>

{{-- Koordinat --}}
<div
    class="break-words rounded-2xl border border-slate-200
           bg-slate-50 p-4 text-sm leading-6 text-slate-600"
>
    <strong class="text-slate-800">Menambahkan lokasi berbeda?</strong>
    Isi latitude dan longitude lokasi tujuan dari Google Maps atau peta
    lainnya. GPS selalu mengambil posisi perangkat yang sedang digunakan.
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div class="min-w-0">
        <label class="label" for="latitude-{{ $locationFormKey }}">
            Latitude
        </label>

        <input
            id="latitude-{{ $locationFormKey }}"
            name="latitude"
            type="number"
            step="0.0000001"
            min="-90"
            max="90"
            inputmode="decimal"
            class="input"
            value="{{ $locationFormValue('latitude', $location?->latitude) }}"
            placeholder="Contoh: -6.1858507"
            required
        >
    </div>

    <div class="min-w-0">
        <label class="label" for="longitude-{{ $locationFormKey }}">
            Longitude
        </label>

        <input
            id="longitude-{{ $locationFormKey }}"
            name="longitude"
            type="number"
            step="0.0000001"
            min="-180"
            max="180"
            inputmode="decimal"
            class="input"
            value="{{ $locationFormValue('longitude', $location?->longitude) }}"
            placeholder="Contoh: 106.7709312"
            required
        >
    </div>
</div>

{{-- Informasi akurasi aktual --}}
<input
    id="detected-accuracy-{{ $locationFormKey }}"
    name="detected_accuracy"
    type="hidden"
>

<div
    data-accuracy-information
    class="hidden rounded-2xl border border-slate-200
           bg-slate-50 px-4 py-3"
>
    <div
        class="flex flex-col items-start gap-1
               sm:flex-row sm:items-center sm:justify-between sm:gap-4"
    >
        <span class="text-sm text-slate-500">
            Akurasi lokasi perangkat
        </span>

        <strong
            data-accuracy-value
            class="text-sm text-slate-800"
        >
            -
        </strong>
    </div>
</div>

{{-- Radius dan batas akurasi --}}
<div class="grid gap-4 sm:grid-cols-2">
    <div class="min-w-0">
        <label class="label" for="radius-meters-{{ $locationFormKey }}">
            Radius absensi (meter)
        </label>

        <input
            id="radius-meters-{{ $locationFormKey }}"
            name="radius_meters"
            type="number"
            min="10"
            max="5000"
            class="input"
            value="{{ $locationFormValue('radius_meters', $location?->radius_meters ?? 100) }}"
            required
        >
    </div>

    <div class="min-w-0">
        <label class="label" for="maximum-accuracy-meters-{{ $locationFormKey }}">
            Batas akurasi pegawai saat absen (meter)
        </label>

        <input
            id="maximum-accuracy-meters-{{ $locationFormKey }}"
            name="maximum_accuracy_meters"
            type="number"
            min="5"
            max="1000"
            class="input"
            value="{{ $locationFormValue('maximum_accuracy_meters', $location?->maximum_accuracy_meters ?? 100) }}"
            required
        >
    </div>
</div>

<p class="help">
    Pastikan titik koordinat diverifikasi langsung di lokasi kerja sebelum
    digunakan. Nilai batas akurasi di atas mengatur syarat GPS pegawai saat
    absen; nilai tersebut tidak meningkatkan akurasi perangkat admin.
</p>
