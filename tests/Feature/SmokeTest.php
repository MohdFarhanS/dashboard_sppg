<?php

namespace Tests\Feature;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;

    private User $akuntan;

    private User $ketuaSppg;

    private User $superadmin;

    private BahanPangan $bahan;

    private MenuHarian $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->ahliGizi()->create();
        $this->akuntan = User::factory()->akuntan()->create();
        $this->ketuaSppg = User::factory()->ketuaSppg()->create();
        $this->superadmin = User::factory()->superadmin()->create();

        $this->bahan = BahanPangan::create([
            'kode' => 'SMK-001',
            'nama_bahan' => 'Nasi Putih (Smoke)',
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

        HargaBahan::create([
            'bahan_pangan_id' => $this->bahan->id,
            'harga_per_100g' => 2000,
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

        $this->menu = MenuHarian::forceCreate([
            'tanggal' => '2026-05-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Smoke Test',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'anggaran_per_porsi' => 15000,
            'foto_menu' => 'menu-foto/smoke.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $this->menu->id,
            'bahan_pangan_id' => $this->bahan->id,
            'jumlah_gram' => 200,
            'jumlah_porsi' => 10,
        ]);
    }

    // -- Halaman publik --

    public function test_landing_page_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('landing'))->assertOk();
    }

    public function test_login_page_dapat_diakses(): void
    {
        $this->get(route('login'))->assertOk();
    }

    // -- Dashboard --

    public function test_dashboard_dapat_diakses_oleh_ahli_gizi(): void
    {
        $this->actingAs($this->ahliGizi)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_dapat_diakses_oleh_akuntan(): void
    {
        $this->actingAs($this->akuntan)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_dapat_diakses_oleh_ketua_sppg(): void
    {
        $this->actingAs($this->ketuaSppg)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_redirect_login_tanpa_auth(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    // -- Superadmin --

    public function test_user_index_dapat_diakses_superadmin(): void
    {
        $this->actingAs($this->superadmin)->get(route('users.index'))->assertOk();
    }

    public function test_user_index_forbidden_untuk_role_operasional(): void
    {
        $this->actingAs($this->ahliGizi)->get(route('users.index'))->assertForbidden();
    }

    // -- Bahan Pangan --

    public function test_bahan_pangan_index_dapat_diakses_semua_role_operasional(): void
    {
        foreach ([$this->ahliGizi, $this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('bahan-pangan.index'))->assertOk();
        }
    }

    public function test_bahan_pangan_show_dapat_diakses_semua_role_operasional(): void
    {
        foreach ([$this->ahliGizi, $this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('bahan-pangan.show', $this->bahan))->assertOk();
        }
    }

    public function test_bahan_pangan_create_hanya_untuk_ketua_sppg(): void
    {
        $this->actingAs($this->ketuaSppg)->get(route('bahan-pangan.create'))->assertOk();
        $this->actingAs($this->ahliGizi)->get(route('bahan-pangan.create'))->assertForbidden();
        $this->actingAs($this->akuntan)->get(route('bahan-pangan.create'))->assertForbidden();
    }

    // -- Menu Harian --

    public function test_menu_harian_index_dapat_diakses_ahli_gizi_dan_ketua(): void
    {
        foreach ([$this->ahliGizi, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('menu-harian.index'))->assertOk();
        }
    }

    public function test_menu_harian_index_forbidden_untuk_akuntan(): void
    {
        $this->actingAs($this->akuntan)->get(route('menu-harian.index'))->assertForbidden();
    }

    public function test_menu_harian_show_dapat_diakses_ahli_gizi_dan_ketua(): void
    {
        foreach ([$this->ahliGizi, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('menu-harian.show', $this->menu))->assertOk();
        }
    }

    public function test_menu_harian_create_redirect_ke_simulasi_untuk_ahli_gizi(): void
    {
        // MenuHarianController::create() selalu redirect ke simulasi.index
        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.create'))
            ->assertRedirect(route('simulasi.index'));

        $this->actingAs($this->ketuaSppg)
            ->get(route('menu-harian.create'))
            ->assertForbidden();
    }

    // -- Simulasi --

    public function test_simulasi_index_hanya_untuk_ahli_gizi(): void
    {
        $this->actingAs($this->ahliGizi)->get(route('simulasi.index'))->assertOk();
        $this->actingAs($this->ketuaSppg)->get(route('simulasi.index'))->assertForbidden();
        $this->actingAs($this->akuntan)->get(route('simulasi.index'))->assertForbidden();
    }

    // -- Monitoring Gizi --

    public function test_gizi_dashboard_redirect_ke_dashboard_untuk_ahli_gizi_dan_ketua(): void
    {
        // GiziController::dashboard selalu redirect ke route dashboard utama
        foreach ([$this->ahliGizi, $this->ketuaSppg] as $user) {
            $this->actingAs($user)
                ->get(route('gizi.dashboard'))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_gizi_dashboard_forbidden_untuk_akuntan(): void
    {
        $this->actingAs($this->akuntan)->get(route('gizi.dashboard'))->assertForbidden();
    }

    // -- Biaya & Harga Bahan --

    public function test_biaya_dashboard_dapat_diakses_akuntan_dan_ketua(): void
    {
        foreach ([$this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('biaya.dashboard'))->assertOk();
        }
    }

    public function test_biaya_dashboard_forbidden_untuk_ahli_gizi(): void
    {
        $this->actingAs($this->ahliGizi)->get(route('biaya.dashboard'))->assertForbidden();
    }

    public function test_harga_index_dapat_diakses_akuntan_dan_ketua(): void
    {
        foreach ([$this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('biaya.harga.index'))->assertOk();
        }
    }

    public function test_harga_create_hanya_untuk_akuntan(): void
    {
        $this->actingAs($this->akuntan)->get(route('biaya.harga.create'))->assertOk();
        $this->actingAs($this->ketuaSppg)->get(route('biaya.harga.create'))->assertForbidden();
    }

    // -- Anggaran Porsi --

    public function test_anggaran_index_hanya_untuk_ketua_sppg(): void
    {
        $this->actingAs($this->ketuaSppg)->get(route('anggaran.index'))->assertOk();
        $this->actingAs($this->ahliGizi)->get(route('anggaran.index'))->assertForbidden();
        $this->actingAs($this->akuntan)->get(route('anggaran.index'))->assertForbidden();
    }

    // -- Budget Alert --

    public function test_budget_alert_dapat_diakses_akuntan_dan_ketua(): void
    {
        foreach ([$this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('budget-alert.index'))->assertOk();
        }
    }

    public function test_budget_alert_forbidden_untuk_ahli_gizi(): void
    {
        $this->actingAs($this->ahliGizi)->get(route('budget-alert.index'))->assertForbidden();
    }

    // -- Laporan --

    public function test_laporan_dapat_diakses_semua_role_operasional(): void
    {
        foreach ([$this->ahliGizi, $this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)->get(route('laporan.index'))->assertOk();
        }
    }

    // -- Import TKPI --

    public function test_import_tkpi_hanya_untuk_ketua_sppg(): void
    {
        $this->actingAs($this->ketuaSppg)->get(route('import-tkpi.index'))->assertOk();
        $this->actingAs($this->ahliGizi)->get(route('import-tkpi.index'))->assertForbidden();
        $this->actingAs($this->akuntan)->get(route('import-tkpi.index'))->assertForbidden();
    }

    // -- Pesan Masuk --

    public function test_pesan_masuk_hanya_untuk_ketua_sppg(): void
    {
        $this->actingAs($this->ketuaSppg)->get(route('pesan-masuk.index'))->assertOk();
        $this->actingAs($this->ahliGizi)->get(route('pesan-masuk.index'))->assertForbidden();
    }

    // -- API autocomplete --

    public function test_api_search_bahan_pangan_dapat_diakses_semua_role_operasional(): void
    {
        foreach ([$this->ahliGizi, $this->akuntan, $this->ketuaSppg] as $user) {
            $this->actingAs($user)
                ->get(route('api.bahan-pangan.search', ['q' => 'nasi']))
                ->assertOk();
        }
    }

    public function test_api_search_bahan_pangan_redirect_tanpa_auth(): void
    {
        $this->get(route('api.bahan-pangan.search', ['q' => 'nasi']))
            ->assertRedirect(route('login'));
    }
}
