<?php

namespace Tests\Feature\BlackBox;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
                'kode' => sprintf('SIM-%03d', $i),
                'nama_bahan' => "Nasi Putih #$i",
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
                'bahan_pangan_id' => $bahan->id,
                'harga_per_100g' => 2000 + ($i * 100),
                'berlaku_mulai' => '2026-01-01',
                'berlaku_sampai' => null,
            ]);

            if ($i === 1) {
                $this->bahan1 = $bahan;
            }
            if ($i === 2) {
                $this->bahan2 = $bahan;
            }
        }

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
            'bahans' => [['id' => $this->bahan1->id, 'gram' => 150, 'porsi' => 1]],
            'jumlah_porsi' => 10,
            'tanggal' => '2026-06-15',
            'kelompok' => 'SD_4_6',
        ];
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $payload)
            ->assertOk()
            ->assertJsonStructure([
                'gizi' => ['energi', 'protein', 'lemak', 'karbohidrat'],
                'persen_akg' => ['energi'],
                'akg_target',
                'detail',
                'biaya' => ['total', 'cost_per_porsi', 'anggaran', 'selisih', 'persen_anggaran'],
            ]);
        $this->assertEquals(0, MenuHarian::count(),
            'Tidak boleh ada record MenuHarian setelah /kalkulasi - endpoint hanya menghitung, tidak menyimpan');
        $this->assertEquals(0, MenuDetailBahan::count(),
            'Tidak boleh ada record MenuDetailBahan setelah /kalkulasi - endpoint hanya menghitung, tidak menyimpan');
    }

    public function test_skenario_15_duplikat_tanggal_kelompok_sasaran_ditolak(): void
    {
        $payload = [
            'tanggal' => '2026-06-20',
            'catatan' => 'Menu pertama S15',
            'jumlah_porsi' => 10,
            'kelompok_sasaran' => 'SD_4_6',
            'bahans' => [['id' => $this->bahan1->id, 'gram' => 150, 'porsi' => 1]],
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
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Draft S16',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'catatan' => 'Test skenario 16',
        ]);
        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $this->bahan1->id,
            'jumlah_gram' => 150,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,
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
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-10',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Final S17',
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'catatan' => 'Test skenario 17',
            'foto_menu' => 'menu-foto/test-s17.jpg',
        ]);
        $this->actingAs($this->ahliGizi)
            ->get(route('menu-harian.edit', $menu))
            ->assertRedirect(route('menu-harian.show', $menu))
            ->assertSessionHas('error', 'Menu sudah final, tidak bisa diedit.');
    }

    public function test_skenario_25_bahan_baru_default_jumlah_porsi_ikut_menu(): void
    {
        // Regresi A3: baris bahan baru via menu-harian.update tanpa jumlah_porsi
        // harus default ke jumlah_porsi menu (10), bukan hardcoded 1 - jika tidak,
        // totalGizi() akan understated ~10x untuk bahan tsb.
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Draft S25',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'catatan' => 'Test skenario 25',
        ]);

        $this->actingAs($this->ahliGizi)
            ->put(route('menu-harian.update', $menu), [
                'status' => 'draft',
                'bahans' => [
                    ['bahan_pangan_id' => $this->bahan1->id, 'jumlah_gram' => 150],
                ],
            ])
            ->assertRedirect(route('menu-harian.show', $menu));

        $detail = MenuDetailBahan::where('menu_harian_id', $menu->id)->first();
        $this->assertSame(10, $detail->jumlah_porsi,
            'S25: jumlah_porsi bahan baru harus default ke jumlah_porsi menu (10), bukan 1');

        $menu->load('detailBahans.bahanPangan');
        $gizi = $menu->totalGizi();
        $this->assertEquals(270, $gizi['energi'],
            'S25: energi per porsi harus 270 ((150g/100)*180kkal), tidak understated akibat jumlah_porsi salah');
    }

    public function test_skenario_26_bahan_dengan_jumlah_porsi_eksplisit_tidak_dipaksa_sama_dengan_menu(): void
    {
        // Fix A3 tidak boleh overwrite nilai jumlah_porsi eksplisit yang dikirim user
        // (mis. garnish/topping yang sengaja disajikan ke lebih sedikit orang).
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-16',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Draft S26',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'catatan' => 'Test skenario 26',
        ]);

        $this->actingAs($this->ahliGizi)
            ->put(route('menu-harian.update', $menu), [
                'status' => 'draft',
                'bahans' => [
                    ['bahan_pangan_id' => $this->bahan1->id, 'jumlah_gram' => 150, 'jumlah_porsi' => 3],
                ],
            ])
            ->assertRedirect(route('menu-harian.show', $menu));

        $detail = MenuDetailBahan::where('menu_harian_id', $menu->id)->first();
        $this->assertSame(3, $detail->jumlah_porsi,
            'S26: jumlah_porsi eksplisit (3) harus dipertahankan, tidak dipaksa jadi jumlah_porsi menu (10)');
    }

    public function test_skenario_30_race_simpan_duplikat_dijawab_422_bukan_500(): void
    {
        // Regresi C1 (TOCTOU): pre-check "sudah ada" lolos, lalu request paralel lain
        // menempati slot (tanggal, kelompok_sasaran) sebelum insert kita commit.
        // Listener query di bawah meniru race itu secara deterministik: begitu pre-check
        // (SELECT pertama ke menu_harians) selesai, penyerobot di-insert di luar transaksi
        // controller sehingga tetap ada setelah transaksi kita rollback.
        $sudahMenyerobot = false;
        DB::listen(function ($query) use (&$sudahMenyerobot) {
            if ($sudahMenyerobot
                || ! str_contains($query->sql, 'menu_harians')
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                return;
            }
            $sudahMenyerobot = true;

            DB::table('menu_harians')->insert([
                // Format tanggal harus persis sama dengan hasil cast 'date' Eloquent,
                // karena SQLite membandingkan index unik secara literal string.
                'tanggal' => Carbon::parse('2026-06-21')->toDateTimeString(),
                'user_id' => $this->ahliGizi->id,
                'nama_menu' => 'Menu Penyerobot S30',
                'status' => 'draft',
                'kelompok' => 'sd4_ibu_menyusui',
                'kelompok_sasaran' => 'SD_4_6',
                'jumlah_porsi' => 10,
                'anggaran_per_porsi' => 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), [
                'tanggal' => '2026-06-21',
                'catatan' => 'Menu korban race S30',
                'jumlah_porsi' => 10,
                'kelompok_sasaran' => 'SD_4_6',
                'bahans' => [['id' => $this->bahan1->id, 'gram' => 150, 'porsi' => 1]],
            ]);

        $this->assertTrue($sudahMenyerobot, 'S30: skenario race tidak terpicu - pre-check tidak menjalankan query ke menu_harians');

        $response->assertUnprocessable()
            ->assertJson(['error' => 'Menu untuk tanggal dan kelompok ini sudah ada. Silakan edit menu yang sudah ada.']);
        $this->assertNotNull($response->json('redirect'),
            'S30: respons duplikat harus menyertakan link edit ke menu yang menempati slot');
        $this->assertEquals(1, MenuHarian::count(),
            'S30: unique (tanggal, kelompok_sasaran) harus menahan insert kedua - hanya 1 menu tersisa');
        $this->assertEquals(0, MenuDetailBahan::count(),
            'S30: transaksi harus rollback penuh - tidak boleh ada detail bahan yatim');
    }

    public function test_skenario_31_edit_ubah_kelompok_ke_slot_terpakai_ditolak_422(): void
    {
        // Mode edit mengizinkan ganti kelompok_sasaran. Bila slot tujuan sudah dipakai
        // menu lain di tanggal yang sama, harus ditolak 422 - bukan 500 unique violation,
        // dan menu asal harus utuh (detail bahannya tidak telanjur terhapus).
        $menuA = MenuHarian::forceCreate([
            'tanggal' => '2026-06-22',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu A S31',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
        ]);
        MenuDetailBahan::create([
            'menu_harian_id' => $menuA->id,
            'bahan_pangan_id' => $this->bahan1->id,
            'jumlah_gram' => 150,
            'jumlah_porsi' => 10,
        ]);

        $menuB = MenuHarian::forceCreate([
            'tanggal' => '2026-06-22',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu B S31',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SMP',
            'jumlah_porsi' => 10,
        ]);

        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.simpan'), [
                'menu_id' => $menuA->id,
                'tanggal' => '2026-06-22',
                'catatan' => 'Pindah kelompok S31',
                'jumlah_porsi' => 10,
                'kelompok_sasaran' => 'SMP',
                'bahans' => [['id' => $this->bahan2->id, 'gram' => 200, 'porsi' => 10]],
            ])
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'Menu untuk tanggal dan kelompok ini sudah ada. Silakan edit menu yang sudah ada.',
                'redirect' => route('simulasi.edit-simulasi', $menuB),
            ]);

        $this->assertSame('SD_4_6', $menuA->fresh()->kelompok_sasaran,
            'S31: kelompok_sasaran menu asal tidak boleh berubah setelah bentrok ditolak');
        $this->assertEquals(1, MenuDetailBahan::where('menu_harian_id', $menuA->id)->count(),
            'S31: detail bahan menu asal harus utuh - penolakan terjadi sebelum detail dihapus');
        $this->assertSame($this->bahan1->id, MenuDetailBahan::where('menu_harian_id', $menuA->id)->first()->bahan_pangan_id,
            'S31: detail bahan asal (bahan1) tidak boleh tergantikan bahan2 dari payload yang ditolak');
    }
}
