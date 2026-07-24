<?php

namespace App\Models;

use App\Models\Concerns\PeriodeBerlaku;
use Illuminate\Database\Eloquent\Model;

class HargaBahan extends Model
{
    use PeriodeBerlaku;

    protected $fillable = [
        'bahan_pangan_id', 'harga_per_100g', 'berlaku_mulai', 'berlaku_sampai', 'keterangan',
    ];

    protected $casts = [
        'berlaku_mulai' => 'date:Y-m-d',
        'berlaku_sampai' => 'date:Y-m-d',
        'harga_per_100g' => 'decimal:2',
    ];

    public function bahanPangan()
    {
        return $this->belongsTo(BahanPangan::class);
    }

    protected static function pesanPeriodeBentrok(): string
    {
        return 'Sudah ada tarif untuk bahan ini yang berlaku mulai tanggal tersebut. Pilih tanggal lain.';
    }

    /**
     * Ambil harga aktif untuk bahan + unit tertentu pada tanggal tertentu.
     */
    public static function hargaAktif(int $bahanId, ?string $tanggal = null): float
    {
        $tgl = $tanggal ?? today()->toDateString();

        $harga = static::where('bahan_pangan_id', $bahanId)
            ->where('berlaku_mulai', '<=', $tgl)
            ->where(function ($q) use ($tgl) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tgl);
            })
            ->orderByDesc('berlaku_mulai')
            ->value('harga_per_100g');

        return (float) ($harga ?? 0);
    }

    /**
     * Versi batch dari hargaAktif(): satu query untuk banyak bahan sekaligus,
     * hindari N+1 saat loop per-item (audit D3).
     *
     * @param  int[]  $bahanIds
     * @return array<int, float> keyed by bahan_pangan_id
     */
    public static function hargaAktifBatch(array $bahanIds, ?string $tanggal = null): array
    {
        if (empty($bahanIds)) {
            return [];
        }

        $tgl = $tanggal ?? today()->toDateString();

        return static::whereIn('bahan_pangan_id', $bahanIds)
            ->where('berlaku_mulai', '<=', $tgl)
            ->where(function ($q) use ($tgl) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tgl);
            })
            ->orderByDesc('berlaku_mulai')
            ->get(['bahan_pangan_id', 'harga_per_100g'])
            ->unique('bahan_pangan_id')
            ->mapWithKeys(fn ($h) => [$h->bahan_pangan_id => (float) $h->harga_per_100g])
            ->all();
    }
}
