<?php

namespace Tests\Feature;

use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuHarianMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * E8 regression: 'status', 'user_id', 'anggaran_per_porsi' dikeluarkan dari
     * $fillable agar tidak bisa disusupi lewat mass-assignment (mis. controller
     * yang ceroboh meneruskan $request->all()). Field lain tetap fillable.
     */
    public function test_status_user_id_anggaran_per_porsi_tidak_mass_assignable(): void
    {
        $menu = new MenuHarian;
        $menu->fill([
            'tanggal' => '2026-01-01',
            'nama_menu' => 'Menu Uji',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 10,
            'status' => 'final',
            'user_id' => 999,
            'anggaran_per_porsi' => 999999,
        ]);

        $this->assertNull($menu->status);
        $this->assertNull($menu->user_id);
        $this->assertNull($menu->anggaran_per_porsi);

        // Field lain tetap bisa diisi via mass-assignment seperti biasa.
        $this->assertSame('Menu Uji', $menu->nama_menu);
        $this->assertSame('SD_4_6', $menu->kelompok_sasaran);
    }

    public function test_forcecreate_masih_bisa_set_field_terproteksi_secara_eksplisit(): void
    {
        $user = User::factory()->ahliGizi()->create();

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-01-02',
            'nama_menu' => 'Menu Uji 2',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SMP',
            'jumlah_porsi' => 5,
            'status' => 'draft',
            'user_id' => $user->id,
            'anggaran_per_porsi' => 15000,
        ]);

        $this->assertSame('draft', $menu->status);
        $this->assertSame($user->id, $menu->user_id);
        $this->assertEquals(15000, (float) $menu->anggaran_per_porsi);
    }
}
