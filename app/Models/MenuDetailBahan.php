<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuDetailBahan extends Model
{
    protected $fillable = [
        'menu_harian_id', 'bahan_pangan_id', 'jumlah_gram', 'jumlah_porsi', 'harga_per_100g',
    ];

    public function menuHarian(): BelongsTo
    {
        return $this->belongsTo(MenuHarian::class);
    }

    public function bahanPangan(): BelongsTo
    {
        return $this->belongsTo(BahanPangan::class);
    }
}
