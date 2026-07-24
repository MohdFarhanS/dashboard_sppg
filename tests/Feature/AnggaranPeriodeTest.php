<?php

namespace Tests\Feature;

use App\Models\AnggaranPorsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Regresi C2 — integritas periode AnggaranPorsi pada POST /anggaran.
 *
 * Penetapan anggaran menulis DUA record sekaligus (balita_sd3 + sd4_ibu_menyusui):
 * tutup periode lama, lalu buat periode baru. Tanpa transaksi + guard tanggal,
 * proses ini bisa meninggalkan state parsial atau range terbalik.
 */
class AnggaranPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private User $ketua;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ketua = User::factory()->ketuaSppg()->create();
    }

    /** Bikin satu periode langsung di DB (bypass controller). */
    private function seedPeriode(string $kelompok, float $nominal, string $mulai, ?string $sampai = null): AnggaranPorsi
    {
        return AnggaranPorsi::create([
            'kelompok' => $kelompok,
            'anggaran_per_porsi' => $nominal,
            'berlaku_mulai' => $mulai,
            'berlaku_sampai' => $sampai,
            'created_by' => $this->ketua->id,
        ]);
    }

    private function postAnggaran(string $mulai, float $balita = 12000, float $sd4 = 18000)
    {
        return $this->actingAs($this->ketua)->post(route('anggaran.store'), [
            'anggaran_balita_sd3' => $balita,
            'anggaran_sd4_ibu_menyusui' => $sd4,
            'berlaku_mulai' => $mulai,
            'keterangan' => 'Test C2',
        ]);
    }

    public function test_penetapan_maju_menutup_periode_lama_dan_buka_periode_baru(): void
    {
        $lamaBalita = $this->seedPeriode('balita_sd3', 10000, '2026-01-01');
        $lamaSd4 = $this->seedPeriode('sd4_ibu_menyusui', 15000, '2026-01-01');

        $this->postAnggaran('2026-02-01')->assertRedirect(route('anggaran.index'));

        $this->assertSame('2026-01-31', $lamaBalita->fresh()->berlaku_sampai->toDateString(),
            'Periode lama balita_sd3 harus ditutup sehari sebelum periode baru');
        $this->assertSame('2026-01-31', $lamaSd4->fresh()->berlaku_sampai->toDateString(),
            'Periode lama sd4_ibu_menyusui harus ditutup sehari sebelum periode baru');

        $baru = AnggaranPorsi::where('berlaku_mulai', '2026-02-01')->get();
        $this->assertCount(2, $baru, 'Harus ada 2 periode baru (satu per kelompok)');
        $this->assertTrue($baru->every(fn ($a) => $a->berlaku_sampai === null),
            'Periode terbaru harus open-ended karena tidak ada periode setelahnya');

        $this->assertEquals(15000, AnggaranPorsi::aktif('2026-01-15', 'sd4_ibu_menyusui'));
        $this->assertEquals(18000, AnggaranPorsi::aktif('2026-02-15', 'sd4_ibu_menyusui'));
    }

    public function test_backdate_tidak_membuat_range_terbalik_dan_tidak_menghapus_periode_berjalan(): void
    {
        // Periode berjalan mulai 1 Juli. Ketua lalu menetapkan anggaran backdate 1 Juni.
        $juli = $this->seedPeriode('sd4_ibu_menyusui', 20000, '2026-07-01');
        $juliBalita = $this->seedPeriode('balita_sd3', 14000, '2026-07-01');

        $this->postAnggaran('2026-06-01', balita: 12000, sd4: 18000)
            ->assertRedirect(route('anggaran.index'));

        // Periode Juli TIDAK boleh disentuh — menutupnya di 31 Mei bikin range terbalik
        // (berlaku_mulai 2026-07-01 > berlaku_sampai 2026-05-31) sehingga aktif() tak
        // pernah bisa memilihnya lagi.
        $this->assertNull($juli->fresh()->berlaku_sampai,
            'Periode Juli (mulai setelah tarif backdate) harus tetap open-ended');
        $this->assertNull($juliBalita->fresh()->berlaku_sampai,
            'Periode Juli balita_sd3 juga harus tetap open-ended');

        // Periode backdate harus ditutup sehari sebelum periode Juli, bukan open-ended.
        $juni = AnggaranPorsi::where('kelompok', 'sd4_ibu_menyusui')
            ->where('berlaku_mulai', '2026-06-01')->firstOrFail();
        $this->assertSame('2026-06-30', $juni->berlaku_sampai->toDateString(),
            'Periode backdate harus ditutup sehari sebelum periode berikutnya');

        // Timeline utuh: Juni pakai tarif backdate, Juli pakai tarif lama.
        $this->assertEquals(18000, AnggaranPorsi::aktif('2026-06-15', 'sd4_ibu_menyusui'),
            'Pertengahan Juni harus pakai tarif backdate');
        $this->assertEquals(20000, AnggaranPorsi::aktif('2026-07-15', 'sd4_ibu_menyusui'),
            'Pertengahan Juli harus tetap pakai tarif Juli — bukan tarif backdate');
    }

    public function test_backdate_memendekkan_periode_tertutup_yang_melewati_tanggal_baru(): void
    {
        // Periode Januari sudah ditutup sampai akhir Juni. Backdate ke 1 Maret harus
        // memendekkannya jadi 28 Februari, bukan membiarkannya tumpang tindih —
        // dua periode yang sama-sama cocok bikin aktif() bergantung urutan sort.
        $januari = $this->seedPeriode('sd4_ibu_menyusui', 15000, '2026-01-01', '2026-06-30');
        $this->seedPeriode('balita_sd3', 10000, '2026-01-01', '2026-06-30');

        $this->postAnggaran('2026-03-01', balita: 12000, sd4: 18000)
            ->assertRedirect(route('anggaran.index'));

        $this->assertSame('2026-02-28', $januari->fresh()->berlaku_sampai->toDateString(),
            'Periode lama harus dipendekkan sampai sehari sebelum tarif backdate');
        $this->assertEquals(15000, AnggaranPorsi::aktif('2026-02-15', 'sd4_ibu_menyusui'),
            'Februari masih pakai tarif lama');
        $this->assertEquals(18000, AnggaranPorsi::aktif('2026-05-15', 'sd4_ibu_menyusui'),
            'Mei sudah pakai tarif backdate — tanpa tumpang tindih dengan periode lama');
    }

    public function test_tanggal_mulai_yang_sudah_dipakai_ditolak_validasi(): void
    {
        $this->seedPeriode('balita_sd3', 10000, '2026-03-01');
        $this->seedPeriode('sd4_ibu_menyusui', 15000, '2026-03-01');

        $this->postAnggaran('2026-03-01')
            ->assertSessionHasErrors('berlaku_mulai');

        $this->assertEquals(2, AnggaranPorsi::count(),
            'Penetapan duplikat tidak boleh menambah record');
        $this->assertEquals(15000, AnggaranPorsi::aktif('2026-03-15', 'sd4_ibu_menyusui'),
            'Tarif aktif tidak boleh berubah setelah penetapan duplikat ditolak');
    }

    public function test_kegagalan_di_kelompok_kedua_merollback_kelompok_pertama(): void
    {
        $lamaBalita = $this->seedPeriode('balita_sd3', 10000, '2026-01-01');
        $lamaSd4 = $this->seedPeriode('sd4_ibu_menyusui', 15000, '2026-01-01');

        // Gagalkan penyimpanan kelompok kedua. Tanpa DB::transaction, kelompok pertama
        // sudah terlanjur commit: periode lamanya tertutup tanpa pengganti sehingga
        // aktif() untuk balita_sd3 jatuh ke 0 ("belum diatur").
        AnggaranPorsi::creating(function (AnggaranPorsi $a) {
            if ($a->kelompok === 'sd4_ibu_menyusui') {
                throw new RuntimeException('Simulasi kegagalan DB pada kelompok kedua');
            }
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->withoutExceptionHandling()->postAnggaran('2026-02-01');
        } finally {
            AnggaranPorsi::flushEventListeners();

            $this->assertEquals(2, AnggaranPorsi::count(),
                'Tidak boleh ada record baru setelah rollback');
            $this->assertNull($lamaBalita->fresh()->berlaku_sampai,
                'Penutupan periode balita_sd3 harus ikut ter-rollback');
            $this->assertNull($lamaSd4->fresh()->berlaku_sampai,
                'Periode sd4_ibu_menyusui harus tetap utuh');
            $this->assertEquals(10000, AnggaranPorsi::aktif('2026-02-15', 'balita_sd3'),
                'Tarif balita_sd3 harus tetap aktif, bukan jatuh ke 0 karena state parsial');
        }
    }
}
