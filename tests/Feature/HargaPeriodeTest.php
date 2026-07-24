<?php

namespace Tests\Feature;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Regresi C3 — constraint + validasi overlap periode pada `harga_bahans`
 * dan `anggaran_porsis`.
 *
 * hargaAktif()/aktif() memilih pemenang dengan orderByDesc('berlaku_mulai').
 * Dua periode dengan tanggal mulai sama = pemenang non-deterministik, dan
 * rentang tumpang tindih = tarif yang dipakai menu bisa berubah sendiri.
 */
class HargaPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private User $akuntan;

    private BahanPangan $bahan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->akuntan = User::factory()->akuntan()->create();
        $this->bahan = BahanPangan::factory()->bddPenuh()->create(['nama_bahan' => 'Beras Uji']);
    }

    private function seedTarif(int $bahanId, float $harga, string $mulai, ?string $sampai = null): HargaBahan
    {
        return HargaBahan::create([
            'bahan_pangan_id' => $bahanId,
            'harga_per_100g' => $harga,
            'berlaku_mulai' => $mulai,
            'berlaku_sampai' => $sampai,
        ]);
    }

    private function postTarif(string $mulai, float $hargaPerKg = 20000, ?int $bahanId = null)
    {
        return $this->actingAs($this->akuntan)->post(route('biaya.harga.store'), [
            'bahan_pangan_id' => $bahanId ?? $this->bahan->id,
            'harga_per_kg' => $hargaPerKg,
            'berlaku_mulai' => $mulai,
            'keterangan' => 'Test C3',
        ]);
    }

    public function test_tarif_maju_menutup_tarif_lama_dan_tarif_baru_open_ended(): void
    {
        $lama = $this->seedTarif($this->bahan->id, 2000, '2026-01-01');

        $this->postTarif('2026-02-01', hargaPerKg: 35000)
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertSame('2026-01-31', $lama->fresh()->berlaku_sampai->toDateString(),
            'Tarif lama harus ditutup sehari sebelum tarif baru');

        $baru = HargaBahan::where('berlaku_mulai', '2026-02-01')->firstOrFail();
        $this->assertNull($baru->berlaku_sampai, 'Tarif terbaru harus open-ended');
        $this->assertEquals(3500, (float) $baru->harga_per_100g, 'harga_per_kg 35000 → 3500/100g');

        $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-01-15'));
        $this->assertEquals(3500, HargaBahan::hargaAktif($this->bahan->id, '2026-02-15'));
    }

    public function test_tanggal_mulai_duplikat_untuk_bahan_yang_sama_ditolak(): void
    {
        $this->seedTarif($this->bahan->id, 2000, '2026-03-01');

        $this->postTarif('2026-03-01', hargaPerKg: 99000)
            ->assertSessionHasErrors('berlaku_mulai');

        $this->assertEquals(1, HargaBahan::count(), 'Tarif duplikat tidak boleh menambah record');
        $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-03-15'),
            'Tarif aktif tidak boleh berubah setelah penetapan duplikat ditolak');
    }

    public function test_tanggal_mulai_sama_untuk_bahan_berbeda_tetap_boleh(): void
    {
        $bahanLain = BahanPangan::factory()->bddPenuh()->create(['nama_bahan' => 'Ayam Uji']);
        $this->seedTarif($this->bahan->id, 2000, '2026-03-01');

        $this->postTarif('2026-03-01', hargaPerKg: 45000, bahanId: $bahanLain->id)
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-03-15'));
        $this->assertEquals(4500, HargaBahan::hargaAktif($bahanLain->id, '2026-03-15'));
    }

    public function test_backdate_tidak_membuat_range_terbalik_dan_ditutup_sebelum_tarif_berikutnya(): void
    {
        // Tarif berjalan mulai 1 Juli. Akuntan lalu memasukkan tarif backdate 1 Juni.
        $juli = $this->seedTarif($this->bahan->id, 5000, '2026-07-01');

        $this->postTarif('2026-06-01', hargaPerKg: 30000)
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertNull($juli->fresh()->berlaku_sampai,
            'Tarif Juli (mulai setelah tarif backdate) harus tetap open-ended');

        $juni = HargaBahan::where('berlaku_mulai', '2026-06-01')->firstOrFail();
        $this->assertSame('2026-06-30', $juni->berlaku_sampai->toDateString(),
            'Tarif backdate harus ditutup sehari sebelum tarif berikutnya');

        $this->assertEquals(3000, HargaBahan::hargaAktif($this->bahan->id, '2026-06-15'));
        $this->assertEquals(5000, HargaBahan::hargaAktif($this->bahan->id, '2026-07-15'),
            'Juli harus tetap pakai tarif Juli — bukan tarif backdate');
    }

    public function test_backdate_memendekkan_periode_tertutup_yang_melewati_tanggal_baru(): void
    {
        // Periode Januari sudah ditutup sampai akhir Juni; backdate ke 1 Maret harus
        // memendekkannya jadi 28 Februari, bukan membiarkan dua periode saling menimpa.
        $januari = $this->seedTarif($this->bahan->id, 2000, '2026-01-01', '2026-06-30');

        $this->postTarif('2026-03-01', hargaPerKg: 40000)
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertSame('2026-02-28', $januari->fresh()->berlaku_sampai->toDateString(),
            'Periode lama harus dipendekkan sampai sehari sebelum tarif backdate');
        $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-02-15'));
        $this->assertEquals(4000, HargaBahan::hargaAktif($this->bahan->id, '2026-05-15'),
            'Mei sudah pakai tarif backdate — tanpa tumpang tindih dengan periode lama');
    }

    public function test_kegagalan_saat_membuat_tarif_baru_merollback_penutupan_tarif_lama(): void
    {
        $lama = $this->seedTarif($this->bahan->id, 2000, '2026-01-01');

        HargaBahan::creating(function () {
            throw new RuntimeException('Simulasi kegagalan DB saat membuat tarif baru');
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->withoutExceptionHandling()->postTarif('2026-02-01');
        } finally {
            HargaBahan::flushEventListeners();

            $this->assertEquals(1, HargaBahan::count(), 'Tidak boleh ada tarif baru setelah rollback');
            $this->assertNull($lama->fresh()->berlaku_sampai,
                'Penutupan tarif lama harus ikut ter-rollback — kalau tidak, bahan ini kehilangan tarif aktif');
            $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-02-15'),
                'hargaAktif() tidak boleh jatuh ke 0 karena state parsial');
        }
    }

    public function test_hapus_tarif_aktif_membuka_kembali_tarif_terakhir_tanpa_overlap(): void
    {
        $lama = $this->seedTarif($this->bahan->id, 2000, '2026-01-01', '2026-01-31');
        $aktif = $this->seedTarif($this->bahan->id, 5000, '2026-02-01');

        $this->actingAs($this->akuntan)
            ->delete(route('biaya.harga.destroy', $aktif))
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertNull($lama->fresh()->berlaku_sampai,
            'Tarif sebelumnya harus dibuka kembali supaya bahan tetap punya tarif aktif');
        $this->assertEquals(2000, HargaBahan::hargaAktif($this->bahan->id, '2026-03-15'));
    }

    public function test_hapus_tarif_historis_tidak_mengubah_tarif_aktif(): void
    {
        $historis = $this->seedTarif($this->bahan->id, 2000, '2026-01-01', '2026-01-31');
        $aktif = $this->seedTarif($this->bahan->id, 5000, '2026-02-01');

        $this->actingAs($this->akuntan)
            ->delete(route('biaya.harga.destroy', $historis))
            ->assertRedirect(route('biaya.harga.index'));

        $this->assertNull($aktif->fresh()->berlaku_sampai);
        $this->assertEquals(5000, HargaBahan::hargaAktif($this->bahan->id, '2026-03-15'));
    }

    public function test_constraint_db_menolak_tarif_ganda_pada_tanggal_yang_sama(): void
    {
        $this->seedTarif($this->bahan->id, 2000, '2026-01-01');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->seedTarif($this->bahan->id, 9000, '2026-01-01');
    }

    public function test_constraint_db_menolak_anggaran_ganda_pada_tanggal_yang_sama(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 15000,
            'berlaku_mulai' => '2026-01-01',
            'created_by' => $ketua->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 20000,
            'berlaku_mulai' => '2026-01-01',
            'created_by' => $ketua->id,
        ]);
    }

    public function test_race_tarif_duplikat_dijawab_validasi_bukan_error_500(): void
    {
        // Simulasi TOCTOU: baris kembar disisipkan tepat setelah pre-check
        // tetapkanPeriode() lolos, sebelum insert-nya sendiri berjalan.
        $sudahDisisipkan = false;

        DB::listen(function ($query) use (&$sudahDisisipkan) {
            if ($sudahDisisipkan) {
                return;
            }

            if (str_contains($query->sql, 'select exists') && str_contains($query->sql, 'harga_bahans')) {
                $sudahDisisipkan = true;
                DB::table('harga_bahans')->insert([
                    'bahan_pangan_id' => $this->bahan->id,
                    'harga_per_100g' => 1234,
                    'berlaku_mulai' => '2026-04-01',
                    'berlaku_sampai' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->postTarif('2026-04-01', hargaPerKg: 50000);

        $this->assertTrue($sudahDisisipkan, 'Skenario race gagal disimulasikan — pre-check tidak terdeteksi');

        // Tanpa penanganan UniqueConstraintViolationException, request ini berakhir HTTP 500.
        $response->assertSessionHasErrors('berlaku_mulai');
        $response->assertStatus(302);

        // Baris penyusup ikut masuk transaksi request ini, jadi ikut ter-rollback —
        // yang penting: tidak ada tarif ganda yang tersisa untuk tanggal tersebut.
        $this->assertLessThanOrEqual(1, HargaBahan::where('berlaku_mulai', '2026-04-01')->count(),
            'Tidak boleh ada dua tarif dengan tanggal mulai sama untuk bahan yang sama');
    }
}
