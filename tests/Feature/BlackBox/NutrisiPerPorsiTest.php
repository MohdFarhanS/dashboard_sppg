<?php

namespace Tests\Feature\BlackBox;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Black-box test: Perhitungan Nilai Gizi per Porsi (Skenario 1-6)
 *
 * Endpoint: POST /simulasi/kalkulasi  (route: simulasi.kalkulasi, middleware: role:ahli_gizi)
 *
 * Formulasi yang diuji:
 *   bdd_fraction = bahan.bdd / 100
 *   faktor       = (gram * bdd_fraction) / 100
 *   nilai_batch  = faktor * nutrisi_per_100g * porsi_bahan
 *   gizi_per_org = SUM(nilai_batch) / jumlah_porsi
 *
 * Data acuan:
 *   nasi (BBT-001): BDD=100%, energi=180, protein=4, lemak=0, karbo=40
 *   ayam (BBT-002): BDD=50%,  energi=200, protein=20, lemak=10, karbo=0
 */
class NutrisiPerPorsiTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;
    private BahanPangan $nasi;
    private BahanPangan $ayam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->ahliGizi()->create();

        $this->nasi = BahanPangan::create([
            'kode'        => 'BBT-001',
            'nama_bahan'  => 'Nasi Putih (BBT)',
            'kategori'    => 'Serealia',
            'bdd'         => 100,
            'energi'      => 180,
            'protein'     => 4,
            'lemak'       => 0,
            'karbohidrat' => 40,
            'serat'       => 0,
            'kalsium'     => 0,
            'besi'        => 0,
            'vit_c'       => 0,
            'is_active'   => true,
        ]);

        $this->ayam = BahanPangan::create([
            'kode'        => 'BBT-002',
            'nama_bahan'  => 'Daging Ayam (BBT)',
            'kategori'    => 'Daging',
            'bdd'         => 50,
            'energi'      => 200,
            'protein'     => 20,
            'lemak'       => 10,
            'karbohidrat' => 0,
            'serat'       => 0,
            'kalsium'     => 0,
            'besi'        => 0,
            'vit_c'       => 0,
            'is_active'   => true,
        ]);

        HargaBahan::create([
            'bahan_pangan_id' => $this->nasi->id,
            'harga_per_100g'  => 2000,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
        ]);

        HargaBahan::create([
            'bahan_pangan_id' => $this->ayam->id,
            'harga_per_100g'  => 5000,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
        ]);

        foreach (['sd4_ibu_menyusui', 'balita_sd3'] as $kelompok) {
            AnggaranPorsi::create([
                'kelompok'           => $kelompok,
                'anggaran_per_porsi' => 15000,
                'berlaku_mulai'      => '2026-01-01',
                'berlaku_sampai'     => null,
                'created_by'         => $this->ahliGizi->id,
            ]);
        }
    }

    private function postKalkulasi(array $payload): TestResponse
    {
        return $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), array_merge([
                'jumlah_porsi' => 1,
                'tanggal'      => '2026-05-08',
                'kelompok'     => 'SD_4_6',
            ], $payload));
    }

    /**
     * Skenario 1: Input gram valid untuk bahan dengan BDD 100%.
     *
     * Nasi 100g, BDD=100:
     *   faktor = (100 * 1.0) / 100 = 1.0
     *   energi = 1.0 * 180 * 1 / 1 = 180 kkal
     *   protein = 1.0 * 4 = 4 g  |  lemak = 0 g  |  karbo = 40 g
     */
    public function test_skenario_01_gram_valid_bdd_100_persen_gizi_proporsional(): void
    {
        $gizi = $this->postKalkulasi([
            'bahans' => [['id' => $this->nasi->id, 'gram' => 100, 'porsi' => 1]],
        ])->assertOk()->json('gizi');

        $this->assertEquals(180.0, $gizi['energi'],      'energi harus 180 kkal (100g × 180/100g)');
        $this->assertEquals(4.0,   $gizi['protein'],     'protein harus 4 g');
        $this->assertEquals(0.0,   $gizi['lemak'],       'lemak harus 0 g');
        $this->assertEquals(40.0,  $gizi['karbohidrat'], 'karbohidrat harus 40 g');
    }

    /**
     * Skenario 2: Input gram valid untuk bahan dengan BDD < 100%.
     *
     * Ayam 100g, BDD=50:
     *   faktor = (100 * 0.50) / 100 = 0.50
     *   energi = 0.50 * 200 = 100 kkal
     *   protein = 0.50 * 20 = 10 g  |  lemak = 0.50 * 10 = 5 g  |  karbo = 0 g
     */
    public function test_skenario_02_bdd_di_bawah_100_persen_faktor_bdd_diterapkan(): void
    {
        $gizi = $this->postKalkulasi([
            'bahans' => [['id' => $this->ayam->id, 'gram' => 100, 'porsi' => 1]],
        ])->assertOk()->json('gizi');

        $this->assertEquals(100.0, $gizi['energi'],      'energi harus 100 kkal (50% dari 200 kkal/100g)');
        $this->assertEquals(10.0,  $gizi['protein'],     'protein harus 10 g (50% dari 20 g/100g)');
        $this->assertEquals(5.0,   $gizi['lemak'],       'lemak harus 5 g (50% dari 10 g/100g)');
        $this->assertEquals(0.0,   $gizi['karbohidrat'], 'karbohidrat harus 0 g');
    }

    /**
     * Skenario 3: Akumulasi gizi dari beberapa bahan dalam satu menu.
     *
     * Nasi 200g (BDD=100): faktor=2.0 → energi=360, protein=8, lemak=0, karbo=80
     * Ayam 100g (BDD=50):  faktor=0.5 → energi=100, protein=10, lemak=5, karbo=0
     * Akumulasi            energi=460, protein=18, lemak=5, karbo=80
     */
    public function test_skenario_03_akumulasi_gizi_dari_beberapa_bahan(): void
    {
        $gizi = $this->postKalkulasi([
            'bahans' => [
                ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1],
                ['id' => $this->ayam->id, 'gram' => 100, 'porsi' => 1],
            ],
        ])->assertOk()->json('gizi');

        $this->assertEquals(460.0, $gizi['energi'],      'energi total = 360 + 100 = 460 kkal');
        $this->assertEquals(18.0,  $gizi['protein'],     'protein total = 8 + 10 = 18 g');
        $this->assertEquals(5.0,   $gizi['lemak'],       'lemak total = 0 + 5 = 5 g');
        $this->assertEquals(80.0,  $gizi['karbohidrat'], 'karbo total = 80 + 0 = 80 g');
    }

    /**
     * Skenario 4: Input gram berupa karakter non-numerik.
     *
     * gram = 'abc' harus ditolak dengan HTTP 422 dan error pada bahans.0.gram.
     */
    public function test_skenario_04_gram_non_numerik_ditolak_dengan_validasi(): void
    {
        $this->postKalkulasi([
            'bahans' => [['id' => $this->nasi->id, 'gram' => 'abc', 'porsi' => 1]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bahans.0.gram']);
    }

    /**
     * Skenario 5: Input gram nol atau negatif (BVA — Boundary Value Analysis).
     *
     * Aturan validasi: min:1  → gram=0 dan gram=-50 keduanya ditolak.
     */
    public function test_skenario_05_gram_nol_dan_negatif_ditolak_bva(): void
    {
        $base = ['jumlah_porsi' => 1, 'tanggal' => '2026-05-08', 'kelompok' => 'SD_4_6'];

        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), array_merge($base, [
                'bahans' => [['id' => $this->nasi->id, 'gram' => 0, 'porsi' => 1]],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bahans.0.gram']);

        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), array_merge($base, [
                'bahans' => [['id' => $this->nasi->id, 'gram' => -50, 'porsi' => 1]],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bahans.0.gram']);
    }

    /**
     * Skenario 6: Persentase AKG sesuai kelompok sasaran.
     *
     * Nasi 200g (BDD=100) → energi per orang = 360 kkal
     *
     * TK_PAUD: target energi siang = round(1400 × 0.325, 1) = 455.0
     *          persen_akg.energi   = round(360 / 455.0 × 100, 1) = 79.1%
     *
     * SMA:     target energi siang = round(2375 × 0.325, 1) = 771.9
     *          persen_akg.energi   = round(360 / 771.9 × 100, 1) = 46.6%
     */
    public function test_skenario_06_persen_akg_berbeda_untuk_kelompok_sasaran_berbeda(): void
    {
        $bahans = [['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1]];

        $pctTkPaud = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans'       => $bahans,
                'jumlah_porsi' => 1,
                'tanggal'      => '2026-05-08',
                'kelompok'     => 'TK_PAUD',
            ])
            ->assertOk()
            ->json('persen_akg.energi');

        $pctSma = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans'       => $bahans,
                'jumlah_porsi' => 1,
                'tanggal'      => '2026-05-08',
                'kelompok'     => 'SMA',
            ])
            ->assertOk()
            ->json('persen_akg.energi');

        $this->assertEqualsWithDelta(79.1, $pctTkPaud, 0.2,
            'persen AKG energi TK_PAUD harus ~79.1% (target 455 kkal)');

        $this->assertEqualsWithDelta(46.6, $pctSma, 0.2,
            'persen AKG energi SMA harus ~46.6% (target 771.9 kkal)');

        $this->assertGreaterThan($pctSma, $pctTkPaud,
            'TK_PAUD (target lebih kecil) harus menghasilkan % AKG lebih tinggi dari SMA untuk menu yang sama');
    }
}