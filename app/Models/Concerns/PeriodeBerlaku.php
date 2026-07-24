<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Aturan penetapan periode berlaku (berlaku_mulai / berlaku_sampai) yang dipakai
 * bersama oleh AnggaranPorsi dan HargaBahan.
 *
 * Timeline per kunci (kelompok / bahan_pangan_id) harus berurutan tanpa celah dan
 * tanpa tumpang tindih — kalau tidak, aktif()/hargaAktif() yang memilih dengan
 * orderByDesc('berlaku_mulai') jadi non-deterministik (audit C3).
 */
trait PeriodeBerlaku
{
    /**
     * Sisipkan satu periode baru pada timeline milik $kunci.
     *
     * @param  array<string, mixed>  $kunci  kolom pembeda timeline, mis. ['kelompok' => 'balita_sd3']
     * @param  string  $mulai  tanggal Y-m-d periode baru
     * @param  array<string, mixed>  $atribut  nilai periode baru (nominal, keterangan, dst.)
     *
     * @throws ValidationException bila tanggal mulai sudah dipakai timeline yang sama
     */
    public static function tetapkanPeriode(array $kunci, string $mulai, array $atribut): static
    {
        $mulaiStr = Carbon::parse($mulai)->toDateString();
        $sehariSebelum = Carbon::parse($mulaiStr)->subDay()->toDateString();

        // Dua periode tidak boleh mulai pada tanggal yang sama — DB juga menolaknya
        // lewat unique index, tapi di sini kita beri pesan yang bisa dibaca pengguna.
        $bentrok = static::where($kunci)
            ->whereDate('berlaku_mulai', $mulaiStr)
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'berlaku_mulai' => static::pesanPeriodeBentrok(),
            ]);
        }

        // Tutup periode yang MULAI SEBELUM periode baru dan masih membentang
        // melewatinya. Filter '<' penting: tanpa itu, penetapan backdate akan memberi
        // periode masa depan berlaku_sampai yang lebih awal dari berlaku_mulai-nya
        // (range terbalik) sehingga periode itu hilang selamanya dari aktif().
        static::where($kunci)
            ->whereDate('berlaku_mulai', '<', $mulaiStr)
            ->where(function ($q) use ($mulaiStr) {
                $q->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', $mulaiStr);
            })
            ->update(['berlaku_sampai' => $sehariSebelum]);

        // Backdate: bila sudah ada periode yang mulai SETELAH periode ini, periode baru
        // harus ditutup sehari sebelumnya — bukan open-ended — supaya tidak tumpang tindih.
        $mulaiBerikutnya = static::where($kunci)
            ->whereDate('berlaku_mulai', '>', $mulaiStr)
            ->orderBy('berlaku_mulai')
            ->value('berlaku_mulai');

        return static::create($atribut + [
            'berlaku_mulai' => $mulaiStr,
            'berlaku_sampai' => $mulaiBerikutnya
                ? Carbon::parse($mulaiBerikutnya)->subDay()->toDateString()
                : null,
        ] + $kunci);
    }

    /**
     * Invarian timeline: periode paling akhir milik $kunci selalu open-ended.
     * Dipanggil setelah penghapusan periode supaya tidak ada tanggal yang kehilangan
     * tarif/anggaran aktif — dan penghapusan tidak menciptakan tumpang tindih dengan
     * periode setelahnya (yang lebih baru justru dibiarkan menang).
     */
    public static function pastikanPeriodeTerakhirTerbuka(array $kunci): void
    {
        $terakhir = static::where($kunci)
            ->orderByDesc('berlaku_mulai')
            ->first();

        if ($terakhir && $terakhir->berlaku_sampai !== null) {
            $terakhir->update(['berlaku_sampai' => null]);
        }
    }

    protected static function pesanPeriodeBentrok(): string
    {
        return 'Sudah ada periode yang berlaku mulai tanggal ini. Pilih tanggal lain.';
    }
}
