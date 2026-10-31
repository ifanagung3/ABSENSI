# Pembagian Frontend dan Backend

## UI Pegawai

`resources/views/employee/` memakai desain mobile-first dan bottom navigation lima menu:

- Beranda
- Riwayat
- Absen
- Izin
- Profil

`resources/js/attendance-camera.js` menangani izin kamera, preview, selfie JPEG, GPS high accuracy, jarak lokal untuk feedback, ID perangkat lokal, dan pengiriman `multipart/form-data`.

Validasi lokal hanya memberi feedback. Hasil resmi tetap ditentukan backend.

## UI Administrator

`resources/views/admin/` memakai sidebar desktop dan menu horizontal mobile:

- Dashboard
- Pegawai
- Unit & Jabatan
- Lokasi
- Shift
- Jadwal
- Absensi
- Izin & Cuti
- Laporan
- Audit Log
- Profil Admin

Form tambah/edit menggunakan modal Alpine.js. Semua write action memakai CSRF dan method Laravel yang sesuai.

## Backend

### Authentication

`SessionController` memakai satu form `/login`. Role `employee` diarahkan ke `/pegawai/dashboard`; role `admin` ke `/admin/dashboard`.

### Authorization

- Pegawai: `auth` + `role:employee`
- Admin: `auth` + `role:admin`

Akses silang menghasilkan HTTP 403 walaupun URL diketik manual.

### AttendanceService

Service melakukan:

1. Validasi akun dan pegawai aktif.
2. Penyelesaian jadwal hari ini atau shift lintas tengah malam.
3. Validasi lokasi aktif dan akurasi GPS.
4. Perhitungan Haversine backend.
5. Validasi radius 100 meter.
6. Validasi jendela waktu shift.
7. Lock dan transaction database.
8. Pencegahan event ganda.
9. Penyimpanan foto private dan SHA-256.
10. Pemeriksaan selisih jam perangkat dan foto identik lintas pegawai.
11. Penetapan hadir, terlambat, atau pending review.
12. Audit log.
13. Penghapusan file apabila transaction gagal.

### Izin dan Cuti

Lampiran berada di disk private. Saat admin menyetujui, backend hanya memperbarui tanggal yang memiliki jadwal `working`. Pengajuan ditolak dari approval otomatis bila pada rentang tersebut sudah ada check-in.

### Reporting

Halaman laporan merangkum data per pegawai. CSV memakai UTF-8 BOM agar mudah dibuka di Excel.
