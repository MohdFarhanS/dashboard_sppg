<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_harians', function (Blueprint $table) {
            // 'balita_sd3'       = Balita hingga Kelas 3 SD
            // 'sd4_ibu_menyusui' = Kelas 4 SD hingga Ibu Menyusui
            $table->enum('kelompok', ['balita_sd3', 'sd4_ibu_menyusui'])
                ->default('sd4_ibu_menyusui')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->dropColumn('kelompok');
        });
    }
};
