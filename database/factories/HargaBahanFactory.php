<?php

namespace Database\Factories;

use App\Models\BahanPangan;
use App\Models\HargaBahan;
use Illuminate\Database\Eloquent\Factories\Factory;

class HargaBahanFactory extends Factory
{
    protected $model = HargaBahan::class;

    public function definition(): array
    {
        return [
            'bahan_pangan_id' => BahanPangan::factory(),
            'harga_per_100g'  => fake()->numberBetween(500, 10000),
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
            'keterangan'      => null,
        ];
    }

    public function aktif(string $mulai = '2026-01-01'): static
    {
        return $this->state(['berlaku_mulai' => $mulai, 'berlaku_sampai' => null]);
    }

    public function historis(string $mulai = '2025-01-01', string $sampai = '2025-12-31'): static
    {
        return $this->state(['berlaku_mulai' => $mulai, 'berlaku_sampai' => $sampai]);
    }
}