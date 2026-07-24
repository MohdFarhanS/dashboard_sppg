<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seeder khusus instance demo/portfolio.
 *
 * Superadmin dibuat dari kredensial pribadi via env (DEMO_SUPERADMIN_EMAIL/
 * PASSWORD), tidak pernah hardcode. Tiga akun operasional pakai password
 * publik (DEMO_PASSWORD) karena memang didokumentasikan di README untuk
 * keperluan testing pengunjung.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $unitSppg = config('app.unit_sppg', 'SPPG');

        $superadminEmail = env('DEMO_SUPERADMIN_EMAIL');
        $superadminPassword = env('DEMO_SUPERADMIN_PASSWORD');

        if (! $superadminEmail || ! $superadminPassword) {
            throw new RuntimeException(
                'DEMO_SUPERADMIN_EMAIL dan DEMO_SUPERADMIN_PASSWORD wajib diisi di .env sebelum demo:reset dijalankan.'
            );
        }

        User::create([
            'name' => 'Super Admin',
            'nama_lengkap' => 'Super Administrator',
            'email' => $superadminEmail,
            'password' => Hash::make($superadminPassword),
            'role' => User::ROLE_SUPERADMIN,
            'unit_sppg' => null,
            'is_active' => true,
        ]);

        $demoPassword = env('DEMO_PASSWORD', 'Demo@SPPG2026');

        $akunDemo = [
            ['name' => 'Ketua SPPG', 'email' => 'ketua@mbg.id', 'role' => User::ROLE_KETUA_SPPG],
            ['name' => 'Ahli Gizi', 'email' => 'gizi@mbg.id', 'role' => User::ROLE_AHLI_GIZI],
            ['name' => 'Akuntan', 'email' => 'akuntan@mbg.id', 'role' => User::ROLE_AKUNTAN],
        ];

        foreach ($akunDemo as $akun) {
            User::create([
                'name' => $akun['name'],
                'nama_lengkap' => "{$akun['name']} (Demo)",
                'email' => $akun['email'],
                'password' => Hash::make($demoPassword),
                'role' => $akun['role'],
                'unit_sppg' => $unitSppg,
                'is_active' => true,
            ]);
        }
    }
}
