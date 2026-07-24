<?php

namespace Tests\Feature;

use App\Constants\AKG;
use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario integrasi penuh: 30 hari × 12 kelompok_sasaran = 360 menu/bulan
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ JANUARI                                                              │
 * │  Harga nasi     : Rp 2.000/100g                                     │
 * │  Anggaran       : balita_sd3 = Rp 8.000 | sd4 = Rp 10.000          │
 * │  360 menu final : Jan 1-30 × 12 kelompok_sasaran                    │
 * │  Cost/porsi     : 200g × 2.000/100 = Rp 4.000                      │
 * ├──────────────────────────────────────────────────────────────────────┤
 * │ PERUBAHAN FEBRUARI                                                   │
 * │  Harga nasi     : Rp 3.500/100g  (auto-close Januari)              │
 * │  Anggaran       : balita_sd3 = Rp 10.000 | sd4 = Rp 14.000         │
 * ├──────────────────────────────────────────────────────────────────────┤
 * │ VERIFIKASI UTAMA                                                     │
 * │  Menu Januari   : cost & anggaran TIDAK berubah setelah Feb diset   │
 * │  Menu Februari  : cost = Rp 7.000, anggaran = Feb values            │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * Cakupan:
 *   - Snapshot harga (harga_per_100g di MenuDetailBahan)
 *   - Snapshot anggaran (anggaran_per_porsi di MenuHarian)
 *   - Semua 12 kelompok_sasaran (AKG::KELOMPOK keys)
 *   - Kedua kelompok anggaran: balita_sd3 dan sd4_ibu_menyusui
 */
class IntegrationHargaAnggaranTest extends TestCase
{
    use RefreshDatabase;

    // ─── Konstanta skenario ────────────────────────────────────────────
    private const HARGA_JAN = 2000.0;   // /100g

    private const HARGA_FEB = 3500.0;   // /100g

    private const ANGGARAN_BALITA_JAN = 8000.0;

    private const ANGGARAN_SD4_JAN = 10000.0;

    private const ANGGARAN_BALITA_FEB = 10000.0;

    private const ANGGARAN_SD4_FEB = 14000.0;

    private const GRAM = 200;

    private const COST_JAN = self::GRAM * self::HARGA_JAN / 100;  // 4000

    private const COST_FEB = self::GRAM * self::HARGA_FEB / 100;  // 7000

    private User $ahliGizi;

    private BahanPangan $bahan;

    /** Semua 12 kelompok_sasaran dari AKG::KELOMPOK */
    private array $kelompokSasaranList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->create([
            'role' => 'ahli_gizi',
            'unit_sppg' => 'SPPG Test',
        ]);

