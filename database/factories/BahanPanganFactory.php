<?php

namespace Database\Factories;

use App\Models\BahanPangan;
use Illuminate\Database\Eloquent\Factories\Factory;

class BahanPanganFactory extends Factory
{
    protected $model = BahanPangan::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'kode' => 'FAK-'.str_pad($counter, 3, '0', STR_PAD_LEFT),
            'nama_bahan' => fake()->words(2, true),
            'kategori' => fake()->randomElement(['Serealia', 'Daging', 'Sayuran', 'Buah', 'Kacang']),
            'bdd' => 100,
            'energi' => fake()->numberBetween(50, 400),
            'protein' => fake()->numberBetween(1, 30),
            'lemak' => fake()->numberBetween(0, 20),
            'karbohidrat' => fake()->numberBetween(5, 80),
            'serat' => fake()->numberBetween(0, 5),
            'kalsium' => fake()->numberBetween(0, 200),
            'besi' => fake()->randomFloat(1, 0, 5),
            'vit_c' => fake()->randomFloat(1, 0, 50),
            'is_active' => true,
        ];
    }

    public function bddPenuh(): static
    {
        return $this->state(['bdd' => 100]);
    }

    public function bddParsial(int $bdd = 58): static
    {
        return $this->state(['bdd' => $bdd]);
    }
}
