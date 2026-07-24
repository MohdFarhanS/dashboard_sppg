<?php

namespace Tests\Feature\BlackBox;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiayaProduksiTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;

    private User $akuntan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahliGizi = User::factory()->ahliGizi()->create();
        $this->akuntan = User::factory()->akuntan()->create();
    }

    public function test_skenario_07_kalkulasi_biaya_pakai_harga_aktif_pada_tanggal_menu(): void
    {
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 2000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => '2026-02-28',
        ]);

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 5000,
            'berlaku_mulai' => '2026-03-01',
            'berlaku_sampai' => null,
        ]);

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-02-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu S07',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'catatan' => 'Test skenario 7',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,
        ]);

        $menu->load('detailBahans.bahanPangan');
        $biaya = $menu->totalBiaya();

        $this->assertEquals(2000, $biaya['cost_per_porsi'],
            'Kalkulasi harus memakai harga lama (2000/100g) berlaku pada 2026-02-15, bukan harga baru (5000/100g)');
    }

    public function test_skenario_08_tarif_lama_otomatis_ditutup_saat_tarif_baru_ditambahkan(): void
    {
        $bahan = BahanPangan::factory()->create();

        $tarifLama = HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 2000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        $this->actingAs($this->akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $bahan->id,
                'harga_per_kg' => 50000,
                'berlaku_mulai' => '2026-06-01',
                'berlaku_sampai' => null,
                'keterangan' => 'Tarif baru S08',
            ])
            ->assertRedirect();

        $tarifLama->refresh();

        $this->assertNotNull($tarifLama->berlaku_sampai,
            'Tarif lama harus ditutup (berlaku_sampai terisi) saat tarif baru ditambahkan');
        $this->assertEquals('2026-05-31', $tarifLama->berlaku_sampai->toDateString(),
            'berlaku_sampai tarif lama harus satu hari sebelum berlaku_mulai tarif baru (2026-05-31)');

        $hargaAktifBaru = HargaBahan::hargaAktif($bahan->id, '2026-07-01');
        $this->assertEquals(5000.0, $hargaAktifBaru,
            'Tarif baru (5000/100g) harus menjadi acuan kalkulasi untuk tanggal setelah 2026-06-01');
    }

    public function test_skenario_09_biaya_persis_sama_dengan_anggaran_status_warning_bva(): void
    {
        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
            'created_by' => $this->ahliGizi->id,
        ]);

        $bahan = BahanPangan::factory()->bddPenuh()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 1500,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu S09',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'catatan' => 'Test skenario 9 BVA',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 1000,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,
        ]);

        $menu->load('detailBahans.bahanPangan');
        $biaya = $menu->totalBiaya();

        $this->assertEquals(15000, $biaya['cost_per_porsi'],
            'Biaya per porsi harus tepat 15000 sesuai desain BVA');
        $this->assertEquals(100.0, $biaya['persen_anggaran'],
            'Persentase anggaran harus tepat 100% (biaya = anggaran)');
        $this->assertEquals('warning', $menu->statusAnggaran(),
            'Status harus warning saat biaya per porsi tepat 100% dari anggaran (band >=85%)');
    }

    public function test_skenario_10_snapshot_harga_terkunci_setelah_finalisasi(): void
    {
        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
            'created_by' => $this->ahliGizi->id,
        ]);

        $bahan = BahanPangan::factory()->bddPenuh()->create();

        $hargaRecord = HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 2000,
            'berlaku_mulai' => '2026-06-15',
            'berlaku_sampai' => null,
        ]);

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu S10',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'catatan' => 'Test skenario 10',
            'foto_menu' => 'menu-foto/test-s10.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,
        ]);

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $menu))
            ->assertRedirect();

        $detail = MenuDetailBahan::where('menu_harian_id', $menu->id)->first();
        $this->assertEquals(2000.0, (float) $detail->harga_per_100g,
            'Snapshot harga_per_100g harus terkunci ke 2000 pada saat finalisasi');

        $hargaRecord->update(['harga_per_100g' => 5000]);

        $menuFinal = MenuHarian::with('detailBahans.bahanPangan')->find($menu->id);
        $biaya = $menuFinal->totalBiaya();

        $this->assertEquals(2000, $biaya['cost_per_porsi'],
            'Biaya menu final harus tetap 2000 (snapshot) meskipun tarif diperbarui ke 5000 setelah finalisasi');
    }

    public function test_skenario_24_snapshot_terkunci_nol_saat_bahan_tak_berharga(): void
    {
        // Regresi A2: bahan tanpa HargaBahan sama sekali → hargaAktif() = 0.
        // Finalize harus kunci snapshot ke 0 (bukan null-lalu-live-recalc),
        // sehingga menambah harga backdate setelah finalisasi tidak mengubah biaya.
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu S24',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'catatan' => 'Test skenario 24',
            'foto_menu' => 'menu-foto/test-s24.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,
        ]);

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $menu))
            ->assertRedirect();

        $detail = MenuDetailBahan::where('menu_harian_id', $menu->id)->first();
        $this->assertNotNull($detail->harga_per_100g,
            'S24: snapshot harga_per_100g harus tersimpan sebagai 0, bukan null, saat bahan tak berharga');
        $this->assertSame(0.0, (float) $detail->harga_per_100g,
            'S24: snapshot harga_per_100g harus terkunci ke 0 saat bahan tak berharga');

        $menuFinal = MenuHarian::with('detailBahans.bahanPangan')->find($menu->id);
        $this->assertSame('belum_ada_data', $menuFinal->statusAnggaran(),
            'S24: status harus belum_ada_data saat snapshot biaya 0');

        // Tambah harga baru berlaku mundur mencakup tanggal menu (backdate).
        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 3000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        $menuFinal = MenuHarian::with('detailBahans.bahanPangan')->find($menu->id);
        $biaya = $menuFinal->totalBiaya();

        $this->assertEquals(0, $biaya['cost_per_porsi'],
            'S24: biaya menu final harus tetap 0 (snapshot terkunci) meski harga backdate ditambahkan setelah finalisasi');
    }

    public function test_skenario_28_dashboard_biaya_tidak_melabel_over_budget_saat_anggaran_belum_diatur(): void
    {
        // Regresi A4 (lanjutan): BiayaController::dashboard() dulu klasifikasi
        // over/under budget dari selisih (anggaran - cost) mentah. Setelah
        // AnggaranPorsi::aktif() jujur return 0 saat belum diatur, selisih jadi
        // negatif untuk hampir semua biaya nyata > 0, sehingga menu yang anggarannya
        // belum diatur salah dilabel "Over Budget". Harus pakai statusAnggaran().
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        // Sengaja TIDAK membuat AnggaranPorsi untuk kelompok ini.
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu S28',
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'foto_menu' => 'menu-foto/test-s28.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => 5000,
        ]);

        $response = $this->actingAs($this->akuntan)
            ->get(route('biaya.dashboard', ['bulan' => '2026-06']))
            ->assertOk();

        $response->assertViewHas('overBudget', 0);
        $response->assertViewHas('underBudget', 1);
        $response->assertViewHas('rekapBiaya', function ($rekap) {
            return $rekap->first()['status'] === 'belum_ada_data';
        });
    }

    public function test_skenario_25_api_estimasi_biaya_ikut_kali_jumlah_porsi(): void
    {
        // Regresi A5: apiEstimasi() dulu hitung (gram/100)*harga tanpa × porsi,
        // beda dengan totalBiaya() yang selalu × jumlah_porsi. Estimasi harus
        // konsisten dengan biaya menu tersimpan untuk jumlah_porsi yang sama.
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 2000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        $response = $this->actingAs($this->akuntan)
            ->postJson(route('biaya.api.estimasi'), [
                'tanggal' => '2026-06-15',
                'jumlah_porsi' => 10,
                'items' => [
                    ['bahan_pangan_id' => $bahan->id, 'jumlah_gram' => 100],
                ],
            ])
            ->assertOk();

        // (100/100) * 2000 * 10 porsi = 20000 total; cost_per_porsi = 20000/10 = 2000
        $response->assertJson([
            'total_seluruh' => 20000,
            'cost_per_porsi' => 2000,
        ]);
    }

    public function test_skenario_11_jumlah_porsi_tidak_valid_ditolak_validasi(): void
    {
        $bahan = BahanPangan::factory()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g' => 1000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
        ]);

        $base = [
            'tanggal' => '2026-06-15',
            'catatan' => 'Test porsi tidak valid',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'bahans' => [
                ['id' => $bahan->id, 'gram' => 100, 'porsi' => 1],
            ],
        ];

        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), array_merge($base, ['jumlah_porsi' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jumlah_porsi']);

        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), array_merge($base, ['jumlah_porsi' => -10]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jumlah_porsi']);
    }
}
