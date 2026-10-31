# Hadirin — Sistem Absensi Kamera & Lokasi

Versi final starter kit untuk **SMKN 4 Pariaman**. Aplikasi memakai satu halaman login, lalu memisahkan fitur berdasarkan role `employee` dan `admin` pada route, middleware, controller, layout, menu, dan query backend.

## Stack

- Laravel 13 dan PHP 8.3+
- MySQL/MariaDB dari Laragon
- Blade, Tailwind CSS 4, Alpine.js, Vite
- Kamera depan melalui MediaDevices API
- GPS melalui Geolocation API
- Foto pada private storage

## Konfigurasi lokasi awal

```text
Lokasi       : SMKN 4 Pariaman
Latitude     : -0.6447000
Longitude    : 100.1401000
Radius       : 100 meter
Akurasi GPS  : maksimal ±100 meter
Zona waktu   : Asia/Jakarta (WIB)
```

Koordinat awal harus diverifikasi sekali lagi menggunakan GPS langsung di area sekolah sebelum digunakan sebagai data produksi.

## Fitur Pegawai

- Login bersama di `/login`, redirect otomatis berdasarkan role.
- Beranda: jadwal, jam WIB, status, masuk, pulang, dan ringkasan bulanan.
- Absensi masuk/pulang menggunakan selfie kamera depan dan GPS.
- Indikator jarak, radius, dan akurasi sebelum dikirim.
- Riwayat pribadi dengan filter bulan dan ringkasan keterlambatan.
- Pengajuan izin, sakit, atau cuti dengan lampiran private.
- Pembatalan pengajuan yang masih menunggu.
- Profil, nomor telepon, dan penggantian password.

## Fitur Administrator

- Dashboard terjadwal, hadir, terlambat, izin, belum hadir, dan data ditinjau.
- Tambah/edit/aktif-nonaktif pegawai dan reset password sementara.
- Kelola unit kerja dan jabatan.
- Tambah/edit/aktif-nonaktif lokasi dan geofence.
- Tambah/edit/hapus shift.
- Jadwal per pegawai dan jadwal massal berdasarkan rentang tanggal.
- Daftar dan detail absensi, foto private, koordinat, jarak, akurasi, IP, perangkat, dan jam perangkat.
- Pemeriksaan status absensi dan catatan admin.
- Persetujuan izin/cuti yang otomatis memperbarui jadwal dan rekap pada hari kerja.
- Laporan bulanan dan ekspor CSV UTF-8.
- Audit log aktivitas penting.
- Profil dan penggantian password administrator.

## Aturan Backend Absensi

- Akun, role, dan status pegawai harus aktif.
- Harus ada jadwal `working` yang sesuai, termasuk shift lintas tengah malam.
- Lokasi jadwal harus aktif.
- Akurasi GPS harus memenuhi batas lokasi.
- Jarak dihitung ulang oleh backend menggunakan Haversine.
- Radius SMKN 4 Pariaman adalah 100 meter.
- Waktu resmi menggunakan waktu server.
- Check-out wajib didahului check-in.
- Unique index dan transaction mencegah absensi ganda.
- Foto divalidasi, diberi nama UUID, disimpan private, dan diberi hash SHA-256.
- Foto identik pada pegawai lain dan selisih jam perangkat berlebihan ditandai `pending_review`.
- File foto otomatis dihapus apabila transaksi database gagal.
- Aksi sensitif dicatat ke audit log.

## Instalasi Baru di Laragon

### Cara paling mudah

Ekstrak starter kit, buka **Terminal Laragon** pada folder hasil ekstrak, lalu jalankan:

```bat
INSTALL_NEW_PROJECT.bat
```

Secara default proyek dibuat di `C:\laragon\www\absensi`. Lokasi lain dapat diberikan sebagai argumen:

```bat
INSTALL_NEW_PROJECT.bat D:\projects\absensi
```

Installer membuat proyek Laravel, menyalin seluruh modul Hadirin, mencoba membuat database `absensi_db` menggunakan akun MySQL Laragon `root` tanpa password, menjalankan migration/seeder, membangun frontend, dan menjalankan test. Apabila konfigurasi MySQL berbeda, buat database secara manual dan sesuaikan `.env`.

### Instalasi manual

#### 1. Siapkan proyek Laravel

Buka **Terminal Laragon**:

```bat
cd C:\laragon\www
composer create-project laravel/laravel absensi
cd absensi
```

#### 2. Salin starter kit

Ekstrak ZIP ini, kemudian salin seluruh isi folder `sistem-absensi-kamera-lokasi` ke `C:\laragon\www\absensi`. Izinkan file yang sama ditimpa.

Starter kit ini adalah **application overlay**. Framework dan folder `vendor` diperoleh dari `composer create-project`.

