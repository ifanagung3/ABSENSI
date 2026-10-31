<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB lama mungkin masih memiliki role supervisor.
        // SQLite pada environment test tidak mendukung sintaks MODIFY ENUM.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'supervisor', 'employee') NOT NULL DEFAULT 'employee'");
        }

        DB::table('users')->where('role', 'supervisor')->update(['role' => 'employee']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'supervisor', 'employee') NOT NULL DEFAULT 'employee'");
        }
    }
};
