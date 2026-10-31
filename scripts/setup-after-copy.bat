@echo off
setlocal
cd /d "%~dp0\.."

echo ==================================================
echo  Hadirin - Final Setup untuk Laragon
echo ==================================================

where php >nul 2>nul || (echo [ERROR] PHP tidak ditemukan. Jalankan dari Terminal Laragon.& exit /b 1)
where composer >nul 2>nul || (echo [ERROR] Composer tidak ditemukan.& exit /b 1)
where npm >nul 2>nul || (echo [ERROR] Node.js/NPM tidak ditemukan.& exit /b 1)
if not exist artisan (echo [ERROR] File artisan tidak ada. Salin starter kit ke proyek Laravel terlebih dahulu.& exit /b 1)

if not exist .env copy .env.example .env >nul

where mysql >nul 2>nul
if not errorlevel 1 (
    echo Membuat database absensi_db jika belum ada...
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS absensi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || (
        echo [ERROR] Database tidak dapat dibuat dengan akun root tanpa password.
        echo Buat database secara manual dan sesuaikan .env, lalu jalankan ulang script ini.
        exit /b 1
    )
) else (
    echo [WARN] Perintah mysql tidak ditemukan. Pastikan absensi_db sudah dibuat sesuai .env.
)

call composer install --no-interaction || exit /b 1
call php artisan key:generate --force || exit /b 1
call npm install || exit /b 1
call php artisan migrate --seed || exit /b 1
call php artisan storage:link >nul 2>nul
call npm run build || exit /b 1
call php artisan optimize:clear || exit /b 1
call composer test || exit /b 1

echo.
echo [SELESAI] Buka http://absensi.test/login
echo Ubah semua password demo sebelum produksi.
endlocal
