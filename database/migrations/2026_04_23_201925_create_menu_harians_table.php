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
        Schema::create('menu_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // ahli_gizi yg input
            $table->string('nama_menu')->nullable();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['tanggal']); // 1 hari 1 record per unit
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_harians');
    }
};
