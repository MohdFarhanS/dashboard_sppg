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
        if (! Schema::hasTable('anggaran_porsis')) {
            Schema::create('anggaran_porsis', function (Blueprint $table) {
                $table->id();
                $table->decimal('anggaran_per_porsi', 10, 2);
                $table->date('berlaku_mulai');
                $table->date('berlaku_sampai')->nullable();
                $table->string('keterangan')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
                $table->index('berlaku_mulai');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggaran_porsis');
    }
};
