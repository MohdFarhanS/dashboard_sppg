<?php

namespace Tests\Feature;

use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuHarianFinalizeTest extends TestCase
{
    use RefreshDatabase;

    private User $ahliGizi;

    private BahanPangan $bahan;

    private MenuHarian $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahliGizi = User::factory()->create([
            'role' => 'ahli_gizi',
            'unit_sppg' => 'SPPG Test',
        ]);

        $this->bahan = BahanPangan::create([
            'kode' => 'TES-001',
            'nama_bahan' => 'Nasi Putih (Test)',
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

        // Anggaran Januari: 10.000/porsi
        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 10000,
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
            'created_by' => $this->ahliGizi->id,
        ]);

        $this->menu = MenuHarian::forceCreate([
            'tanggal' => '2026-01-15',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Januari Test',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => 10000,
            'foto_menu' => 'menu-foto/test.jpg',
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $this->menu->id,
            'bahan_pangan_id' => $this->bahan->id,
            'jumlah_gram' => 200,
            'jumlah_porsi' => 1,
        ]);
    }

    public function test_draft_menu_menggunakan_anggaran_aktif_dari_anggaranporsi(): void
    {
        // Draft: selalu hitung ulang dari AnggaranPorsi
        $biaya = $this->menu->totalBiaya();

        $this->assertEquals(10000, $biaya['anggaran']);
    }

    public function test_finalize_menyimpan_snapshot_anggaran_per_porsi(): void
    {
        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu))
            ->assertRedirect();

        $this->menu->refresh();

        $this->assertEquals('final', $this->menu->status);
        $this->assertEquals(10000, (float) $this->menu->anggaran_per_porsi);
    }

    public function test_menu_final_tetap_pakai_anggaran_lama_setelah_anggaran_diubah(): void
    {
        // Finalisasi menu Januari dengan anggaran 10.000
        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu));

        $this->menu->refresh();
        $this->assertEquals(10000, (float) $this->menu->anggaran_per_porsi);

        // Simulasikan perubahan anggaran mulai Februari (anggaran baru = 14.000)
        AnggaranPorsi::where('kelompok', 'sd4_ibu_menyusui')
            ->whereNull('berlaku_sampai')
            ->update(['berlaku_sampai' => '2026-01-31']);

        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 14000,
            'berlaku_mulai' => '2026-02-01',
            'berlaku_sampai' => null,
            'created_by' => $this->ahliGizi->id,
        ]);

        // Menu Januari yang sudah final harus tetap menampilkan anggaran 10.000
        $this->menu->load('detailBahans.bahanPangan');
        $biaya = $this->menu->totalBiaya();

        $this->assertEquals(10000, $biaya['anggaran'],
            'Menu final harus tetap menggunakan anggaran yang berlaku saat finalisasi (10.000), bukan anggaran terbaru (14.000).'
        );
    }

    public function test_menu_baru_di_bulan_februari_menggunakan_anggaran_baru(): void
    {
        // Tutup anggaran Januari, buat anggaran Februari
        AnggaranPorsi::where('kelompok', 'sd4_ibu_menyusui')
            ->whereNull('berlaku_sampai')
            ->update(['berlaku_sampai' => '2026-01-31']);

        AnggaranPorsi::create([
            'kelompok' => 'sd4_ibu_menyusui',
            'anggaran_per_porsi' => 14000,
            'berlaku_mulai' => '2026-02-01',
            'berlaku_sampai' => null,
            'created_by' => $this->ahliGizi->id,
        ]);

        $menuFebruari = MenuHarian::forceCreate([
            'tanggal' => '2026-02-10',
            'user_id' => $this->ahliGizi->id,
            'nama_menu' => 'Menu Februari Test',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'anggaran_per_porsi' => 14000,
        ]);

        MenuDetailBahan::create([
            'menu_harian_id' => $menuFebruari->id,
            'bahan_pangan_id' => $this->bahan->id,
            'jumlah_gram' => 200,
            'jumlah_porsi' => 1,
        ]);

        $menuFebruari->load('detailBahans.bahanPangan');
        $biaya = $menuFebruari->totalBiaya();

        $this->assertEquals(14000, $biaya['anggaran'],
            'Menu draft di Februari harus menggunakan anggaran Februari (14.000).'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Snapshot harga bahan
    // ──────────────────────────────────────────────────────────────────────────

    public function test_finalize_menyimpan_snapshot_harga_di_detail_bahans(): void
    {
        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu));

        $this->menu->refresh();
        $detail = $this->menu->detailBahans->first();

        $this->assertNotNull($detail->harga_per_100g,
            'Setelah finalisasi, harga_per_100g detail bahan harus terisi.'
        );
        $this->assertEquals(2000.0, (float) $detail->harga_per_100g,
            'Harga yang dikunci harus sesuai HargaBahan aktif pada tanggal menu.'
        );
    }

    public function test_menu_final_tetap_pakai_harga_lama_setelah_harga_bahan_diubah(): void
    {
        // Finalisasi menu Januari dengan harga nasi Rp 2.000/100g
        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu));

        $this->menu->refresh();

        // Simulasikan perubahan harga mulai Februari: nasi naik jadi Rp 5.000/100g
        HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereNull('berlaku_sampai')
            ->update(['berlaku_sampai' => '2026-01-31']);

        HargaBahan::create([
            'bahan_pangan_id' => $this->bahan->id,
            'harga_per_100g' => 5000,
            'berlaku_mulai' => '2026-02-01',
            'berlaku_sampai' => null,
        ]);

        // Menu Januari final harus tetap menghitung dengan harga Rp 2.000
        // 200g × 2000/100 = Rp 4.000 total bahan; 1 porsi → cost/porsi = 4.000
        $this->menu->load('detailBahans.bahanPangan');
        $biaya = $this->menu->totalBiaya();

        $this->assertEquals(4000, $biaya['total_seluruh'],
            'Menu final harus menggunakan harga snapshot (2.000/100g), bukan harga baru (5.000/100g).'
        );
    }

    public function test_draft_menu_pakai_harga_terkini_dari_hargabahan(): void
    {
        // Tanpa finalisasi — harga di detail NULL, harus ambil dari HargaBahan
        $this->assertEquals('draft', $this->menu->status);

        $this->menu->load('detailBahans.bahanPangan');
        $biaya = $this->menu->totalBiaya();

        // 200g nasi × 2000/100 = 4000
        $this->assertEquals(4000, $biaya['total_seluruh']);
    }

    public function test_storeharga_menutup_record_open_ended_sebelumnya(): void
    {
        $lamaId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)->value('id');
        $this->assertNotNull($lamaId, 'Record harga awal harus ada.');

        $akuntan = User::factory()->create(['role' => 'akuntan', 'unit_sppg' => 'SPPG Test']);
        $this->actingAs($akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $this->bahan->id,
                'harga_per_kg' => 60000,  // Rp 6.000/100g
                'berlaku_mulai' => '2026-03-01',
                'keterangan' => '',
            ])
            ->assertStatus(302);

        // Harus ada 2 record setelah penambahan
        $this->assertEquals(2,
            HargaBahan::where('bahan_pangan_id', $this->bahan->id)->count()
        );

        // Record lama harus ditutup dengan berlaku_sampai = 2026-02-28
        $lama = HargaBahan::find($lamaId);
        $this->assertNotNull($lama);
        $this->assertNotNull($lama->berlaku_sampai);
        $this->assertEquals('2026-02-28', $lama->berlaku_sampai->toDateString());

        // Record baru: cari selain lamaId, open-ended
        $baru = HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereKeyNot($lamaId)
            ->first();

        $this->assertNotNull($baru, 'Record harga baru harus ada.');
        $this->assertNull($baru->berlaku_sampai);
        $this->assertEquals(6000.0, (float) $baru->harga_per_100g);
    }

    public function test_hapus_tarif_aktif_mengaktifkan_tarif_sebelumnya(): void
    {
        $akuntan = User::factory()->create(['role' => 'akuntan', 'unit_sppg' => 'SPPG Test']);
        $lamaId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)->value('id');

        // Tambah tarif Maret → Januari auto-close
        $this->actingAs($akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $this->bahan->id,
                'harga_per_kg' => 50000,
                'berlaku_mulai' => '2026-03-01',
                'keterangan' => '',
            ])->assertStatus(302);

        $baruId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereKeyNot($lamaId)->value('id');
        $this->assertNotNull($baruId, 'Tarif Maret harus tersimpan.');

        // Hapus tarif aktif (Maret)
        $this->actingAs($akuntan)
            ->delete(route('biaya.harga.destroy', $baruId))
            ->assertStatus(302);

        // Tarif Januari harus diaktifkan kembali (berlaku_sampai = null)
        $lama = HargaBahan::find($lamaId);
        $this->assertNull($lama->berlaku_sampai,
            'Tarif sebelumnya harus diaktifkan kembali setelah tarif aktif dihapus.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Validasi foto menu sebelum finalisasi
    // ──────────────────────────────────────────────────────────────────────────

    public function test_finalize_ditolak_jika_foto_belum_diupload(): void
    {
        $this->menu->update(['foto_menu' => null]);

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu))
            ->assertRedirect(route('menu-harian.show', $this->menu))
            ->assertSessionHas('error');

        $this->menu->refresh();
        $this->assertEquals('draft', $this->menu->status);
    }

    // C5: dua submit finalize bersamaan pada menu draft yang sama tak boleh
    // sama-sama lolos dan menimpa snapshot anggaran/harga secara parsial.
    public function test_finalize_race_dua_submit_bersamaan_tidak_menimpa_snapshot(): void
    {
        // Listener meniru request finalize lain yang menang race: begitu transaksi
        // kita melakukan SELECT ... FOR UPDATE (lockForUpdate), "request B" langsung
        // commit finalize duluan di luar transaksi kita (raw query, tak ikut rollback).
        $requestLainMenang = false;
        DB::listen(function ($query) use (&$requestLainMenang) {
            if ($requestLainMenang
                || ! str_contains($query->sql, 'menu_harians')
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                return;
            }
            $requestLainMenang = true;

            DB::table('menu_harians')->where('id', $this->menu->id)->update([
                'status' => 'final',
                'anggaran_per_porsi' => 99999,
                'updated_at' => now(),
            ]);
            DB::table('menu_detail_bahans')->where('menu_harian_id', $this->menu->id)->update([
                'harga_per_100g' => 7777,
                'updated_at' => now(),
            ]);
        });

        $response = $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu));

        $this->assertTrue($requestLainMenang, 'C5: skenario race tidak terpicu - lockForUpdate tidak menjalankan SELECT ke menu_harians');

        $response->assertRedirect(route('menu-harian.show', $this->menu))
            ->assertSessionHas('error', 'Menu sudah berstatus final.');

        $this->menu->refresh();
        $this->assertEquals('final', $this->menu->status);
        // Snapshot request B harus tetap utuh - request kita (yang kalah race)
        // tidak boleh menimpa dengan hasil hitung ulangnya sendiri.
        $this->assertEquals(99999.0, (float) $this->menu->anggaran_per_porsi,
            'C5: request yang kalah race tidak boleh menimpa anggaran_per_porsi hasil finalize duluan.'
        );
        $detail = $this->menu->detailBahans->first();
        $this->assertEquals(7777.0, (float) $detail->harga_per_100g,
            'C5: request yang kalah race tidak boleh menimpa snapshot harga hasil finalize duluan.'
        );
    }

    public function test_finalize_berhasil_setelah_foto_diupload(): void
    {
        $this->menu->update(['foto_menu' => 'menu-foto/test.jpg']);

        $this->actingAs($this->ahliGizi)
            ->patch(route('menu-harian.finalize', $this->menu))
            ->assertRedirect();

        $this->menu->refresh();
        $this->assertEquals('final', $this->menu->status);
    }

    public function test_upload_foto_berhasil_untuk_menu_draft(): void
    {
        Storage::fake('public');

        $this->menu->update(['foto_menu' => null]);

        $file = UploadedFile::fake()->create('menu.jpg', 100, 'image/jpeg');

        $this->actingAs($this->ahliGizi)
            ->post(route('menu-harian.upload-foto', $this->menu), [
                'foto_menu' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->menu->refresh();
        $this->assertNotNull($this->menu->foto_menu);
        Storage::disk('public')->assertExists($this->menu->foto_menu);
    }

    public function test_upload_foto_ditolak_untuk_menu_final(): void
    {
        Storage::fake('public');

        $this->menu->forceFill(['status' => 'final'])->save();
        $file = UploadedFile::fake()->create('menu.jpg', 100, 'image/jpeg');

        $this->actingAs($this->ahliGizi)
            ->post(route('menu-harian.upload-foto', $this->menu), [
                'foto_menu' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_hapus_tarif_historis_tidak_mengubah_tarif_aktif(): void
    {
        $akuntan = User::factory()->create(['role' => 'akuntan', 'unit_sppg' => 'SPPG Test']);
        $lamaId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)->value('id');

        $this->actingAs($akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $this->bahan->id,
                'harga_per_kg' => 50000, 'berlaku_mulai' => '2026-03-01', 'keterangan' => '',
            ])->assertStatus(302);

        $maretId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereKeyNot($lamaId)->value('id');

        $this->actingAs($akuntan)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $this->bahan->id,
                'harga_per_kg' => 60000, 'berlaku_mulai' => '2026-04-01', 'keterangan' => '',
            ])->assertStatus(302);

        $aprilId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)
            ->whereNull('berlaku_sampai')->value('id');

        // Hapus tarif Maret (historis)
        $this->actingAs($akuntan)
            ->delete(route('biaya.harga.destroy', $maretId))
            ->assertStatus(302);

        // Tarif April (aktif) tidak boleh terpengaruh
        $april = HargaBahan::find($aprilId);
        $this->assertNull($april->berlaku_sampai, 'Tarif aktif tidak boleh berubah saat tarif historis dihapus.');
    }

    public function test_ketua_sppg_tidak_bisa_tambah_atau_hapus_harga(): void
    {
        $ketua = User::factory()->create(['role' => 'ketua_sppg', 'unit_sppg' => 'SPPG Test']);
        $lamaId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)->value('id');

        // GET create → 403
        $this->actingAs($ketua)
            ->get(route('biaya.harga.create'))
            ->assertForbidden();

        // POST store → 403
        $this->actingAs($ketua)
            ->post(route('biaya.harga.store'), [
                'bahan_pangan_id' => $this->bahan->id,
                'harga_per_kg' => 30000,
                'berlaku_mulai' => '2026-06-01',
            ])->assertForbidden();

        // DELETE → 403
        $this->actingAs($ketua)
            ->delete(route('biaya.harga.destroy', $lamaId))
            ->assertForbidden();
    }

    public function test_edit_harga_selalu_diredirect(): void
    {
        $akuntan = User::factory()->create(['role' => 'akuntan', 'unit_sppg' => 'SPPG Test']);
        $lamaId = HargaBahan::where('bahan_pangan_id', $this->bahan->id)->value('id');

        // GET edit → redirect (tarif immutable, tidak bisa diedit)
        $this->actingAs($akuntan)
            ->get(route('biaya.harga.edit', $lamaId))
            ->assertRedirect(route('biaya.harga.index'));
    }

    public function test_update_menu_final_ditolak(): void
    {
        $this->menu->forceFill(['status' => 'final'])->save();

        $this->actingAs($this->ahliGizi)
            ->put(route('menu-harian.update', $this->menu), [
                'status' => 'draft',
            ])
            ->assertRedirect(route('menu-harian.show', $this->menu))
            ->assertSessionHas('error');

        $this->menu->refresh();
        $this->assertEquals('final', $this->menu->status, 'Menu final tidak boleh berubah jadi draft lewat update().');
    }

    public function test_destroy_menu_final_ditolak(): void
    {
        $this->menu->forceFill(['status' => 'final'])->save();

        $this->actingAs($this->ahliGizi)
            ->delete(route('menu-harian.destroy', $this->menu))
            ->assertRedirect(route('menu-harian.show', $this->menu))
            ->assertSessionHas('error');

        $this->assertNotNull(MenuHarian::find($this->menu->id), 'Menu final tidak boleh terhapus.');
    }

    public function test_simpan_simulasi_menu_final_ditolak(): void
    {
        $this->menu->forceFill(['status' => 'final'])->save();

        $this->actingAs($this->ahliGizi)
            ->post(route('simulasi.simpan'), [
                'tanggal' => '2026-01-15',
                'catatan' => 'Test',
                'jumlah_porsi' => 1,
                'menu_id' => $this->menu->id,
                'kelompok_sasaran' => 'SD_4_6',
                'bahans' => [
                    ['id' => $this->bahan->id, 'gram' => 200, 'porsi' => 1],
                ],
            ])
            ->assertStatus(422);

        $this->menu->refresh();
        $this->assertEquals('final', $this->menu->status);
    }
}
