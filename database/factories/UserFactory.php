<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => 'ahli_gizi',
            'unit_sppg'         => 'SPPG Test',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function ahliGizi(string $unit = 'SPPG Test'): static
    {
        return $this->state(['role' => 'ahli_gizi', 'unit_sppg' => $unit]);
    }

    public function akuntan(string $unit = 'SPPG Test'): static
    {
        return $this->state(['role' => 'akuntan', 'unit_sppg' => $unit]);
    }

    public function ketuaSppg(string $unit = 'SPPG Test'): static
    {
        return $this->state(['role' => 'ketua_sppg', 'unit_sppg' => $unit]);
    }

    public function superadmin(): static
    {
        return $this->state(['role' => 'superadmin', 'unit_sppg' => null]);
    }
}