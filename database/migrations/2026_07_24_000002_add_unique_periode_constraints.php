<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit C3 — dua periode tidak boleh mulai pada tanggal yang sama untuk kunci yang sama.
 *
 * AnggaranPorsi::aktif() dan HargaBahan::hargaAktif() memilih pemenang dengan
 * orderByDesc('berlaku_mulai'). Bila ada dua baris dengan berlaku_mulai identik,
 * pemenangnya bergantung urutan baris di storage — nilai anggaran/harga bisa
 * berubah tanpa ada perubahan data. Constraint ini menutup celah itu di level DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->pastikanTidakAdaDuplikat('anggaran_porsis', ['kelompok', 'berlaku_mulai']);
        $this->pastikanTidakAdaDuplikat('harga_bahans', ['bahan_pangan_id', 'berlaku_mulai']);

        Schema::table('anggaran_porsis', function (Blueprint $table) {
            $table->unique(['kelompok', 'berlaku_mulai'], 'anggaran_porsis_kelompok_mulai_unique');
        });

        // Sekaligus jadi index composite untuk hargaAktif() (where bahan_pangan_id + berlaku_mulai).
        Schema::table('harga_bahans', function (Blueprint $table) {
            $table->unique(['bahan_pangan_id', 'berlaku_mulai'], 'harga_bahans_bahan_mulai_unique');
        });
    }

    public function down(): void
    {
        Schema::table('anggaran_porsis', function (Blueprint $table) {
            $table->dropUnique('anggaran_porsis_kelompok_mulai_unique');
        });

        Schema::table('harga_bahans', function (Blueprint $table) {
            $table->dropUnique('harga_bahans_bahan_mulai_unique');
        });
    }

    /**
     * Data duplikat harus dibereskan manual — migrasi tidak boleh menghapus baris
     * anggaran/harga secara diam-diam.
     */
    private function pastikanTidakAdaDuplikat(string $tabel, array $kolom): void
    {
        $duplikat = DB::table($tabel)
            ->select($kolom)
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy($kolom)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplikat->isEmpty()) {
            return;
        }

        $daftar = $duplikat->map(function ($baris) use ($kolom) {
            $nilai = array_map(fn ($k) => "{$k}=".($baris->$k ?? 'NULL'), $kolom);

            return '('.implode(', ', $nilai).") x{$baris->jumlah}";
        })->implode('; ');

        throw new RuntimeException(
            "Migrasi dibatalkan: tabel `{$tabel}` masih punya periode dengan tanggal mulai ganda — {$daftar}. "
            .'Hapus/gabungkan baris duplikat tersebut lebih dulu, lalu jalankan ulang migrasi.'
        );
    }
};
