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
            $table->dropForeign(['user_id']);
        });
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('import_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('menu_harians', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('import_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
