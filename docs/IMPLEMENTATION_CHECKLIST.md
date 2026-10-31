# Checklist Final

## Selesai

- [x] Satu login dan redirect role
- [x] Middleware akses silang
- [x] UI pegawai mobile-first
- [x] Beranda, riwayat, absensi, izin, profil
- [x] Kamera depan dan GPS
- [x] Geofence backend 100 meter
- [x] Jadwal biasa dan lintas tengah malam
- [x] Pencegahan absensi ganda
- [x] Foto dan lampiran private
- [x] Hash foto dan peninjauan anomali
- [x] CRUD pegawai, unit, jabatan, lokasi, shift
- [x] Jadwal tunggal dan massal
- [x] Review absensi
- [x] Approval izin sinkron ke jadwal
- [x] Laporan bulanan, jadwal, alpa, dan CSV
- [x] Audit log UI
- [x] Profil/password pegawai dan admin
- [x] Halaman error 403/404/419/500
- [x] Installer proyek baru, setup script, dan verification script
- [x] Penutupan harian otomatis untuk status tidak hadir
- [x] Unit dan feature test dasar tanpa ketergantungan ekstensi GD

## Wajib Sebelum Produksi

- [ ] Verifikasi koordinat langsung di sekolah
- [ ] Ganti password demo
- [ ] Hapus atau ubah akun demo
- [ ] Aktifkan HTTPS
- [ ] Set `APP_DEBUG=false`
- [ ] Set `ATTENDANCE_ENFORCE_TIME_WINDOW=true`
- [ ] Atur jadwal resmi
- [ ] Atur backup database dan foto
- [ ] Tetapkan kebijakan retensi selfie
- [ ] Jalankan `php artisan test`
- [ ] Uji ponsel Android dan iPhone
- [ ] Lakukan security review

## Pengembangan Opsional

- Face recognition/liveness dengan persetujuan dan kebijakan biometrik
- Notifikasi push/email
- Ekspor XLSX/PDF
- Integrasi data kepegawaian
- Monitoring error terpusat
