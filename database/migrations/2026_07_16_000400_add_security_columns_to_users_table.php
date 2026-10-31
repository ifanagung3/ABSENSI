<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('session_version')->default(1);
            $table->boolean('must_change_password')->default(false)->index();
        });

        DB::table('users')
            ->whereIn('email', ['admin@hadirin.test', 'pegawai@hadirin.test'])
            ->update(['must_change_password' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['session_version', 'must_change_password']);
        });
    }
};
