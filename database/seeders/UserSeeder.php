<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $unitSppg = config('app.unit_sppg', 'SPPG');

        User::create([
            'name'         => 'Super Admin',
            'nama_lengkap' => 'Super Administrator',
            'email'        => 'superadmin@mbg.id',
            'password'     => Hash::make('password123'),
            'role'         => 'superadmin',
            'unit_sppg'    => null,
            'is_active'    => true,
        ]);

        User::create([
            'name'         => 'Ketua SPPG',
            'nama_lengkap' => 'Ketua SPPG Utama',
            'email'        => 'ketua@mbg.id',
            'password'     => Hash::make('password123'),
            'role'         => 'ketua_sppg',
            'unit_sppg'    => $unitSppg,
            'is_active'    => true,
        ]);

        User::create([
            'name'         => 'Ahli Gizi',
            'nama_lengkap' => 'Ahli Gizi SPPG',
            'email'        => 'gizi@mbg.id',
            'password'     => Hash::make('password123'),
            'role'         => 'ahli_gizi',
            'unit_sppg'    => $unitSppg,
            'is_active'    => true,
        ]);

        User::create([
            'name'         => 'Akuntan',
            'nama_lengkap' => 'Akuntan SPPG',
            'email'        => 'akuntan@mbg.id',
            'password'     => Hash::make('password123'),
            'role'         => 'akuntan',
            'unit_sppg'    => $unitSppg,
            'is_active'    => true,
        ]);
    }
}
