<?php

namespace Tests\Feature\BlackBox;

use App\Models\BahanPangan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->ketuaSppg()->create();
    }

    private function buatMenuFinal(
        string $tanggal,
        float $anggaran,
        float $hargaPer100g,
        float $gram,
        string $namaMenu = 'Menu Test'
    ): MenuHarian {
        $bahan = BahanPangan::factory()->create();

        $menu = MenuHarian::forceCreate([
            'tanggal' => $tanggal,
            'user_id' => $this->user->id,
            'nama_menu' => $namaMenu,
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => $anggaran,
            'foto_menu' => 'menu-foto/test.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => $gram,
            'jumlah_porsi' => 1,
            'harga_per_100g' => $hargaPer100g,
        ]);

        return $menu->load('detailBahans.bahanPangan');
    }

    public function test_skenario_18_biaya_tepat_di_bawah_ambang_peringatan_bva(): void
    {
        $menu = $this->buatMenuFinal('2026-06-15', 10000, 100, 8490, 'Menu S18');

        $biaya = $menu->totalBiaya();
        $this->assertEquals(8490, $biaya['cost_per_porsi'],
            'S18: cost_per_porsi harus 8490 (84,9% dari anggaran 10000)');
        $this->assertEquals(84.9, $biaya['persen_anggaran'],
            'S18: persen_anggaran harus 84.9 (di bawah ambang 85%)');
        $this->assertEquals('aman', $menu->statusAnggaran(),
            'S18: status harus aman saat biaya 84,9% dari anggaran');

        $this->actingAs($this->user)
            ->get(route('budget-alert.index', ['bulan' => '2026-06']))
            ->assertOk()
            ->assertViewHas('alerts', function ($alerts) {
                return count($alerts) === 0;
            });
    }

    public function test_skenario_19_biaya_tepat_di_ambang_peringatan_85_persen_bva(): void
    {
        $menu = $this->buatMenuFinal('2026-06-15', 10000, 100, 8500, 'Menu S19');

        $biaya = $menu->totalBiaya();
        $this->assertEquals(8500, $biaya['cost_per_porsi'],
            'S19: cost_per_porsi harus 8500 (tepat 85% dari anggaran 10000)');
        $this->assertEquals(85.0, $biaya['persen_anggaran'],
            'S19: persen_anggaran harus tepat 85.0');
        $this->assertEquals('warning', $menu->statusAnggaran(),
            'S19: status harus warning saat biaya tepat 85% dari anggaran');

        $this->actingAs($this->user)
            ->get(route('budget-alert.index', ['bulan' => '2026-06']))
            ->assertOk()
            ->assertViewHas('alerts', function ($alerts) {
                return count($alerts) === 1 && $alerts[0]['status'] === 'warning';
            });
    }

    public function test_skenario_20_biaya_tepat_100_persen_dari_anggaran_bva(): void
    {
        $menu = $this->buatMenuFinal('2026-06-15', 10000, 100, 10000, 'Menu S20');

        $biaya = $menu->totalBiaya();
        $this->assertEquals(10000, $biaya['cost_per_porsi'],
            'S20: cost_per_porsi harus 10000 (tepat 100% dari anggaran)');
        $this->assertEquals(100.0, $biaya['persen_anggaran'],
            'S20: persen_anggaran harus tepat 100.0');
        $this->assertEquals('warning', $menu->statusAnggaran(),
            'S20: status harus warning saat tepat 100% - threshold over adalah LEBIH DARI 100%');
    }

    public function test_skenario_21_biaya_melebihi_100_persen_anggaran_bva(): void
    {
        $menu = $this->buatMenuFinal('2026-06-15', 10000, 100, 10050, 'Menu S21');

        $biaya = $menu->totalBiaya();
        $this->assertEquals(10050, $biaya['cost_per_porsi'],
            'S21: cost_per_porsi harus 10050 (100,5% dari anggaran 10000)');
        $this->assertEquals(100.5, $biaya['persen_anggaran'],
            'S21: persen_anggaran harus 100.5');
        $this->assertEquals('over', $menu->statusAnggaran(),
            'S21: status harus over saat biaya melebihi 100% dari anggaran');

        $this->actingAs($this->user)
            ->get(route('budget-alert.index', ['bulan' => '2026-06', 'severity' => 'over']))
            ->assertOk()
            ->assertViewHas('alerts', function ($alerts) {
                return count($alerts) === 1 && $alerts[0]['status'] === 'over';
            });
    }

    public function test_skenario_22_filter_daftar_alert_berdasarkan_bulan_dan_severity(): void
    {
        $this->buatMenuFinal('2026-05-01', 10000, 100, 8500, 'Mei Warning');
        $this->buatMenuFinal('2026-05-02', 10000, 100, 10050, 'Mei Over');
        $this->buatMenuFinal('2026-06-01', 10000, 100, 8500, 'Juni Warning');
        $this->buatMenuFinal('2026-06-02', 10000, 100, 10050, 'Juni Over');

        $response = $this->actingAs($this->user)
            ->get(route('budget-alert.index', ['bulan' => '2026-05', 'severity' => 'warning']))
            ->assertOk();

        $response->assertViewHas('alerts', function ($alerts) {
            if (count($alerts) !== 1) {
                return false;
            }
            if ($alerts[0]['status'] !== 'warning') {
                return false;
            }

            return $alerts[0]['menu']->tanggal->format('Y-m') === '2026-05';
        });

        $response->assertViewHas('countWarning', 1);
        $response->assertViewHas('countOver', 1);
    }

    public function test_skenario_23_biaya_nol_menghasilkan_status_belum_ada_data(): void
    {
        // Regresi A1: cost_per_porsi hasil round() selalu float (0.0).
        // Perbandingan strict `=== 0` (int) selalu false sehingga cabang
        // 'belum_ada_data' jadi dead code dan status jatuh ke 'aman'.
        $menu = $this->buatMenuFinal('2026-06-15', 10000, 0, 8500, 'Menu S23');

        $biaya = $menu->totalBiaya();
        $this->assertEquals(0, $biaya['cost_per_porsi'],
            'S23: cost_per_porsi harus 0 saat harga bahan 0');
        $this->assertSame('belum_ada_data', $menu->statusAnggaran(),
            'S23: status harus belum_ada_data saat cost_per_porsi 0, bukan aman');
    }

    public function test_skenario_27_anggaran_belum_diatur_tidak_dilabel_aman(): void
    {
        // Regresi A4: AnggaranPorsi::aktif() dulu fallback ke 15000 (angka palsu)
        // saat belum ada AnggaranPorsi untuk kelompok/tanggal terkait, sehingga menu
        // berbiaya nyata tapi anggaran tak pernah diatur diam-diam dilabel 'aman'.
        // Sekarang fallback ke 0, dan statusAnggaran() harus mengenali anggaran<=0
        // sebagai 'belum_ada_data', bukan 'aman'.
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        // Sengaja TIDAK membuat AnggaranPorsi apa pun untuk kelompok ini.
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->user->id,
            'nama_menu' => 'Menu S27',
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'foto_menu' => 'menu-foto/test-s27.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => 5000,
        ]);

        $menu->load('detailBahans.bahanPangan');
        $biaya = $menu->totalBiaya();

        $this->assertEquals(0, $biaya['anggaran'],
            'S27: anggaran harus 0 (bukan fallback palsu 15000) saat belum diatur');
        $this->assertGreaterThan(0, $biaya['cost_per_porsi'],
            'S27: cost_per_porsi harus tetap terhitung nyata (5000) meski anggaran belum diatur');
        $this->assertSame('belum_ada_data', $menu->statusAnggaran(),
            'S27: status harus belum_ada_data saat anggaran belum diatur, bukan aman');
    }

    public function test_skenario_29_halaman_budget_alert_tidak_hitung_belum_diatur_sebagai_aman(): void
    {
        // Regresi A4 (lanjutan): BudgetAlertController dulu menangkap status
        // 'belum_ada_data' ke counter $countAman via catch-all else, dan tidak
        // mengecualikannya dari daftar alert default (severity=''). Harus punya
        // bucket sendiri dan tetap disembunyikan dari tampilan default.
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        // Menu dengan anggaran belum diatur (tidak ada AnggaranPorsi utk kelompok ini).
        $menuBelumDiatur = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->user->id,
            'nama_menu' => 'Menu Belum Diatur S29',
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'foto_menu' => 'menu-foto/test-s29.jpg',
        ]);
        MenuDetailBahan::create([
            'menu_harian_id' => $menuBelumDiatur->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram' => 100,
            'jumlah_porsi' => 1,
            'harga_per_100g' => 5000,
        ]);

        // Menu lain yang benar-benar aman (anggaran dikonfigurasi & mencukupi).
        $this->buatMenuFinal('2026-06-16', 10000, 100, 5000, 'Menu Aman S29');

        $response = $this->actingAs($this->user)
            ->get(route('budget-alert.index', ['bulan' => '2026-06']))
            ->assertOk();

        $response->assertViewHas('countAman', 1);
        $response->assertViewHas('countBelumAdaData', 1);
        $response->assertViewHas('alerts', function ($alerts) {
            return count($alerts) === 0;
        });
    }

    public function test_skenario_30_get_index_tidak_mendismiss_alert_hanya_post_dismiss_yang_boleh(): void
    {
        // Regresi C6: GET index() dulu langsung menandai alert bulan ini sebagai
        // "sudah dilihat" via session — efek samping pada request GET, sehingga
        // prefetch/refresh diam-diam bisa membuang notifikasi tanpa aksi user.
        $menu = $this->buatMenuFinal(today()->format('Y-m-d'), 10000, 100, 10050, 'Menu Over Bulan Ini');

        $this->actingAs($this->user)
            ->get(route('budget-alert.index'))
            ->assertOk();

        $this->assertSame([], session('dismissed_alert_ids', []),
            'S30: GET index() tidak boleh mengubah session dismissed_alert_ids');

        $this->actingAs($this->user)
            ->post(route('budget-alert.dismiss'))
            ->assertRedirect();

        $this->assertContains($menu->id, session('dismissed_alert_ids', []),
            'S30: POST dismiss() harus menandai menu over/warning bulan ini sebagai sudah dilihat');
    }
}
