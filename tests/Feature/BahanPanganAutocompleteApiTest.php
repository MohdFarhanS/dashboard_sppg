<?php

namespace Tests\Feature;

use App\Models\BahanPangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BahanPanganAutocompleteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_search_mengembalikan_nama_bahan_apa_adanya_untuk_di_escape_client(): void
    {
        // Regresi B8: DOM XSS di autocomplete simulasi/menu-harian.edit —
        // fetchBahan() dulu innerHTML data API mentah tanpa escape. Sekarang
        // JS (escapeHtml()) yang tanggung jawab escape sebelum innerHTML;
        // API sendiri tetap kirim raw text (dipakai juga utk .value input,
        // yang tidak boleh di-encode di server).
        $ahliGizi = User::factory()->ahliGizi()->create();
        $payload = '<img src=x onerror=alert(1)>';
        BahanPangan::factory()->create(['kode' => 'XSS001', 'nama_bahan' => $payload]);

        $response = $this->actingAs($ahliGizi)
            ->getJson('/api/bahan-pangan/search?q=XSS001&limit=8');

        $response->assertOk();
        $this->assertSame($payload, $response->json('0.nama_bahan'));
    }
}
