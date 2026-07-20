<?php

namespace Database\Factories;

use App\Models\AnggaranPorsi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnggaranPorsiFactory extends Factory
{
    protected $model = AnggaranPorsi::class;

    public function definition(): array
    {
        return [
            'kelompok'           => fake()->randomElement(['sd4_ibu_menyusui', 'balita_sd3']),
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai'      => '2026-01-01',
            'berlaku_sampai'     => null,
            'keterangan'         => null,
            'created_by'         => User::factory(),
        ];
    }

    public function sd4IbuMenyusui(float $nominal = 15000): static
    {
        return $this->state(['kelompok' => 'sd4_ibu_menyusui', 'anggaran_per_porsi' => $nominal]);
    }

    public function balitaSd3(float $nominal = 12000): static
    {
        return $this->state(['kelompok' => 'balita_sd3', 'anggaran_per_porsi' => $nominal]);
    }

    public function aktif(string $mulai = '2026-01-01'): static
    {
        return $this->state(['berlaku_mulai' => $mulai, 'berlaku_sampai' => null]);
    }
}