<?php

namespace Tests\Feature;

use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi audit D3: hargaAktif()/BahanPangan::find() dipanggil per-item di dalam
 * loop (SimulasiController::kalkulasi, BiayaController::apiEstimasi) menghasilkan
 * jumlah query yang tumbuh linear dengan jumlah bahan. Setelah batch-load
 * (HargaBahan::hargaAktifBatch + whereIn), jumlah query harus konstan (O(1))
 * terlepas dari berapa banyak bahan dikirim.
 */
class PerformanceBatchLoadTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;

    private User $akuntan;

    /** @var BahanPangan[] */
    private array $bahans = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->ahliGizi()->create();
        $this->akuntan = User::factory()->akuntan()->create();

        for ($i = 1; $i <= 8; $i++) {
            $bahan = BahanPangan::create([
                'kode' => sprintf('PERF-%03d', $i),
                'nama_bahan' => "Bahan Perf {$i}",
                'kategori' => 'Serealia',
                'bdd' => 100,
                'energi' => 100,
                'protein' => 5,
                'lemak' => 2,
                'karbohidrat' => 20,
                'serat' => 1,
                'kalsium' => 10,
                'besi' => 1,
                'vit_c' => 0,
                'is_active' => true,
            ]);

            HargaBahan::create([
                'bahan_pangan_id' => $bahan->id,
                'harga_per_100g' => 1000 * $i,
                'berlaku_mulai' => '2026-01-01',
                'berlaku_sampai' => null,
            ]);

            $this->bahans[] = $bahan;
        }
    }

    private function bahansPayload(int $count): array
    {
        return collect($this->bahans)
            ->take($count)
            ->map(fn (BahanPangan $b) => ['id' => $b->id, 'gram' => 100, 'porsi' => 1])
            ->values()
            ->all();
    }

    public function test_kalkulasi_jumlah_query_tidak_bertambah_seiring_jumlah_bahan(): void
    {
        $payloadKecil = [
            'bahans' => $this->bahansPayload(2),
            'jumlah_porsi' => 1,
            'tanggal' => '2026-05-08',
            'kelompok' => 'SD_4_6',
        ];
        $payloadBesar = [
            'bahans' => $this->bahansPayload(8),
            'jumlah_porsi' => 1,
            'tanggal' => '2026-05-08',
            'kelompok' => 'SD_4_6',
        ];

        DB::enableQueryLog();
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $payloadKecil)
            ->assertOk();
        $logKecil = DB::getQueryLog();

        DB::flushQueryLog();
        $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), $payloadBesar)
            ->assertOk();
        $logBesar = DB::getQueryLog();
        DB::disableQueryLog();

        $hargaBahansQuery = fn ($log) => collect($log)->filter(fn ($q) => str_contains($q['query'], 'harga_bahans'))->count();

        // Inti fix D3: HargaBahan::hargaAktif() per-item diganti hargaAktifBatch()
        // satu query whereIn — jumlah query ke harga_bahans harus tetap 1 berapa pun
        // jumlah bahan (tabel ini tak disentuh oleh validasi Laravel sama sekali,
        // jadi terisolasi dari noise 'exists' rule).
        $this->assertSame(1, $hargaBahansQuery($logKecil), 'Query harga_bahans harus 1 (batch) untuk payload kecil');
        $this->assertSame(1, $hargaBahansQuery($logBesar), 'Query harga_bahans harus 1 (batch) untuk payload besar, bukan N per-item');

        // Sisa pertumbuhan total query (jika ada) berasal dari validasi bawaan Laravel
        // ('bahans.*.id' => exists:bahan_pangans,id, expand 1 query/index — di luar
        // scope D3). Tanpa fix BahanPangan::find() per item, pertumbuhan akan ~2x lebih
        // besar (validasi + find per item ekstra); dengan fix, hanya validasi yang
        // tumbuh linear.
        $selisihItem = count($payloadBesar['bahans']) - count($payloadKecil['bahans']);
        $pertumbuhanQuery = count($logBesar) - count($logKecil);
        $this->assertLessThanOrEqual(
            $selisihItem + 1,
            $pertumbuhanQuery,
            "Pertumbuhan query ({$pertumbuhanQuery}) menandakan N+1 masih ada di luar validasi bawaan"
        );
    }

    public function test_api_estimasi_jumlah_query_tidak_bertambah_seiring_jumlah_bahan(): void
    {
        $items = fn (int $count) => collect($this->bahans)
            ->take($count)
            ->map(fn (BahanPangan $b) => ['bahan_pangan_id' => $b->id, 'jumlah_gram' => 100])
            ->values()
            ->all();

        DB::enableQueryLog();
        $this->actingAs($this->akuntan)
            ->postJson(route('biaya.api.estimasi'), [
                'tanggal' => '2026-05-08',
                'jumlah_porsi' => 1,
                'items' => $items(2),
            ])
            ->assertOk();
        $queryCountKecil = count(DB::getQueryLog());

        DB::flushQueryLog();
        $this->actingAs($this->akuntan)
            ->postJson(route('biaya.api.estimasi'), [
                'tanggal' => '2026-05-08',
                'jumlah_porsi' => 1,
                'items' => $items(8),
            ])
            ->assertOk();
        $queryCountBesar = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $queryCountKecil,
            $queryCountBesar,
            "Query count harus konstan: kecil={$queryCountKecil}, besar={$queryCountBesar}"
        );
    }

    public function test_kalkulasi_hasil_tetap_benar_setelah_batch_load(): void
    {
        // Nasi-analog (i=1): harga 1000/100g; bahan ke-2 (i=2): harga 2000/100g
        $detail = $this->actingAs($this->ahliGizi)
            ->postJson(route('simulasi.kalkulasi'), [
                'bahans' => $this->bahansPayload(2),
                'jumlah_porsi' => 1,
                'tanggal' => '2026-05-08',
                'kelompok' => 'SD_4_6',
            ])
            ->assertOk()
            ->json('detail');

        $this->assertCount(2, $detail);
        $this->assertEquals($this->bahans[0]->id, $detail[0]['id']);
        $this->assertEquals(1000, $detail[0]['biaya']);
        $this->assertEquals($this->bahans[1]->id, $detail[1]['id']);
        $this->assertEquals(2000, $detail[1]['biaya']);
    }

    /**
     * Regresi audit D4: totalGizi()/totalBiaya() akses $detail->bahanPangan tanpa
     * menjamin eager-load. Kalau caller lupa eager-load, harus tetap O(1) query
     * (via loadMissing di awal method) — bukan N query (1 per detail bahan).
     *
     * Pakai menu status final (harga snapshot per detail) supaya query yang
     * dihitung murni akibat load relasi detailBahans.bahanPangan, terisolasi
     * dari lookup HargaBahan::hargaAktif() per-item yang memang terjadi untuk
     * menu draft (isu terpisah, di luar scope D4).
     */
    public function test_totalgizi_dan_totalbiaya_tetap_o1_query_walau_dipanggil_tanpa_eager_load(): void
    {
        $menuKecil = $this->buatMenuFinalTanpaEagerLoad(2, '2026-05-08');
        $menuBesar = $this->buatMenuFinalTanpaEagerLoad(8, '2026-05-09');

        DB::enableQueryLog();
        $menuKecil->totalGizi();
        $menuKecil->totalBiaya();
        $queryKecil = count(DB::getQueryLog());

        DB::flushQueryLog();
        $menuBesar->totalGizi();
        $menuBesar->totalBiaya();
        $queryBesar = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $queryKecil,
            $queryBesar,
            "Query count harus konstan terlepas jumlah detail bahan (loadMissing sekali, bukan N+1): kecil={$queryKecil}, besar={$queryBesar}"
        );
    }

    public function test_totalbiaya_hasil_tetap_benar_walau_dipanggil_tanpa_eager_load(): void
    {
        $menu = $this->buatMenuFinalTanpaEagerLoad(2, '2026-05-08');

        $biaya = $menu->totalBiaya();

        $this->assertEquals(3000, $biaya['cost_per_porsi'], 'Bahan 1 (1000/100g) + bahan 2 (2000/100g) = 3000/porsi');
    }

    private function buatMenuFinalTanpaEagerLoad(int $jumlahBahan, string $tanggal): MenuHarian
    {
        $menu = MenuHarian::forceCreate([
            'tanggal' => $tanggal,
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => "Menu D4 {$jumlahBahan} bahan",
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => 15000,
        ]);

        foreach (array_slice($this->bahans, 0, $jumlahBahan) as $i => $bahan) {
            MenuDetailBahan::create([
                'menu_harian_id' => $menu->id,
                'bahan_pangan_id' => $bahan->id,
                'jumlah_gram' => 100,
                'jumlah_porsi' => 1,
                'harga_per_100g' => 1000 * ($i + 1),
            ]);
        }

        // Fetch ulang tanpa with() — mensimulasikan caller yang lupa eager-load.
        return MenuHarian::find($menu->id);
    }
}
