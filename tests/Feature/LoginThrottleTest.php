<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_gagal_berulang_diblokir_setelah_5_percobaan(): void
    {
        // Regresi B3: POST /login dulu tanpa throttle sama sekali → brute-force
        // password bebas. Sekarang dibatasi throttle:5,1 (5 percobaan/menit).
        $user = User::factory()->ahliGizi()->create();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'salah-password',
            ])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 dalam window yang sama harus diblokir throttle (429).
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'salah-password',
        ])->assertStatus(429);
    }

    public function test_login_valid_masih_bisa_sebelum_kena_limit(): void
    {
        $user = User::factory()->ahliGizi()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
