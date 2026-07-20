<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanPangan extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode', 'kode_lama', 'nama_bahan', 'kategori', 'sub_kategori', 'sumber',
        'bdd', 'air', 'energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'abu',
        'kalsium', 'fosfor', 'besi', 'natrium', 'kalium', 'tembaga', 'seng',
        'retinol', 'b_karoten', 'kar_total', 'thiamin', 'riboflavin', 'niasin',
        'vit_c', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scope pencarian
    public function scopeCari($query, $keyword)
    {
        return $query->where('nama_bahan', 'like', "%{$keyword}%")
                     ->orWhere('kode', 'like', "%{$keyword}%");
    }

    // Scope per kategori
    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}