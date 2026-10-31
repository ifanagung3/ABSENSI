# Daftar Route Aplikasi

Aplikasi memakai session authentication dan route web. Endpoint absensi mengembalikan JSON karena dipanggil oleh JavaScript kamera.

## Autentikasi

| Method | Endpoint | Fungsi | Akses |
|---|---|---|---|
| GET | `/login` | Form login bersama | Guest |
| POST | `/login` | Autentikasi dan redirect berdasarkan role | Guest |
| POST | `/logout` | Mengakhiri sesi | Auth |
| GET | `/api/status` | Status aplikasi dan waktu server | Publik |

## Pegawai

Semua route memakai `auth` dan `role:employee`.

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/pegawai/dashboard` | Beranda pribadi |
| GET | `/pegawai/absensi?type=check_in` | Kamera absensi masuk |
| GET | `/pegawai/absensi?type=check_out` | Kamera absensi pulang |
| POST | `/pegawai/absensi` | Kirim selfie dan lokasi |
| GET | `/pegawai/riwayat?month=YYYY-MM` | Riwayat bulanan |
| GET | `/pegawai/izin` | Daftar pengajuan |
| POST | `/pegawai/izin` | Membuat pengajuan |
| PATCH | `/pegawai/izin/{id}/cancel` | Membatalkan pengajuan pending |
| GET | `/pegawai/izin/{id}/lampiran` | Membaca lampiran milik sendiri |
| GET | `/pegawai/profil` | Profil |
| PATCH | `/pegawai/profil` | Ubah nama/telepon |
| PUT | `/pegawai/profil/password` | Ubah password |

## Administrator

Semua route memakai `auth` dan `role:admin`.

| Modul | Route utama | Operasi |
|---|---|---|
| Dashboard | `/admin/dashboard` | Statistik sekolah |
| Pegawai | `/admin/pegawai` | List, tambah, edit, toggle, reset password |
| Unit & jabatan | `/admin/master-data` | Tambah, edit, hapus referensi |
| Lokasi | `/admin/lokasi` | Tambah, edit, toggle geofence |
| Shift | `/admin/shift` | Tambah, edit, hapus |
| Jadwal | `/admin/jadwal` | Satu jadwal, jadwal massal, hapus |
| Absensi | `/admin/absensi` | List, detail, review, foto private |
| Izin | `/admin/izin` | Review dan lampiran private |
| Laporan | `/admin/laporan` | Rekap bulanan dan ekspor CSV |
| Audit | `/admin/audit-log` | Riwayat aktivitas |
| Profil | `/admin/profil` | Profil dan password admin |

## Payload Absensi

`multipart/form-data`

```text
event_type=check_in
photo=<binary JPEG>
latitude=-0.6447000
longitude=100.1401000
location_accuracy=18
device_time=2026-07-13T00:20:00.000Z
timezone=Asia/Jakarta
device_id=<UUID lokal>
```

## Respons Sukses

```json
{
  "message": "Absensi masuk berhasil dicatat.",
  "redirect": "http://absensi.test/pegawai/dashboard",
  "attendance": {
    "status": "present",
    "checked_in_at": "07:20",
    "checked_out_at": null,
    "late_minutes": 0
  }
}
```

## Respons Validasi

Laravel mengembalikan HTTP `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "location": [
      "Posisi Anda berjarak 240 meter dari SMKN 4 Pariaman. Radius maksimal 100 meter."
    ]
  }
}
```

Koordinat lokasi resmi selalu dibaca dari tabel `work_locations`. Frontend tidak menentukan hasil validasi.
