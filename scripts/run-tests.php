<?php

$phpArguments = [];

if (! extension_loaded('pdo_sqlite')) {
    $extensionDirectory = (string) ini_get('extension_dir');
    $pdoSqlite = $extensionDirectory.DIRECTORY_SEPARATOR.'php_pdo_sqlite.dll';
    $sqlite3 = $extensionDirectory.DIRECTORY_SEPARATOR.'php_sqlite3.dll';

    if (PHP_OS_FAMILY !== 'Windows' || ! is_file($pdoSqlite) || ! is_file($sqlite3)) {
        fwrite(STDERR, "Ekstensi pdo_sqlite belum aktif. Aktifkan ekstensi tersebut sebelum menjalankan test.\n");
        exit(1);
    }

    $phpArguments = [
        '-d', 'extension=php_pdo_sqlite.dll',
        '-d', 'extension=php_sqlite3.dll',
    ];
}

$run = static function (array $arguments): int {
    $command = implode(' ', array_map(escapeshellarg(...), $arguments));
    passthru($command, $exitCode);

    return $exitCode;
};

$baseCommand = [PHP_BINARY, ...$phpArguments];

$clearExitCode = $run([...$baseCommand, 'artisan', 'config:clear', '--ansi']);
if ($clearExitCode !== 0) {
    exit($clearExitCode);
}

$testArguments = array_slice($_SERVER['argv'], 1);
exit($run([...$baseCommand, 'artisan', 'test', ...$testArguments]));
