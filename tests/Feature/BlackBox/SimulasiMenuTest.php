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

class SimulasiMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;
    private BahanPangan $bahan1;
    private BahanPangan $bahan2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->ahliGizi()->create();

        for ($i = 1; $i <= 10; $i++) {
            $bahan = BahanPangan::create([
                'kode'        => sprintf('SIM-%03d', $i),
                'nama_bahan'  => "Nasi Putih #$i",
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

            HargaBahan::create([
                'bahan_pangan_id' => $bahan->id,
                'harga_per_100g'  => 2000 + ($i * 100),
                'berlaku_mulai'   => '2026-01-01',
                'berlaku_sampai'  => null,
            ]);

            if ($i === 1) { $this->bahan1 = $bahan; }
            if ($i === 2) { $this->bahan2 = $bahan; }
        }

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

    public function test_skenario_12_autocomplete_bahan_pangan_input_valid(): void
    {
        $response = $this->actingAs($this->ahliGizi)
            ->getJson(route('api.bahan-pangan.search', ['q' => 'Na']))
            ->assertOk();
        $data = $response->json();
        $this->assertGreaterThan(0, count($data), 'Response harus mengandung minimal 1 bahan aktif yang cocok dengan query "Na"');
        $this->assertLessThanOrEqual(8, count($data), 'Response harus dibatasi maksimal 8 hasil (limit default endpoint)');
        foreach ($data as $item) {
            $this->assertArrayHasKey('harga_per_100g', $item, 'Setiap item harus memiliki field harga_per_100g dari HargaBahan aktif');
        }
    }

    public function test_skenario_13_autocomplete_input_satu_karakter_bva(): void
    {
        $response = $this->actingAs($this->ahliGizi)
            ->getJson(route('api.bahan-pangan.search', ['q' => 'N']))
            ->assertOk();
        $data = $response->json();
        $this->assertEmpty($data,
            'Response harus kosong: query satu karakter belum memenuhi ambang minimal 2 karakter (BVA). FAIL berarti server mengembalikan hasil tanpa validasi panjang minimum.');
    }

    public function test_skenario_14_kalkulasi_simulasi_tanpa_simpan(): void
    {
        $payload = [
            'bahans'       => [['id' => $this->bahan1->id, 'gram' => 150, 'porsi' => 1]],
            'jumlah_porsi' => 10,
            'tanggal'      => '2026-06-15',
            'kelompok'     => 'SD_4_6',
        ];
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $payload)
            ->assertOk()
            ->assertJsonStructure([
                'gizi'       => ['energi', 'protein', 'lemak', 'karbohidrat'],
                'persen_akg' => ['energi'],
                'akg_target',
                'detail',
                'biaya'      => ['total', 'cost_per_porsi', 'anggaran', 'selisih', 'persen_anggaran'],
            ]);
        $this->assertEquals(0, MenuHarian::count(),
            'Tidak boleh ada record MenuHarian setelah /kalkulasi - endpoint hanya menghitung, tidak menyimpan');
        $this->assertEquals(0, MenuDetailBahan::count(),
            'Tidak boleh ada record MenuDetailBahan setelah /kalkulasi - endpoint hanya menghitung, tidak menyimpan');
    }

    public function test_skenario_15_duplikat_tanggal_kelompok_sasaran_ditolak(): void
    {
        $payload = [
            'tanggal'          => '2026-06-20',
            'catatan'          => 'Menu pertama S15',
            'jumlah_porsi'     => 10,
            'kelompok_sasaran' => 'SD_4_6',
            'bahans'           => [['id' => $this->bahan1->id, 'gram' => 150, 'porsi' => 1]],
        ];
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), $payload)
            ->assertOk();
        $this->assertEquals(1, MenuHarian::count(), 'Menu pertama harus tersimpan ke database');
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), array_merge($payload, ['catatan' => 'Menu duplikat S15']))
            ->assertUnprocessable()
            ->assertJson(['error' => 'Menu untuk tanggal dan kelompok ini sudah ada. Silakan edit menu yang sudah ada.']);
        $this->assertEquals(1, MenuHarian::count(),
            'Jumlah menu tidak boleh bertambah setelah penyimpanan duplikat ditolak');
    }

    public function test_skenario_16_edit_menu_draft_prefill_otomatis(): void
    {
        $menu = MenuHarian::create([
            'tanggal'          => '2026-06-15',
            'user_id'          => $this->ahliGizi->id,
            'nama_menu'        => 'Menu Draft S16',
            'status'           => 'draft',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi'     => 10,
            'catatan'          => 'Test skenario 16',
        ]);
        MenuDetailBahan::create([
            'menu_harian_id'  => $menu->id,
            'bahan_pangan_id' => $this->bahan1->id,
            'jumlah_gram'     => 150,
            'jumlah_porsi'    => 1,
            'harga_per_100g'  => null,
        ]);
        $response = $this->actingAs($this->ahliGizi)
            ->get(route('simulasi.edit-simulasi', $menu))
            ->assertOk();
        $response->assertViewHas('existingBahans', function ($existingBahans) {
            return count($existingBahans) > 0
                && isset($existingBahans[0]['id'])
                && isset($existingBahans[0]['jumlah_gram']);
        });
        $response->assertViewHas('menuHarian', function ($m) {
            return $m->kelompok_sasaran === 'SD_4_6';
        });
    }

    public function test_skenario_17_edit_menu_final_redirect_ke_show(): void
    {
        $menu = MenuHarian::create([
            'tanggal'          => '2026-06-10',
            'user_id'          => $this->ahliGizi->id,
            'nama_menu'        => 'Menu Final S17',
            'status'           => 'final',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi'     => 10,
            'catatan'          => 'Test skenario 17',
            'foto_menu'        => 'menu-foto/test-s17.jpg',
        ]);
        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.edit', $menu))
            ->assertRedirect(route('menu-harian.show', $menu))
            ->assertSessionHas('error', 'Menu sudah final, tidak bisa diedit.');
    }
}