        $this->bahan = BahanPangan::create([
            'kode' => 'TES-NASI',
            'nama_bahan' => 'Nasi Putih (Integ Test)',
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

        $this->kelompokSasaranList = array_keys(AKG::KELOMPOK);  // 12 kelompok
    }

    // ─── Helper: set harga bahan (auto-close lama) ────────────────────
    private function tetapkanHarga(float $hargaPer100g, string $berlakuMulai): void
    {
        $berlakuSampaiLama = Carbon::parse($berlakuMulai)->subDay()->toDateString();
        HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereNull('berlaku_sampai')
            ->where('berlaku_mulai', '<', $berlakuMulai)
            ->update(['berlaku_sampai' => $berlakuSampaiLama]);

        HargaBahan::create([
            'bahan_pangan_id' => $this->bahan->id,
            'harga_per_100g' => $hargaPer100g,
            'berlaku_mulai' => $berlakuMulai,
            'berlaku_sampai' => null,
        ]);
    }

    // ─── Helper: set anggaran kedua kelompok (auto-close lama) ────────
    private function tetapkanAnggaran(float $balita, float $sd4, string $berlakuMulai): void
    {
        $berlakuSampaiLama = Carbon::parse($berlakuMulai)->subDay()->toDateString();

        foreach (['balita_sd3' => $balita, 'sd4_ibu_menyusui' => $sd4] as $kelompok => $anggaran) {
            AnggaranPorsi::where('kelompok', $kelompok)
                ->whereNull('berlaku_sampai')
                ->update(['berlaku_sampai' => $berlakuSampaiLama]);

            AnggaranPorsi::create([
                'kelompok' => $kelompok,
                'anggaran_per_porsi' => $anggaran,
                'berlaku_mulai' => $berlakuMulai,
                'berlaku_sampai' => null,
                'created_by' => $this->ahliGizi->id,
            ]);
        }
    }

    // ─── Helper: buat 1 menu + finalisasi, kembalikan ID ─────────────
    private function buatDanFinalisasi(
        string $tanggal,
        string $kelompok,
        string $kelompokSasaran
    ): int {
        $harga = HargaBahan::hargaAktif($this->bahan->id, $tanggal);

        $menu = MenuHarian::forceCreate([
            'tanggal' => $tanggal,
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => "Menu {$tanggal} {$kelompokSasaran}",
            'status' => 'draft',
            'kelompok' => $kelompok,
            'kelompok_sasaran' => $kelompokSasaran,
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => AnggaranPorsi::aktif($tanggal, $kelompok),
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $this->bahan->id,
            'jumlah_gram' => self::GRAM,
            'jumlah_porsi' => 1,
            'harga_per_100g' => $harga > 0 ? $harga : null,
        ]);

        $menu->forceFill([
            'status' => 'final',
            'anggaran_per_porsi' => AnggaranPorsi::aktif($tanggal, $kelompok),
        ])->save();

        return $menu->id;
    }

    // ─── Helper: load fresh model dari DB (lazy-load saat totalBiaya) ─
    private function freshMenu(int $id): MenuHarian
    {
        return MenuHarian::findOrFail($id);
    }

    // ─── Helper: expected anggaran berdasarkan kelompok_sasaran ───────
    private function expectedAnggaran(string $kelompokSasaran, bool $januari): float
    {
        $kelompok = AKG::toAnggaranKelompok($kelompokSasaran);
        if ($januari) {
            return $kelompok === 'balita_sd3' ? self::ANGGARAN_BALITA_JAN : self::ANGGARAN_SD4_JAN;
        }

        return $kelompok === 'balita_sd3' ? self::ANGGARAN_BALITA_FEB : self::ANGGARAN_SD4_FEB;
    }

    // ═══════════════════════════════════════════════════════════════════
    // TEST 1: Skenario lengkap 360+360 menu — Januari → Februari
    // ═══════════════════════════════════════════════════════════════════

    public function test_360_menu_per_bulan_snapshot_konsisten(): void
    {
        // ── 1. Tetapkan harga + anggaran Januari ─────────────────────
        $this->tetapkanHarga(self::HARGA_JAN, '2026-01-01');
        $this->tetapkanAnggaran(self::ANGGARAN_BALITA_JAN, self::ANGGARAN_SD4_JAN, '2026-01-01');

        // ── 2. Buat 360 menu Januari (30 hari × 12 kelompok_sasaran) ─
        $idsJan = [];  // ['kelompok_sasaran' => [id, ...]]
        foreach ($this->kelompokSasaranList as $ks) {
            $kelompok = AKG::toAnggaranKelompok($ks);
            $idsJan[$ks] = [];
            for ($hari = 1; $hari <= 30; $hari++) {
                $tanggal = sprintf('2026-01-%02d', $hari);
                $idsJan[$ks][] = $this->buatDanFinalisasi($tanggal, $kelompok, $ks);
            }
        }

        $totalJan = array_sum(array_map('count', $idsJan));
        $this->assertEquals(360, $totalJan, 'Harus ada 360 menu final Januari.');

        // ── 3. Spot-check: verifikasi cost & anggaran Januari sebelum perubahan Feb
        foreach ($this->kelompokSasaranList as $ks) {
            $expectedAng = $this->expectedAnggaran($ks, true);
            foreach ([0, 14, 29] as $idx) {  // cek menu pertama, tengah, terakhir
                $b = $this->freshMenu($idsJan[$ks][$idx])->totalBiaya();
                $this->assertEquals(self::COST_JAN, $b['cost_per_porsi'],
                    "Jan [{$ks}] hari ".($idx + 1).' — cost harus Rp '.self::COST_JAN);
                $this->assertEquals($expectedAng, $b['anggaran'],
                    "Jan [{$ks}] hari ".($idx + 1)." — anggaran harus Rp {$expectedAng}");
            }
        }

        // ── 4. Ubah harga & anggaran per 1 Februari ──────────────────
        $this->tetapkanHarga(self::HARGA_FEB, '2026-02-01');
        $this->tetapkanAnggaran(self::ANGGARAN_BALITA_FEB, self::ANGGARAN_SD4_FEB, '2026-02-01');

        // Verifikasi auto-close harga Januari
        $hargaJan = HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->where('harga_per_100g', self::HARGA_JAN)->first();
        $this->assertNotNull($hargaJan->berlaku_sampai, 'Harga Januari harus sudah ditutup.');
        $this->assertEquals('2026-01-31', $hargaJan->berlaku_sampai->toDateString());

        // ── 5. Re-check Januari setelah Februari ditetapkan ──────────
        //    Snapshot harus melindungi: harga & anggaran Januari tidak boleh berubah
        foreach ($this->kelompokSasaranList as $ks) {
            $expectedAng = $this->expectedAnggaran($ks, true);
            foreach ([0, 14, 29] as $idx) {
                $b = $this->freshMenu($idsJan[$ks][$idx])->totalBiaya();
                $this->assertEquals(self::COST_JAN, $b['cost_per_porsi'],
                    "Setelah Feb ditetapkan: Jan [{$ks}] hari ".($idx + 1).' cost HARUS TETAP Rp '.self::COST_JAN.' (bukan Rp '.self::COST_FEB.')');
                $this->assertEquals($expectedAng, $b['anggaran'],
                    "Setelah Feb ditetapkan: Jan [{$ks}] hari ".($idx + 1)." anggaran HARUS TETAP Rp {$expectedAng}");
            }
        }

        // ── 6. Buat 360 menu Februari ─────────────────────────────────
        $idsFeb = [];
        foreach ($this->kelompokSasaranList as $ks) {
            $kelompok = AKG::toAnggaranKelompok($ks);
            $idsFeb[$ks] = [];
            for ($hari = 1; $hari <= 30; $hari++) {
                $tanggal = sprintf('2026-02-%02d', $hari);
                $idsFeb[$ks][] = $this->buatDanFinalisasi($tanggal, $kelompok, $ks);
            }
        }

        $totalFeb = array_sum(array_map('count', $idsFeb));
        $this->assertEquals(360, $totalFeb, 'Harus ada 360 menu final Februari.');

        // ── 7. Verifikasi menu Februari pakai harga & anggaran Februari ─
        foreach ($this->kelompokSasaranList as $ks) {
            $expectedAng = $this->expectedAnggaran($ks, false);
            foreach ([0, 14, 29] as $idx) {
                $b = $this->freshMenu($idsFeb[$ks][$idx])->totalBiaya();
                $this->assertEquals(self::COST_FEB, $b['cost_per_porsi'],
                    "Feb [{$ks}] hari ".($idx + 1).' — cost harus Rp '.self::COST_FEB);
                $this->assertEquals($expectedAng, $b['anggaran'],
                    "Feb [{$ks}] hari ".($idx + 1)." — anggaran harus Rp {$expectedAng}");
            }
        }

        // ── 8. Verifikasi akhir: semua menu Januari masih tak berubah ─
        foreach ($this->kelompokSasaranList as $ks) {
            $expectedAng = $this->expectedAnggaran($ks, true);
            foreach ([0, 29] as $idx) {
                $b = $this->freshMenu($idsJan[$ks][$idx])->totalBiaya();
                $this->assertEquals(self::COST_JAN, $b['cost_per_porsi'],
                    "Final check: Jan [{$ks}] cost masih Rp ".self::COST_JAN);
                $this->assertEquals($expectedAng, $b['anggaran'],
                    "Final check: Jan [{$ks}] anggaran masih Rp {$expectedAng}");
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // TEST 2: Snapshot harga dan anggaran tersimpan di level record
    // ═══════════════════════════════════════════════════════════════════

    public function test_snapshot_tersimpan_di_record_untuk_setiap_kelompok(): void
    {
        $this->tetapkanHarga(self::HARGA_JAN, '2026-01-01');
        $this->tetapkanAnggaran(self::ANGGARAN_BALITA_JAN, self::ANGGARAN_SD4_JAN, '2026-01-01');

        foreach ($this->kelompokSasaranList as $ks) {
            $kelompok = AKG::toAnggaranKelompok($ks);
            $expAnggaran = $this->expectedAnggaran($ks, true);
            $id = $this->buatDanFinalisasi('2026-01-15', $kelompok, $ks);

            // harga_per_100g dikunci di MenuDetailBahan
            $detail = MenuDetailBahan::where('menu_harian_id', $id)->firstOrFail();
            $this->assertEquals(self::HARGA_JAN, (float) $detail->harga_per_100g,
                "Detail [{$ks}]: harga_per_100g harus = ".self::HARGA_JAN);

            // anggaran_per_porsi dikunci di MenuHarian
            $menu = MenuHarian::find($id);
            $this->assertEquals($expAnggaran, (float) $menu->anggaran_per_porsi,
                "Menu [{$ks}]: anggaran_per_porsi harus = {$expAnggaran}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // TEST 3: Menu draft menggunakan tarif dari tanggal menu (bukan snapshot)
    // ═══════════════════════════════════════════════════════════════════

    public function test_menu_draft_recalculate_dari_hargabahan(): void
    {
        $this->tetapkanHarga(self::HARGA_JAN, '2026-01-01');
        $this->tetapkanAnggaran(self::ANGGARAN_BALITA_JAN, self::ANGGARAN_SD4_JAN, '2026-01-01');

        // Buat menu draft Jan 10 (tidak di-finalize)
        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-01-10',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Draft',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => AnggaranPorsi::aktif('2026-01-10', 'sd4_ibu_menyusui'),
        ]);
        MenuDetailBahan::create([
            'menu_harian_id' => $menu->id,
            'bahan_pangan_id' => $this->bahan->id,
            'jumlah_gram' => self::GRAM,
            'jumlah_porsi' => 1,
            'harga_per_100g' => null,  // belum di-snapshot
        ]);

        // Ubah tarif ke Februari
        $this->tetapkanHarga(self::HARGA_FEB, '2026-02-01');
        $this->tetapkanAnggaran(self::ANGGARAN_BALITA_FEB, self::ANGGARAN_SD4_FEB, '2026-02-01');

        // Draft menu Jan 10 harus recalculate berdasarkan tanggal menu (Jan) → pakai Januari tarif
        $b = $this->freshMenu($menu->id)->totalBiaya();
        $this->assertEquals(self::COST_JAN, $b['cost_per_porsi'],
            'Draft Jan 10 harus recalculate dari HargaBahan — pakai harga Januari (bukan Februari).'
        );
        $this->assertEquals(self::ANGGARAN_SD4_JAN, $b['anggaran'],
            'Draft Jan 10 harus recalculate anggaran Januari.'
        );
    }
}
