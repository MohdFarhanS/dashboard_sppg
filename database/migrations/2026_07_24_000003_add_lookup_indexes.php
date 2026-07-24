<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit E9 — index kurang untuk kolom yang sering dipakai di WHERE:
 * `users.role` (RoleMiddleware, filter per-role) dan `pesan_masuks.is_read`
 * (scope unread(), badge navbar). Composite `(bahan_pangan_id, berlaku_mulai)`
 * dan `(kelompok, berlaku_mulai)` sudah terpasang lewat unique constraint di
 * migrasi 2026_07_24_000002 (C3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });

        Schema::table('pesan_masuks', function (Blueprint $table) {
            $table->index('is_read', 'pesan_masuks_is_read_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });

        Schema::table('pesan_masuks', function (Blueprint $table) {
            $table->dropIndex('pesan_masuks_is_read_index');
        });
    }
};
