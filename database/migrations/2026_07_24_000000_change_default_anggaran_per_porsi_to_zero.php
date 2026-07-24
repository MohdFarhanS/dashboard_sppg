<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->decimal('anggaran_per_porsi', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->decimal('anggaran_per_porsi', 10, 2)->default(15000)->change();
        });
    }
};
