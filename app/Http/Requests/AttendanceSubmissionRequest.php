<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('employee') ?? false;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(['check_in', 'check_out'])],
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.config('attendance.photo_max_kilobytes', 4096)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['required', 'numeric', 'min:0', 'max:10000'],
            'device_time' => ['required', 'date'],
            'timezone' => ['required', 'string', 'timezone', 'max:80'],
            'device_id' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto selfie wajib diambil.',
            'photo.image' => 'Berkas selfie harus berupa gambar.',
            'photo.max' => 'Ukuran foto melebihi batas yang diizinkan.',
            'latitude.required' => 'Lokasi belum berhasil diperoleh.',
            'longitude.required' => 'Lokasi belum berhasil diperoleh.',
            'location_accuracy.max' => 'Akurasi lokasi tidak valid.',
            'device_time.required' => 'Waktu perangkat tidak tersedia. Muat ulang halaman lalu coba kembali.',
            'timezone.required' => 'Zona waktu perangkat tidak tersedia. Muat ulang halaman lalu coba kembali.',
            'timezone.timezone' => 'Zona waktu perangkat tidak valid.',
            'device_id.required' => 'Identitas perangkat tidak tersedia. Muat ulang halaman lalu coba kembali.',
        ];
    }
}
