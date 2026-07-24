<?php

namespace Tests\Feature;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test data yang digunakan (input manual di UI untuk verifikasi):
 *
 * ┌──────────────────┬──────────────┬────────────────┬─────────────────┐
 * │ Bahan            │ Gram/sajian  │ Jumlah sajian  │ Harga/100g      │
 * ├──────────────────┼──────────────┼────────────────┼─────────────────┤
 * │ Nasi Putih (Test)│ 200 g        │ 1              │ Rp 2.000        │
 * │ Daging Ayam (Test│ 100 g        │ 1              │ Rp 4.000        │
 * └──────────────────┴──────────────┴────────────────┴─────────────────┘
 * Jumlah Porsi menu : 1
 * Kelompok (AKG)    : SD_4_6 (energi=1950, protein=52.5, lemak=65, karbo=290)
 * Anggaran/porsi    : Rp 15.000 (sd4_ibu_menyusui)
 *
 * Target makan siang SD_4_6 (PCT=0.325):
 *   energi      : round(1950×0.325, 1) = 633.8 kkal
 *   protein     : round(52.5×0.325, 1) = 17.1 g
 *   lemak       : round(65×0.325, 1)   = 21.1 g
 *   karbohidrat : round(290×0.325, 1)  = 94.3 g
 *
 * Hasil yang diharapkan:
 *   Energi        : 560 kkal  (88,4% AKG SD_4_6)
 *   Protein       : 28 g      (163,7% AKG SD_4_6)
 *   Lemak         : 10 g      (47,4% AKG SD_4_6)
 *   Karbohidrat   : 80 g      (84,8% AKG SD_4_6)
 *   Total biaya   : Rp 8.000
 *   Cost/porsi    : Rp 8.000
 *   Selisih       : Rp 7.000 (sisa dari anggaran)
 */
class SimulasiKalkulasiTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;

    private BahanPangan $nasi;

    private BahanPangan $ayam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->create([
            'role' => 'ahli_gizi',
            'unit_sppg' => 'SPPG Test',
        ]);

        // Nasi Putih — BDD 100%, angka gizi bulat agar mudah diverifikasi
        $this->nasi = BahanPangan::create([
            'kode' => 'TES-001',
            'nama_bahan' => 'Nasi Putih (Test)',
            'kategori' => 'Serealia',
            'bdd' => 100,
            'energi' => 180,   // kkal/100g
            'protein' => 4,     // g/100g
            'lemak' => 0,
            'karbohidrat' => 40,    // g/100g
            'serat' => 0,
            'kalsium' => 0,
            'besi' => 0,
            'vit_c' => 0,
            'is_active' => true,
        ]);

        // Daging Ayam — BDD 100%, angka gizi bulat
        $this->ayam = BahanPangan::create([
            'kode' => 'TES-002',
            'nama_bahan' => 'Daging Ayam (Test)',
            'kategori' => 'Daging',
            'bdd' => 100,
            'energi' => 200,   // kkal/100g
            'protein' => 20,    // g/100g
            'lemak' => 10,    // g/100g
            'karbohidrat' => 0,
            'serat' => 0,
            'kalsium' => 0,
            'besi' => 0,
            'vit_c' => 0,
            'is_active' => true,
        ]);

        // Harga aktif: berlaku dari 2026-01-01 tanpa batas
        HargaBahan::create([
            'bahan_pangan_id' => $this->nasi->id,
            'harga_per_100g' => 2000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        HargaBahan::create([
            'bahan_pangan_id' => $this->ayam->id,
            'harga_per_100g' => 4000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        // Anggaran porsi kedua kelompok = Rp 15.000
        foreach (['sd4_ibu_menyusui', 'balita_sd3'] as $kelompok) {
            AnggaranPorsi::create([
                'kelompok' => $kelompok,
                'anggaran_per_porsi' => 15000,
                'berlaku_mulai' => '2026-01-01',
                'berlaku_sampai' => null,
                'created_by' => $this->ahliGizi->id,
            ]);
        }
    }

    /** Payload default: 1 porsi, 2 bahan, tanggal 2026-05-08, kelompok SD_4_6 */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'bahans' => [
                ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1],
                ['id' => $this->ayam->id, 'gram' => 100, 'porsi' => 1],
            ],
            'jumlah_porsi' => 1,
            'tanggal' => '2026-05-08',
            'kelompok' => 'SD_4_6',
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Akses & validasi
    // ──────────────────────────────────────────────────────────────────────────

    public function test_kalkulasi_membutuhkan_autentikasi(): void
    {
        $this->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_kalkulasi_menolak_role_bukan_ahli_gizi(): void
    {
        $akuntan = User::factory()->create([
            'role' => 'akuntan',
            'unit_sppg' => 'SPPG Test',
        ]);

        $this->actingAs($akuntan)
            ->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertForbidden();
    }

    public function test_kalkulasi_validasi_bahans_wajib_diisi(): void
    {
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans' => [],
                'jumlah_porsi' => 1,
                'tanggal' => '2026-05-08',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bahans']);
    }

    public function test_kalkulasi_validasi_gram_harus_positif(): void
    {
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload([
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 0, 'porsi' => 1],
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bahans.0.gram']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Kalkulasi gizi
    // Rumus: faktor = (gram × bdd/100) / 100 ; gizi = faktor × nilai_per_100g × sajian
    //
    // Nasi  200g, bdd=100 → faktor=2.0 → energi=360, protein=8, karbo=80
    // Ayam  100g, bdd=100 → faktor=1.0 → energi=200, protein=20, lemak=10
    // Total                             → energi=560, protein=28, lemak=10, karbo=80
    // ──────────────────────────────────────────────────────────────────────────

    public function test_kalkulasi_menghitung_total_gizi_dengan_benar(): void
    {
        $gizi = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertOk()
            ->json('gizi');

        $this->assertEquals(560.0, $gizi['energi']);
        $this->assertEquals(28.0, $gizi['protein']);
        $this->assertEquals(10.0, $gizi['lemak']);
        $this->assertEquals(80.0, $gizi['karbohidrat']);
        $this->assertEquals(0.0, $gizi['serat']);
        $this->assertEquals(0.0, $gizi['kalsium']);
        $this->assertEquals(0.0, $gizi['besi']);
        $this->assertEquals(0.0, $gizi['vit_c']);
    }

    public function test_kalkulasi_menghitung_persen_akg_dengan_benar(): void
    {
        // Kelompok SD_4_6 → targetSajian('SD_4_6', 'siang') dengan PCT=0.325:
        //   energi  : round(1950×0.325, 1) = 633.8 → 560/633.8×100 = 88.4%
        //   protein : round(52.5×0.325, 1) = 17.1  → 28/17.1×100   = 163.7%
        //   lemak   : round(65×0.325, 1)   = 21.1  → 10/21.1×100   = 47.4%
        //   karbo   : round(290×0.325, 1)  = 94.3  → 80/94.3×100   = 84.8%
        //   mikro (serat, kalsium, besi, vit_c): fallback ke MAKAN_SIANG, nilai 0 → 0%
        $persen = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertOk()
            ->json('persen_akg');

        $this->assertEquals(88.4, $persen['energi']);
        $this->assertEquals(163.7, $persen['protein']);
        $this->assertEquals(47.4, $persen['lemak']);
        $this->assertEquals(84.8, $persen['karbohidrat']);
        $this->assertEquals(0.0, $persen['serat']);
        $this->assertEquals(0.0, $persen['kalsium']);
        $this->assertEquals(0.0, $persen['besi']);
        $this->assertEquals(0.0, $persen['vit_c']);
    }

    public function test_kalkulasi_gizi_skala_dengan_jumlah_sajian(): void
    {
        // sajian=10 (batch untuk 10 orang), jumlah_porsi=10
        // API mengembalikan gizi PER ORANG = total_batch / jumlah_porsi
        // energi per orang = 560 kkal (sama seperti sajian=1, porsi=1)
        $gizi = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload([
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 10],
                    ['id' => $this->ayam->id, 'gram' => 100, 'porsi' => 10],
                ],
                'jumlah_porsi' => 10,
            ]))
            ->assertOk()
            ->json('gizi');

        $this->assertEquals(560.0, $gizi['energi']);
        $this->assertEquals(28.0, $gizi['protein']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Kalkulasi biaya
    // Rumus: biaya_bahan = (gram × sajian / 100) × harga_per_100g
    //
    // Nasi : (200 × 1 / 100) × 2000 = 4.000
    // Ayam : (100 × 1 / 100) × 4000 = 4.000
    // Total biaya = 8.000 ; cost/porsi (÷1) = 8.000
    // selisih = 15.000 − 8.000 = 7.000
    // ──────────────────────────────────────────────────────────────────────────

    public function test_kalkulasi_menghitung_total_biaya_dengan_benar(): void
    {
        $biaya = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertOk()
            ->json('biaya');

        $this->assertEquals(8000, $biaya['total']);
        $this->assertEquals(8000, $biaya['cost_per_porsi']);
        $this->assertEquals(15000, $biaya['anggaran']);
        $this->assertEquals(7000, $biaya['selisih']);
    }

    public function test_kalkulasi_biaya_skala_dengan_jumlah_sajian_dan_porsi(): void
    {
        // sajian=10, jumlah_porsi=10 → total biaya ×10, cost/porsi tetap sama
        // Nasi : (200×10/100)×2000 = 40.000
        // Ayam : (100×10/100)×4000 = 40.000
        // Total = 80.000 ; cost/porsi = 80.000/10 = 8.000
        $biaya = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload([
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 10],
                    ['id' => $this->ayam->id, 'gram' => 100, 'porsi' => 10],
                ],
                'jumlah_porsi' => 10,
            ]))
            ->assertOk()
            ->json('biaya');

        $this->assertEquals(80000, $biaya['total']);
        $this->assertEquals(8000, $biaya['cost_per_porsi']);
    }

    public function test_kalkulasi_anggaran_sesuai_kelompok(): void
    {
        // Buat anggaran berbeda untuk balita_sd3 = Rp 12.000
        AnggaranPorsi::where('kelompok', 'balita_sd3')->update(['anggaran_per_porsi' => 12000]);

        // Kelompok AKG 'SD_1_3' → toAnggaranKelompok('SD_1_3') = 'balita_sd3'
        // Sehingga anggaran yang digunakan adalah 12.000 (balita_sd3)
        $biaya = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload([
                'kelompok' => 'SD_1_3',
            ]))
            ->assertOk()
            ->json('biaya');

        $this->assertEquals(12000, $biaya['anggaran']);
        $this->assertEquals(8000, $biaya['total']);
        // selisih = anggaran×porsi − total = 12000 − 8000 = 4000
        $this->assertEquals(4000, $biaya['selisih']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Detail per bahan
    // ──────────────────────────────────────────────────────────────────────────

    public function test_kalkulasi_mengembalikan_detail_per_bahan(): void
    {
        $detail = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload())
            ->assertOk()
            ->json('detail');

        $this->assertCount(2, $detail);

        // Bahan pertama: Nasi
        $this->assertEquals($this->nasi->id, $detail[0]['id']);
        $this->assertEquals(200, $detail[0]['gram']);
        $this->assertTrue($detail[0]['ada_harga']);
        $this->assertEquals(4000, $detail[0]['biaya']);

        // Bahan kedua: Ayam
        $this->assertEquals($this->ayam->id, $detail[1]['id']);
        $this->assertEquals(100, $detail[1]['gram']);
        $this->assertTrue($detail[1]['ada_harga']);
        $this->assertEquals(4000, $detail[1]['biaya']);
    }

    public function test_kalkulasi_menandai_bahan_tanpa_harga(): void
    {
        $bahanTanpaHarga = BahanPangan::create([
            'kode' => 'TES-003',
            'nama_bahan' => 'Bahan Tanpa Harga (Test)',
            'kategori' => 'Lainnya',
            'bdd' => 100,
            'energi' => 50,
            'protein' => 1,
            'lemak' => 0,
            'karbohidrat' => 10,
            'serat' => 0,
            'kalsium' => 0,
            'besi' => 0,
            'vit_c' => 0,
            'is_active' => true,
        ]);

        $detail = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $this->payload([
                'bahans' => [
                    ['id' => $bahanTanpaHarga->id, 'gram' => 100, 'porsi' => 1],
                ],
            ]))
            ->assertOk()
            ->json('detail');

        $this->assertFalse($detail[0]['ada_harga']);
        $this->assertEquals(0, $detail[0]['biaya']);
    }
}
