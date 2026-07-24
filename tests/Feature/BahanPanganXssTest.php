<?php

namespace Tests\Feature;

use App\Models\BahanPangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BahanPanganXssTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    private function basePayload(string $namaBahan): array
    {
        return [
            'kode' => 'XSS001',
            'nama_bahan' => $namaBahan,
            'kategori' => 'Serealia',
        ];
    }

    public function test_store_bahan_pangan_dengan_nama_xss_di_escape_pada_flash_dan_halaman_index(): void
    {
        // Regresi B7: flash 'success' dulu di-render {!! !!} tanpa escape,
        // nama_bahan mentah bisa inject <img onerror> / <script>. Sekarang
        // di-escape via e() di controller sebelum masuk string flash.
        $ketua = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($ketua)
            ->post(route('bahan-pangan.store'), $this->basePayload(self::PAYLOAD));

        $response->assertSessionHas('success');
        $flash = session('success');
        $this->assertStringNotContainsString(self::PAYLOAD, $flash);
        $this->assertStringContainsString('&lt;img', $flash);

        $indexHtml = $this->actingAs($ketua)->get(route('bahan-pangan.index'))->getContent();
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $indexHtml);
    }

    public function test_update_bahan_pangan_dengan_nama_xss_di_escape_pada_flash(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();
        $bahan = BahanPangan::factory()->create(['nama_bahan' => 'Beras Putih']);

        $response = $this->actingAs($ketua)
            ->put(route('bahan-pangan.update', $bahan), $this->basePayload(self::PAYLOAD));

        $response->assertSessionHas('success');
        $flash = session('success');
        $this->assertStringNotContainsString(self::PAYLOAD, $flash);
        $this->assertStringContainsString('&lt;img', $flash);
    }

    public function test_destroy_bahan_pangan_dengan_nama_xss_di_escape_pada_flash(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();
        $bahan = BahanPangan::factory()->create(['nama_bahan' => self::PAYLOAD]);

        $response = $this->actingAs($ketua)->delete(route('bahan-pangan.destroy', $bahan));

        $response->assertSessionHas('success');
        $flash = session('success');
        $this->assertStringNotContainsString(self::PAYLOAD, $flash);
        $this->assertStringContainsString('&lt;img', $flash);
    }
}
