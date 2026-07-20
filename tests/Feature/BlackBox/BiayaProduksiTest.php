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
        $this->akuntan  = User::factory()->akuntan()->create();
    }

    public function test_skenario_07_kalkulasi_biaya_pakai_harga_aktif_pada_tanggal_menu(): void
    {
        $bahan = BahanPangan::factory()->bddPenuh()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g'  => 2000,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => '2026-02-28',
        ]);

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g'  => 5000,
            'berlaku_mulai'   => '2026-03-01',
            'berlaku_sampai'  => null,
        ]);

        $menu = MenuHarian::create([
            'tanggal'          => '2026-02-15',
            'user_id'          => $this->ahliGizi->id,
            'nama_menu'        => 'Menu S07',
            'status'           => 'draft',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi'     => 1,
            'catatan'          => 'Test skenario 7',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id'  => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram'     => 100,
            'jumlah_porsi'    => 1,
            'harga_per_100g'  => null,
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
            'harga_per_100g'  => 2000,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
        ]);

        $this->actingAs($this->akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $bahan->id,
                'harga_per_kg'    => 50000,
                'berlaku_mulai'   => '2026-06-01',
                'berlaku_sampai'  => null,
                'keterangan'      => 'Tarif baru S08',
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
            'kelompok'           => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai'      => '2026-01-01',
            'berlaku_sampai'     => null,
            'created_by'         => $this->ahliGizi->id,
        ]);

        $bahan = BahanPangan::factory()->bddPenuh()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g'  => 1500,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
        ]);

        $menu = MenuHarian::create([
            'tanggal'          => '2026-06-15',
            'user_id'          => $this->ahliGizi->id,
            'nama_menu'        => 'Menu S09',
            'status'           => 'draft',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi'     => 1,
            'catatan'          => 'Test skenario 9 BVA',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id'  => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram'     => 1000,
            'jumlah_porsi'    => 1,
            'harga_per_100g'  => null,
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
            'kelompok'           => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai'      => '2026-01-01',
            'berlaku_sampai'     => null,
            'created_by'         => $this->ahliGizi->id,
        ]);

        $bahan = BahanPangan::factory()->bddPenuh()->create();

        $hargaRecord = HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g'  => 2000,
            'berlaku_mulai'   => '2026-06-15',
            'berlaku_sampai'  => null,
        ]);

        $menu = MenuHarian::create([
            'tanggal'          => '2026-06-15',
            'user_id'          => $this->ahliGizi->id,
            'nama_menu'        => 'Menu S10',
            'status'           => 'draft',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi'     => 1,
            'catatan'          => 'Test skenario 10',
            'foto_menu'        => 'menu-foto/test-s10.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id'  => $menu->id,
            'bahan_pangan_id' => $bahan->id,
            'jumlah_gram'     => 100,
            'jumlah_porsi'    => 1,
            'harga_per_100g'  => null,
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

    public function test_skenario_11_jumlah_porsi_tidak_valid_ditolak_validasi(): void
    {
        $bahan = BahanPangan::factory()->create();

        HargaBahan::create([
            'bahan_pangan_id' => $bahan->id,
            'harga_per_100g'  => 1000,
            'berlaku_mulai'   => '2026-01-01',
            'berlaku_sampai'  => null,
        ]);

        $base = [
            'tanggal'          => '2026-06-15',
            'catatan'          => 'Test porsi tidak valid',
            'kelompok'         => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'bahans'           => [
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
