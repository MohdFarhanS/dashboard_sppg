<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesanMasukThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'nama' => 'Warga Peduli',
            'no_hp' => '08123456789',
            'pesan' => 'Pesan uji throttle formulir kontak publik.',
        ];
    }

    public function test_kirim_pesan_berulang_diblokir_setelah_5_percobaan(): void
    {
        // Regresi B6: POST /pesan dulu tanpa throttle → spam/DoS pesan_masuks
        // dari form kontak publik tanpa auth. Sekarang dibatasi throttle:5,1.
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/pesan', $this->payload())
                ->assertRedirect(route('landing'));
        }

        $this->post('/pesan', $this->payload())
            ->assertStatus(429);
    }

    public function test_kirim_pesan_valid_masih_bisa_sebelum_kena_limit(): void
    {
        $this->post('/pesan', $this->payload())
            ->assertRedirect(route('landing'))
            ->assertSessionHas('pesan_terkirim', true);

        $this->assertDatabaseHas('pesan_masuks', [
            'nama' => 'Warga Peduli',
            'no_hp' => '08123456789',
        ]);
    }
}
