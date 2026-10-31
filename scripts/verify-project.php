<?php

$root = dirname(__DIR__);
$errors = [];
$warnings = [];

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    $errors[] = 'PHP minimal 8.3. Versi saat ini: '.PHP_VERSION;
}

foreach (['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml'] as $extension) {
    if (! extension_loaded($extension)) {
        $errors[] = "Ekstensi PHP {$extension} belum aktif.";
    }
}

foreach (['artisan', 'routes/web.php', 'app/Services/AttendanceService.php', 'resources/views/auth/login.blade.php'] as $file) {
    if (! file_exists($root.'/'.$file)) {
        $errors[] = "Berkas wajib tidak ditemukan: {$file}";
    }
}

foreach (['storage', 'bootstrap/cache'] as $directory) {
    if (is_dir($root.'/'.$directory) && ! is_writable($root.'/'.$directory)) {
        $warnings[] = "Direktori belum writable: {$directory}";
    }
}

if (! file_exists($root.'/.env')) {
    $warnings[] = 'File .env belum dibuat.';
}

if (! file_exists($root.'/public/build/manifest.json')) {
    $warnings[] = 'Aset frontend belum dibangun. Jalankan npm install lalu npm run build.';
}

echo "Hadirin Project Verification\n";
echo 'PHP: '.PHP_VERSION."\n";
echo "Root: {$root}\n\n";

foreach ($errors as $error) {
    echo "[ERROR] {$error}\n";
}
foreach ($warnings as $warning) {
    echo "[WARN ] {$warning}\n";
}
if (! $errors && ! $warnings) {
    echo "[OK] Pemeriksaan dasar berhasil.\n";
}

exit($errors ? 1 : 0);