#### 3. Buat database

```sql
CREATE DATABASE absensi_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

#### 4. Konfigurasi `.env`

```bat
copy .env.example .env
```

Pastikan bagian berikut sesuai:

```env
APP_NAME="Hadirin"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://absensi.test
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_db
DB_USERNAME=root
DB_PASSWORD=

ATTENDANCE_ENFORCE_TIME_WINDOW=true
```

#### 5. Jalankan instalasi

Cara otomatis:

```bat
scripts\setup-after-copy.bat
```

Cara manual:

```bat
composer install
php artisan key:generate
npm install
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan optimize:clear
composer test
```

Klik **Reload** pada Laragon, lalu buka:

```text
http://absensi.test/login
```

## Akun Demo

```text
Pegawai
Email    : pegawai@hadirin.test
Password : password

Administrator
Email    : admin@hadirin.test
Password : password
```

Kedua akun demo otomatis diarahkan untuk mengganti password pada login pertama. Seeder demo tidak dijalankan pada environment `production` dan tidak lagi menimpa password, lokasi, shift, atau jadwal yang sudah ada ketika dijalankan ulang.

## Pengujian Kamera dari Ponsel

Kamera dan geolokasi browser membutuhkan secure context. Pada komputer, `localhost` biasanya dapat dipakai. Untuk membuka aplikasi dari ponsel melalui jaringan lokal, aktifkan HTTPS pada virtual host Laragon atau gunakan sertifikat development yang dipercaya perangkat.

Untuk menguji alur tanpa menunggu jam shift, gunakan sementara pada komputer development:

```env
ATTENDANCE_ENFORCE_TIME_WINDOW=false
```

Jangan gunakan nilai tersebut pada produksi. Setelah pengujian selesai, kembalikan menjadi `true` lalu jalankan:

```bat
php artisan config:clear
```

Lokasi tetap divalidasi meskipun pemeriksaan jendela waktu dimatikan.

## Penutupan Absensi Otomatis

Sistem menyediakan perintah:

```bat
php artisan attendance:close-day
php artisan attendance:close-day 2026-07-13
```

Tanpa argumen, perintah menutup hari sebelumnya dan membuat status `absent` untuk jadwal kerja yang tidak memiliki absensi. Scheduler menjalankannya setiap pukul 06.00 WIB.

## Pemeriksaan Proyek

```bat
php scripts\verify-project.php
php artisan route:list
composer test
npm run build
```

Suite pengujian mencakup 60 test (301 assertion), termasuk akses role, sesi/password,
geofence dan absensi, jadwal/shift/lokasi, izin/cuti, ekspor CSV, audit, header
keamanan, dan keamanan seeder.

Dokumen hasil pemeriksaan akhir tersedia di `docs/FINAL_VALIDATION.md`.

## Struktur Utama

```text
app/Http/Controllers/Auth/       satu login
app/Http/Controllers/Employee/   fitur pegawai
app/Http/Controllers/Admin/      fitur administrator
app/Services/AttendanceService   aturan bisnis absensi
app/Services/GeofenceService     perhitungan jarak
resources/views/employee/        UI mobile pegawai
resources/views/admin/           dashboard admin
config/attendance.php            pengaturan keamanan absensi
database/migrations/             skema database
database/seeders/                akun dan jadwal demo
scripts/                         setup dan verifikasi
```

## Konfigurasi Produksi

Gunakan `.env.production.example` sebagai referensi, lalu isi domain dan kredensial database yang sebenarnya. Jangan menyalin password contoh ke server produksi.

## Sebelum Produksi

- Ganti semua password demo.
- Set `APP_ENV=production` dan `APP_DEBUG=false`.
- Pastikan `ATTENDANCE_ENFORCE_TIME_WINDOW=true`.
- Aktifkan HTTPS dan `SESSION_SECURE_COOKIE=true`.
- Verifikasi koordinat sekolah secara langsung.
- Atur jadwal kerja resmi.
- Tentukan masa retensi dan akses foto selfie.
- Buat backup database dan private storage.
- Jalankan Laravel Scheduler agar data tidak hadir ditutup otomatis setiap pukul 06.00 WIB (`php artisan schedule:work` saat development atau Windows Task Scheduler/cron pada produksi).
- Jalankan test dan security review.
- Jalankan `php artisan migrate --force`, `php artisan config:cache`, dan `php artisan route:cache` pada setiap deployment.
- Pastikan akun dengan password sementara sudah menyelesaikan penggantian password; perubahan/reset password otomatis mencabut sesi lama.

Face recognition dan liveness detection tidak termasuk. Versi ini sengaja menggunakan selfie langsung, GPS, geofence, waktu server, hash foto, peninjauan anomali, dan audit log tanpa menyimpan template biometrik.
