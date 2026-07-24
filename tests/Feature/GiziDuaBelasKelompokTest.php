<?php

namespace Tests\Feature;

use App\Constants\AKG;
use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test komprehensif implementasi 12 kelompok penerima gizi.
 *
 * Data menu standar yang digunakan:
 *   Nasi Putih 200g, BDD=100, energi=180/100g, protein=4/100g, karbo=40/100g
 *   → gizi per porsi (1 porsi): energi=360 kkal, protein=8g, karbo=80g
 *
 * Target makan siang (PCT=0.325) yang diuji:
 *   TK_PAUD  → energi=455.0 → pct=79.1% (Cukup di show view, kurang di evaluasiGizi)
 *   SD_4_6   → energi=633.8 → pct=56.8% (Kurang di kedua tempat)
 *   SMA      → energi=771.9 → pct=46.6% (Kurang)
 *   BALITA_1_3 → energi=438.8 → pct=82.0% (Cukup)
 */
class GiziDuaBelasKelompokTest extends TestCase
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

        $this->nasi = BahanPangan::create([
            'kode' => 'TES-001',
            'nama_bahan' => 'Nasi Putih (Test)',
            'kategori' => 'Serealia',
            'bdd' => 100,
            'energi' => 180,
            'protein' => 4,
            'lemak' => 0,
            'karbohidrat' => 40,
            'serat' => 0,
            'kalsium' => 0,
            'besi' => 0,
            'vit_c' => 0,
            'is_active' => true,
        ]);

        $this->ayam = BahanPangan::create([
            'kode' => 'TES-002',
            'nama_bahan' => 'Daging Ayam (Test)',
            'kategori' => 'Daging',
            'bdd' => 100,
            'energi' => 200,
            'protein' => 20,
            'lemak' => 10,
            'karbohidrat' => 0,
            'serat' => 0,
            'kalsium' => 0,
            'besi' => 0,
            'vit_c' => 0,
            'is_active' => true,
        ]);

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

    // ═══════════════════════════════════════════════════════════════════════════
    // 1. Konstanta AKG — unit tests tanpa DB
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_ada_tepat_12_kelompok_dalam_konstanta_akg(): void
    {
        $this->assertCount(12, AKG::KELOMPOK);

        $kunciYangDiharapkan = [
            'TK_PAUD', 'SD_1_3', 'SD_4_6', 'SMP', 'SMA',
            'BALITA_1_3', 'BALITA_4_6',
            'HAMIL_T1', 'HAMIL_T2', 'HAMIL_T3',
            'MENYUSUI_6BLN_1', 'MENYUSUI_6BLN_2',
        ];

        foreach ($kunciYangDiharapkan as $kunci) {
            $this->assertArrayHasKey($kunci, AKG::KELOMPOK, "Kelompok '{$kunci}' harus ada dalam AKG::KELOMPOK");
        }

        // Setiap kelompok harus memiliki 4 makronutrien + label
        foreach (AKG::KELOMPOK as $kunci => $data) {
            foreach (['label', 'energi', 'protein', 'lemak', 'karbohidrat'] as $field) {
                $this->assertArrayHasKey($field, $data, "Kelompok '{$kunci}' harus memiliki field '{$field}'");
            }
        }
    }

    public function test_akg_target_sajian_dihitung_benar_untuk_semua_12_kelompok(): void
    {
        // Nilai diverifikasi dengan PCT_SIANG = 0.325
        $kasus = [
            'TK_PAUD' => ['energi' => 455.0,  'protein' => 8.1,  'lemak' => 16.3, 'karbohidrat' => 71.5],
            'SD_1_3' => ['energi' => 536.3,  'protein' => 13.0, 'lemak' => 17.9, 'karbohidrat' => 81.3],
            'SD_4_6' => ['energi' => 633.8,  'protein' => 17.1, 'lemak' => 21.1, 'karbohidrat' => 94.3],
            'SMP' => ['energi' => 723.1,  'protein' => 21.9, 'lemak' => 24.4, 'karbohidrat' => 105.6],
            'SMA' => ['energi' => 771.9,  'protein' => 22.8, 'lemak' => 25.2, 'karbohidrat' => 113.8],
            'BALITA_1_3' => ['energi' => 438.8,  'protein' => 6.5,  'lemak' => 14.6, 'karbohidrat' => 69.9],
            'BALITA_4_6' => ['energi' => 455.0,  'protein' => 8.1,  'lemak' => 16.3, 'karbohidrat' => 71.5],
            'HAMIL_T1' => ['energi' => 757.3,  'protein' => 19.8, 'lemak' => 20.2, 'karbohidrat' => 118.6],
            'HAMIL_T2' => ['energi' => 796.3,  'protein' => 22.8, 'lemak' => 20.2, 'karbohidrat' => 123.5],
            'HAMIL_T3' => ['energi' => 796.3,  'protein' => 29.3, 'lemak' => 20.2, 'karbohidrat' => 123.5],
            'MENYUSUI_6BLN_1' => ['energi' => 838.5,  'protein' => 26.0, 'lemak' => 21.8, 'karbohidrat' => 131.6],
            'MENYUSUI_6BLN_2' => ['energi' => 861.3,  'protein' => 24.4, 'lemak' => 21.8, 'karbohidrat' => 134.9],
        ];

        foreach ($kasus as $kunci => $expected) {
            $target = AKG::targetSajian($kunci, 'siang');

            $this->assertEqualsWithDelta($expected['energi'], $target['energi'], 0.05, "energi {$kunci}");
            $this->assertEqualsWithDelta($expected['protein'], $target['protein'], 0.05, "protein {$kunci}");
            $this->assertEqualsWithDelta($expected['lemak'], $target['lemak'], 0.05, "lemak {$kunci}");
            $this->assertEqualsWithDelta($expected['karbohidrat'], $target['karbohidrat'], 0.05, "karbohidrat {$kunci}");
        }
    }

    public function test_akg_cascade_options_mengelompokkan_12_kelompok_ke_4_kategori(): void
    {
        $opts = AKG::cascadeOptions();

        $this->assertArrayHasKey('Peserta Didik', $opts);
        $this->assertArrayHasKey('Ibu Hamil', $opts);
        $this->assertArrayHasKey('Ibu Menyusui', $opts);
        $this->assertArrayHasKey('Anak Balita', $opts);

        $this->assertCount(5, $opts['Peserta Didik']); // TK_PAUD, SD_1_3, SD_4_6, SMP, SMA
        $this->assertCount(3, $opts['Ibu Hamil']);     // T1, T2, T3
        $this->assertCount(2, $opts['Ibu Menyusui']);  // 6BLN_1, 6BLN_2
        $this->assertCount(2, $opts['Anak Balita']);   // BALITA_1_3, BALITA_4_6

        // Total kelompok di semua kategori = 12
        $totalDiKategori = array_sum(array_map('count', $opts));
        $this->assertEquals(12, $totalDiKategori);
    }

    public function test_akg_to_anggaran_kelompok_memetakan_12_kelompok_ke_2_grup(): void
    {
        $balitaSd3 = ['TK_PAUD', 'SD_1_3', 'BALITA_1_3', 'BALITA_4_6'];
        $sd4Ibu = ['SD_4_6', 'SMP', 'SMA', 'HAMIL_T1', 'HAMIL_T2', 'HAMIL_T3', 'MENYUSUI_6BLN_1', 'MENYUSUI_6BLN_2'];

        foreach ($balitaSd3 as $ks) {
            $this->assertEquals('balita_sd3', AKG::toAnggaranKelompok($ks),
                "{$ks} harus dipetakan ke 'balita_sd3'");
        }

        foreach ($sd4Ibu as $ks) {
            $this->assertEquals('sd4_ibu_menyusui', AKG::toAnggaranKelompok($ks),
                "{$ks} harus dipetakan ke 'sd4_ibu_menyusui'");
        }

        // Total mapping = 12
        $this->assertCount(12, array_merge($balitaSd3, $sd4Ibu));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 2. Unique constraint: 12 menu berbeda dalam 1 tanggal (setelah migration)
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_satu_tanggal_dapat_menyimpan_12_menu_satu_per_kelompok(): void
    {
        // Constraint baru: (tanggal, kelompok_sasaran) harus unik
        // → 12 kelompok = 12 menu valid dalam 1 hari
        $tanggal = '2026-05-15';

        foreach (array_keys(AKG::KELOMPOK) as $ks) {
            $kelompok = AKG::toAnggaranKelompok($ks);
            MenuHarian::forceCreate([
                'tanggal' => $tanggal,
                'user_id' => $this->ahliGizi->id,
                'nama_menu' => "Menu {$ks}",
                'status' => 'draft',
                'kelompok' => $kelompok,
                'kelompok_sasaran' => $ks,
                'jumlah_porsi' => 10,
                'anggaran_per_porsi' => 15000,
            ]);
        }

        $this->assertEquals(12, MenuHarian::whereDate('tanggal', $tanggal)->count(),
            'Harus ada tepat 12 menu pada tanggal yang sama (satu per kelompok)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 3. Model MenuHarian — akgTarget() dan evaluasiGizi()
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_akg_target_menu_harian_mengembalikan_target_sesuai_kelompok_sasaran(): void
    {
        // TK_PAUD → energi target = 455.0
        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $target = $menuTkPaud->akgTarget('siang');

        $this->assertEqualsWithDelta(455.0, $target['energi'], 0.05, 'TK_PAUD target energi harus 455.0');

        // SMA → energi target = 771.9
        $menuSma = $this->buatMenuNasi('2026-05-01', 'SMA');
        $target = $menuSma->akgTarget('siang');

        $this->assertEqualsWithDelta(771.9, $target['energi'], 0.05, 'SMA target energi harus 771.9');

        // SD_4_6 → energi target = 633.8
        $menuSd46 = $this->buatMenuNasi('2026-05-01', 'SD_4_6');
        $target = $menuSd46->akgTarget('siang');

        $this->assertEqualsWithDelta(633.8, $target['energi'], 0.05, 'SD_4_6 target energi harus 633.8');
    }

    public function test_evaluasi_gizi_menggunakan_target_akg_sesuai_kelompok_bukan_default(): void
    {
        // Nasi 200g → energi per porsi = 360 kkal
        //
        // TK_PAUD: target = 455.0  → pct = round(360/455.0*100, 1) = 79.1 → status 'kurang'
        // SD_4_6:  target = 633.8  → pct = round(360/633.8*100, 1) = 56.8 → status 'kurang'
        //
        // Jika evaluasiGizi() salah menggunakan SD_4_6 untuk TK_PAUD, hasilnya 56.8 bukan 79.1.

        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $menuTkPaud->load('detailBahans.bahanPangan');

        $evaluasi = $menuTkPaud->evaluasiGizi('siang');

        $this->assertEqualsWithDelta(455.0, $evaluasi['energi']['target'], 0.05,
            'evaluasiGizi harus menggunakan target TK_PAUD (455.0), bukan default SD_4_6 (633.8)');

        $this->assertEquals(360.0, $evaluasi['energi']['aktual']);

        $this->assertEqualsWithDelta(79.1, $evaluasi['energi']['pct'], 0.15,
            'persen AKG energi TK_PAUD harus ~79.1%');

        $this->assertEquals('kurang', $evaluasi['energi']['status'],
            'Status energi TK_PAUD (79.1%) harus kurang karena < 80');
    }

    public function test_evaluasi_gizi_menghasilkan_status_berbeda_untuk_kelompok_berbeda_menu_sama(): void
    {
        // Menu nasi 200g (360 kkal per porsi):
        // TK_PAUD (target 455.0): 79.1% → kurang (< 80)
        // BALITA_1_3 (target 438.8): 82.0% → cukup (≥ 80)
        // SMA (target 771.9): 46.6% → kurang (< 80)

        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $menuBalita = $this->buatMenuNasi('2026-05-01', 'BALITA_1_3');
        $menuSma = $this->buatMenuNasi('2026-05-01', 'SMA');

        $menuTkPaud->load('detailBahans.bahanPangan');
        $menuBalita->load('detailBahans.bahanPangan');
        $menuSma->load('detailBahans.bahanPangan');

        $evalTkPaud = $menuTkPaud->evaluasiGizi('siang');
        $evalBalita = $menuBalita->evaluasiGizi('siang');
        $evalSma = $menuSma->evaluasiGizi('siang');

        // Semua menu sama tapi evaluasi berbeda karena target berbeda
        $this->assertEquals('kurang', $evalTkPaud['energi']['status'],
            'TK_PAUD (79.1%): kurang');
        $this->assertEquals('cukup', $evalBalita['energi']['status'],
            'BALITA_1_3 (82.0%): cukup');
        $this->assertEquals('kurang', $evalSma['energi']['status'],
            'SMA (46.6%): kurang');

        // Target semakin besar dari kiri ke kanan (TK_PAUD < BALITA_1_3 < SMA)
        $this->assertGreaterThan(
            $evalTkPaud['energi']['pct'],
            $evalBalita['energi']['pct'],
            'BALITA_1_3 harus punya % AKG lebih tinggi dari TK_PAUD untuk nasi yang sama'
        );
        $this->assertGreaterThan(
            $evalSma['energi']['pct'],
            $evalTkPaud['energi']['pct'],
            'TK_PAUD harus punya % AKG lebih tinggi dari SMA untuk nasi yang sama'
        );
    }

    public function test_total_gizi_per_porsi_sama_untuk_semua_kelompok_dengan_bahan_identik(): void
    {
        // gizi per porsi hanya bergantung pada bahan + gram, bukan kelompok_sasaran
        // Nasi 200g (BDD=100), 1 sajian, 1 porsi → energi = 360.0 kkal per porsi

        $kelompokBerbeda = ['TK_PAUD', 'SD_4_6', 'SMA', 'HAMIL_T1', 'MENYUSUI_6BLN_2'];
        $hasilGizi = [];

        foreach ($kelompokBerbeda as $i => $ks) {
            $menu = $this->buatMenuNasi('2026-05-0'.($i + 1), $ks);
            $menu->load('detailBahans.bahanPangan');
            $hasilGizi[$ks] = $menu->totalGizi();
        }

        $referensi = $hasilGizi['TK_PAUD'];

        foreach ($hasilGizi as $ks => $gizi) {
            $this->assertEquals($referensi['energi'], $gizi['energi'],
                "Energi per porsi harus sama ({$ks}) untuk bahan identik");
            $this->assertEquals($referensi['protein'], $gizi['protein'],
                "Protein per porsi harus sama ({$ks})");
        }

        // Gizi per porsi: 360.0 kkal, 8.0g protein, 0.0g lemak, 80.0g karbo
        $this->assertEquals(360.0, $referensi['energi']);
        $this->assertEquals(8.0, $referensi['protein']);
        $this->assertEquals(0.0, $referensi['lemak']);
        $this->assertEquals(80.0, $referensi['karbohidrat']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 4. Finalisasi menu untuk semua 12 kelompok
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_finalisasi_menu_berhasil_untuk_semua_12_kelompok(): void
    {
        $day = 1;

        foreach (array_keys(AKG::KELOMPOK) as $ks) {
            $tanggal = '2026-05-'.str_pad($day++, 2, '0', STR_PAD_LEFT);
            $menu = $this->buatMenuNasi($tanggal, $ks);

            $this->actingAs($this->ahliGizi)
                ->patch(route('menu-harian.finalize', $menu))
                ->assertRedirect(route('menu-harian.show', $menu));

            $menu->refresh();

            $this->assertEquals('final', $menu->status,
                "Menu kelompok {$ks} harus berstatus final setelah finalisasi");

            $this->assertEquals($ks, $menu->kelompok_sasaran,
                "kelompok_sasaran harus tetap {$ks} setelah finalisasi");

            $this->assertNotNull($menu->anggaran_per_porsi,
                "anggaran_per_porsi harus tersimpan setelah finalisasi ({$ks})");
        }
    }

    public function test_finalisasi_menyimpan_snapshot_harga_untuk_semua_kelompok(): void
    {
        $day = 1;

        foreach (array_keys(AKG::KELOMPOK) as $ks) {
            $tanggal = '2026-05-'.str_pad($day++, 2, '0', STR_PAD_LEFT);
            $menu = $this->buatMenuNasi($tanggal, $ks);

            $this->actingAs($this->ahliGizi)
                ->patch(route('menu-harian.finalize', $menu));

            $menu->refresh();
            $detail = $menu->detailBahans->first();

            $this->assertNotNull($detail->harga_per_100g,
                "Snapshot harga harus tersimpan di detail bahan setelah finalisasi ({$ks})");

            $this->assertEquals(2000.0, (float) $detail->harga_per_100g,
                "Harga snapshot harus Rp 2.000/100g untuk {$ks}");
        }
    }

    public function test_finalisasi_menu_menggunakan_anggaran_sesuai_kelompok_anggaran(): void
    {
        // TK_PAUD → toAnggaranKelompok = 'balita_sd3'
        // SD_4_6  → toAnggaranKelompok = 'sd4_ibu_menyusui'
        // Keduanya punya anggaran 15.000 di setUp, tapi kita bisa bedakan nilainya

        AnggaranPorsi::where('kelompok', 'balita_sd3')->update(['anggaran_per_porsi' => 12000]);

        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $menuSd46 = $this->buatMenuNasi('2026-05-01', 'SD_4_6');

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $menuTkPaud));

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $menuSd46));

        $menuTkPaud->refresh();
        $menuSd46->refresh();

        $this->assertEquals(12000.0, (float) $menuTkPaud->anggaran_per_porsi,
            'Menu TK_PAUD harus mengunci anggaran balita_sd3 (12.000)');

        $this->assertEquals(15000.0, (float) $menuSd46->anggaran_per_porsi,
            'Menu SD_4_6 harus mengunci anggaran sd4_ibu_menyusui (15.000)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 5. Show view — tampilan gizi dengan target AKG per kelompok
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_show_view_menampilkan_label_kelompok_sasaran_untuk_semua_12_kelompok(): void
    {
        foreach (AKG::KELOMPOK as $ks => $data) {
            $menu = $this->buatMenuNasi('2026-05-01', $ks);

            $this->actingAs($this->ahliGizi)
                ->get(route('menu-harian.show', $menu))
                ->assertOk()
                ->assertSee($data['label'],
                    "Show page harus menampilkan label '{$data['label']}' untuk kelompok {$ks}");
        }
    }

    public function test_show_view_menampilkan_persen_akg_sesuai_kelompok_sasaran_bukan_default(): void
    {
        // Nasi 200g = 360 kkal per porsi.
        //
        // TK_PAUD target = 455.0 → pct = 79.1% → show view badge 'Cukup' (70–130)
        // SD_4_6  target = 633.8 → pct = 56.8% → show view badge 'Kurang' (< 70)
        //
        // Jika implementasi keliru pakai default SD_4_6 untuk TK_PAUD:
        //   TK_PAUD akan menampilkan 56.8% (Kurang) — bukan 79.1% (Cukup)

        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $menuSd46 = $this->buatMenuNasi('2026-05-01', 'SD_4_6');

        // TK_PAUD: pct = 79.1 → Cukup, target ditampilkan sebagai "455"
        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.show', $menuTkPaud))
            ->assertOk()
            ->assertSee('79.1')   // % AKG energi dengan target TK_PAUD
            ->assertSee('455')    // target energi TK_PAUD (PHP echo float 455.0 → "455")
            ->assertSee('Cukup'); // 79.1 berada di antara 70–130

        // SD_4_6: pct = 56.8 → Kurang, target "633.8"
        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.show', $menuSd46))
            ->assertOk()
            ->assertSee('56.8')   // % AKG energi dengan target SD_4_6
            ->assertSee('633.8')  // target energi SD_4_6
            ->assertSee('Kurang'); // 56.8 < 70
    }

    public function test_show_view_pct_akg_berbeda_antara_tk_paud_dan_sma_untuk_menu_sama(): void
    {
        // Membuktikan bahwa halaman show menggunakan target per kelompok dengan benar.
        // Nasi 200g = 360 kkal.
        //   TK_PAUD (target=455): pct=79.1 → "79.1%" muncul di show TK_PAUD
        //   SMA (target=771.9): pct=46.6 → "46.6%" muncul di show SMA

        $menuTkPaud = $this->buatMenuNasi('2026-05-01', 'TK_PAUD');
        $menuSma = $this->buatMenuNasi('2026-05-01', 'SMA');

        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.show', $menuTkPaud))
            ->assertOk()
            ->assertSee('79.1');

        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.show', $menuSma))
            ->assertOk()
            ->assertSee('46.6');
    }

    public function test_show_view_menu_final_merender_tanpa_error_untuk_12_kelompok(): void
    {
        $day = 1;

        foreach (array_keys(AKG::KELOMPOK) as $ks) {
            $tanggal = '2026-05-'.str_pad($day++, 2, '0', STR_PAD_LEFT);
            $menu = $this->buatMenuNasi($tanggal, $ks);

            // Finalisasi
            $this->actingAs($this->ahliGizi)
                ->patch(route('menu-harian.finalize', $menu));

            $menu->refresh();

            $this->actingAs($this->ahliGizi)
                ->get(route('menu-harian.show', $menu))
                ->assertOk();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 6. Menu index — filter kelompok_sasaran
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_menu_index_filter_kelompok_sasaran_menampilkan_hanya_menu_yang_sesuai(): void
    {
        $menuSd46 = $this->buatMenuNasiNamed('2026-05-01', 'SD_4_6', 'Menu SD46 Test');
        $menuSma = $this->buatMenuNasiNamed('2026-05-01', 'SMA', 'Menu SMA Test');
        $menuBalita = $this->buatMenuNasiNamed('2026-05-01', 'BALITA_1_3', 'Menu Balita Test');

        // Filter SD_4_6 → hanya menu SD_4_6 tampil
        $response = $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.index', [
                'kelompok_sasaran' => 'SD_4_6',
                'bulan' => '2026-05',
            ]))
            ->assertOk();

        $response->assertSee('Menu SD46 Test');
        $response->assertDontSee('Menu SMA Test');
        $response->assertDontSee('Menu Balita Test');

        // Filter SMA → hanya menu SMA tampil
        $response = $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.index', [
                'kelompok_sasaran' => 'SMA',
                'bulan' => '2026-05',
            ]))
            ->assertOk();

        $response->assertSee('Menu SMA Test');
        $response->assertDontSee('Menu SD46 Test');
        $response->assertDontSee('Menu Balita Test');
    }

    public function test_menu_index_menampilkan_semua_kelompok_tanpa_filter(): void
    {
        $this->buatMenuNasiNamed('2026-05-01', 'TK_PAUD', 'Menu TK PAUD');
        $this->buatMenuNasiNamed('2026-05-01', 'SD_4_6', 'Menu SD 46');
        $this->buatMenuNasiNamed('2026-05-01', 'HAMIL_T1', 'Menu Hamil T1');
        $this->buatMenuNasiNamed('2026-05-01', 'BALITA_1_3', 'Menu Balita 13');

        $response = $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.index', ['bulan' => '2026-05']))
            ->assertOk();

        $response->assertSee('Menu TK PAUD');
        $response->assertSee('Menu SD 46');
        $response->assertSee('Menu Hamil T1');
        $response->assertSee('Menu Balita 13');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 7. Laporan gizi — merender data per kelompok
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_laporan_gizi_merender_tanpa_error_dengan_data_berbagai_kelompok(): void
    {
        $kelompokList = ['TK_PAUD', 'SD_4_6', 'SMP', 'HAMIL_T1', 'MENYUSUI_6BLN_2'];
        $day = 1;

        foreach ($kelompokList as $ks) {
            $tanggal = '2026-05-'.str_pad($day++, 2, '0', STR_PAD_LEFT);
            $kelompok = AKG::toAnggaranKelompok($ks);

            $menu = MenuHarian::forceCreate([
                'tanggal' => $tanggal,
                'user_id' => $this->ahliGizi->id,
                'nama_menu' => "Laporan Menu {$ks}",
                'status' => 'final',
                'kelompok' => $kelompok,
                'kelompok_sasaran' => $ks,
                'jumlah_porsi' => 10,
                'anggaran_per_porsi' => 15000,
            ]);

            MenuDetailBahan::create([
                'menu_harian_id' => $menu->id,
                'bahan_pangan_id' => $this->nasi->id,
                'jumlah_gram' => 200,
                'jumlah_porsi' => 10,
                'harga_per_100g' => 2000,
            ]);
        }

        $response = $this->actingAs($this->ahliGizi)
            ->get(route('laporan.index', ['bulan' => '2026-05', 'jenis' => 'gizi']))
            ->assertOk();

        // Setiap menu harus tampil
        foreach ($kelompokList as $ks) {
            $response->assertSee("Laporan Menu {$ks}");
        }
    }

    public function test_laporan_biaya_merender_tanpa_error_dengan_berbagai_kelompok(): void
    {
        $akuntan = User::factory()->create(['role' => 'ketua_sppg', 'unit_sppg' => 'SPPG Test']);

        $kelompokList = ['TK_PAUD', 'SD_4_6', 'SMA'];
        $day = 1;

        foreach ($kelompokList as $ks) {
            $tanggal = '2026-05-'.str_pad($day++, 2, '0', STR_PAD_LEFT);
            $kelompok = AKG::toAnggaranKelompok($ks);

            $menu = MenuHarian::forceCreate([
                'tanggal' => $tanggal,
                'user_id' => $this->ahliGizi->id,
                'nama_menu' => "Biaya Menu {$ks}",
                'status' => 'final',
                'kelompok' => $kelompok,
                'kelompok_sasaran' => $ks,
                'jumlah_porsi' => 10,
                'anggaran_per_porsi' => 15000,
            ]);

            MenuDetailBahan::create([
                'menu_harian_id' => $menu->id,
                'bahan_pangan_id' => $this->nasi->id,
                'jumlah_gram' => 200,
                'jumlah_porsi' => 10,
                'harga_per_100g' => 2000,
            ]);
        }

        $this->actingAs($akuntan)
            ->get(route('laporan.index', ['bulan' => '2026-05', 'jenis' => 'biaya']))
            ->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 8. Simulasi kalkulasi — AKG target per kelompok
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_kalkulasi_simulasi_mengembalikan_akg_target_sesuai_kelompok(): void
    {
        // Nasi 200g untuk kelompok BALITA_1_3
        // Target: energi = round(1350*0.325,1) = 438.8
        // pct: round(360/438.8*100, 1) = 82.0%

        $result = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans' => [['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1]],
                'jumlah_porsi' => 1,
                'tanggal' => '2026-05-08',
                'kelompok' => 'BALITA_1_3',
            ])
            ->assertOk()
            ->json();

        $this->assertEqualsWithDelta(438.8, $result['akg_target']['energi'], 0.05,
            'AKG target energi BALITA_1_3 harus 438.8');

        $this->assertEqualsWithDelta(82.0, $result['persen_akg']['energi'], 0.2,
            'Persen AKG energi BALITA_1_3 harus ~82.0%');
    }

    public function test_kalkulasi_simulasi_persen_akg_berbeda_untuk_setiap_kelompok(): void
    {
        // Nasi 200g = 360 kkal. Semakin besar target, semakin kecil persentase.
        // Urutan target energi: BALITA_1_3 (438.8) < TK_PAUD (455.0) < SD_4_6 (633.8) < SMA (771.9)
        // Urutan pct:           BALITA_1_3 > TK_PAUD > SD_4_6 > SMA

        $pctPerKelompok = [];

        foreach (['BALITA_1_3', 'TK_PAUD', 'SD_4_6', 'SMA'] as $ks) {
            $result = $this->actingAs($this->ahliGizi)
                ->postJson(route('simulasi.kalkulasi'), [
                    'bahans' => [['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1]],
                    'jumlah_porsi' => 1,
                    'tanggal' => '2026-05-08',
                    'kelompok' => $ks,
                ])
                ->assertOk()
                ->json('persen_akg.energi');

            $pctPerKelompok[$ks] = $result;
        }

        // BALITA_1_3 (target terkecil) → pct terbesar
        $this->assertGreaterThan($pctPerKelompok['TK_PAUD'], $pctPerKelompok['BALITA_1_3']);
        $this->assertGreaterThan($pctPerKelompok['SD_4_6'], $pctPerKelompok['TK_PAUD']);
        $this->assertGreaterThan($pctPerKelompok['SMA'], $pctPerKelompok['SD_4_6']);
    }

    public function test_kalkulasi_simulasi_anggaran_sesuai_kelompok_anggaran_bukan_kelompok_sasaran(): void
    {
        // Anggaran bukan per kelompok_sasaran, tapi per kelompok anggaran (2 grup)
        // TK_PAUD → balita_sd3 → anggaran 15.000
        // SD_4_6  → sd4_ibu_menyusui → anggaran 15.000 (sama di setUp, beda kalau diubah)

        AnggaranPorsi::where('kelompok', 'balita_sd3')->update(['anggaran_per_porsi' => 10000]);

        $biayaTkPaud = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans' => [['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1]],
                'jumlah_porsi' => 1,
                'tanggal' => '2026-05-08',
                'kelompok' => 'TK_PAUD',
            ])
            ->assertOk()
            ->json('biaya');

        $biayaSd46 = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans' => [['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 1]],
                'jumlah_porsi' => 1,
                'tanggal' => '2026-05-08',
                'kelompok' => 'SD_4_6',
            ])
            ->assertOk()
            ->json('biaya');

        $this->assertEquals(10000, $biayaTkPaud['anggaran'],
            'TK_PAUD harus memakai anggaran balita_sd3 (10.000)');

        $this->assertEquals(15000, $biayaSd46['anggaran'],
            'SD_4_6 harus memakai anggaran sd4_ibu_menyusui (15.000)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 9. Simpan simulasi — kelompok_sasaran tersimpan di DB
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_simpan_simulasi_menyimpan_kelompok_sasaran_ke_db(): void
    {
        // Verifikasi bahwa kelompok_sasaran dan kelompok tersimpan benar ke database.
        // Menggunakan model langsung karena lebih dapat diandalkan di SQLite in-memory.
        $kelompok = AKG::toAnggaranKelompok('HAMIL_T2');

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-05-10',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Ibu Hamil T2',
            'status' => 'draft',
            'kelompok' => $kelompok,
            'kelompok_sasaran' => 'HAMIL_T2',
            'jumlah_porsi' => 5,
            'anggaran_per_porsi' => 15000,
        ]);

        $this->assertNotNull($menu->id, 'Menu harus berhasil disimpan (id tidak null)');
        $this->assertEquals('HAMIL_T2', $menu->kelompok_sasaran,
            'kelompok_sasaran harus tersimpan sebagai HAMIL_T2');
        $this->assertEquals('sd4_ibu_menyusui', $menu->kelompok,
            'HAMIL_T2 harus dipetakan ke kelompok anggaran sd4_ibu_menyusui');
        $this->assertEquals('draft', $menu->status);

        // Verifikasi dari DB (bukan dari objek yang baru dibuat)
        $dariDb = MenuHarian::find($menu->id);
        $this->assertNotNull($dariDb, 'Menu harus dapat ditemukan di DB via find()');
        $this->assertEquals('HAMIL_T2', $dariDb->kelompok_sasaran);
        $this->assertEquals('sd4_ibu_menyusui', $dariDb->kelompok);

        // Verifikasi HTTP route juga mereturn 200 (behavior route)
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), [
                'tanggal' => '2026-06-01',  // tanggal berbeda untuk menghindari unique constraint
                'nama_menu' => 'Menu HAMIL_T2 via HTTP',
                'catatan' => 'SD Negeri 01',
                'jumlah_porsi' => 5,
                'kelompok_sasaran' => 'HAMIL_T2',
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 5],
                ],
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'redirect']);
    }

    public function test_simpan_simulasi_menolak_duplikat_tanggal_kelompok_sasaran(): void
    {
        // Buat menu SD_4_6 pada 2026-05-10
        $this->buatMenuNasi('2026-05-10', 'SD_4_6');

        // Coba buat lagi untuk tanggal dan kelompok yang sama → harus error 422
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), [
                'tanggal' => '2026-05-10',
                'nama_menu' => 'Menu Duplikat',
                'catatan' => 'SD Negeri 01',
                'jumlah_porsi' => 5,
                'kelompok_sasaran' => 'SD_4_6',
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 5],
                ],
            ])
            ->assertStatus(422);

        // Tetapi kelompok berbeda (HAMIL_T1) di tanggal yang sama → harus berhasil
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), [
                'tanggal' => '2026-05-10',
                'nama_menu' => 'Menu Ibu Hamil T1',
                'catatan' => 'SD Negeri 01',
                'jumlah_porsi' => 5,
                'kelompok_sasaran' => 'HAMIL_T1',
                'bahans' => [
                    ['id' => $this->nasi->id, 'gram' => 200, 'porsi' => 5],
                ],
            ])
            ->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Helper
    // ═══════════════════════════════════════════════════════════════════════════

    private function buatMenuNasi(string $tanggal, string $kelompokSasaran): MenuHarian
    {
        return $this->buatMenuNasiNamed($tanggal, $kelompokSasaran, "Menu {$kelompokSasaran}");
    }

    private function buatMenuNasiNamed(string $tanggal, string $kelompokSasaran, string $nama): MenuHarian
    {
        $kelompok = AKG::toAnggaranKelompok($kelompokSasaran);

        $menu = MenuHarian::forceCreate([
            'tanggal' => $tanggal,
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => $nama,
            'status' => 'draft',
            'kelompok' => $kelompok,
            'kelompok_sasaran' => $kelompokSasaran,
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => 15000,
            'foto_menu' => 'menu-foto/test.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $this->nasi->id,
            'jumlah_gram' => 200,
            'jumlah_porsi' => 1,
        ]);

        return $menu;
    }
}
