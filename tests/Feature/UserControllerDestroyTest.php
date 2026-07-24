<?php

namespace Tests\Feature;

use App\Models\ImportLog;
use App\Models\MenuHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    }

    public function test_hapus_user_tanpa_relasi_berhasil(): void
    {
        $target = User::factory()->ahliGizi()->create();

        $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_hapus_user_dengan_menu_final_ditolak(): void
    {
        // Regresi B2: cascade delete dulu menghapus SEMUA menu_harians (termasuk final)
        // saat user penginput dihapus. Sekarang harus ditolak.
        $target = User::factory()->ahliGizi()->create();

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-15',
            'user_id' => $target->id,
            'nama_menu' => 'Menu Final B2',
            'status' => 'final',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
            'foto_menu' => 'menu-foto/test-b2-final.jpg',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $target));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('menu_harians', ['id' => $menu->id]);
    }

    public function test_hapus_user_dengan_menu_draft_ditolak(): void
    {
        // Restrict berlaku ke semua status, bukan cuma final.
        $target = User::factory()->ahliGizi()->create();

        $menu = MenuHarian::forceCreate([
            'tanggal' => '2026-06-16',
            'user_id' => $target->id,
            'nama_menu' => 'Menu Draft B2',
            'status' => 'draft',
            'kelompok' => 'sd4_ibu_menyusui',
            'kelompok_sasaran' => 'SD_4_6',
            'jumlah_porsi' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $target));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('menu_harians', ['id' => $menu->id]);
    }

    public function test_hapus_user_dengan_riwayat_import_ditolak(): void
    {
        $target = User::factory()->create(['role' => User::ROLE_KETUA_SPPG]);

        ImportLog::create([
            'user_id' => $target->id,
            'filename' => 'tkpi.csv',
            'inserted' => 10,
            'updated' => 0,
            'skipped' => 0,
            'mode' => 'skip',
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $target));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_hapus_akun_sendiri_ditolak(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->delete(route('users.destroy', $this->superadmin));

        $response->assertSessionHas('error', 'Tidak bisa menghapus akun sendiri.');
        $this->assertDatabaseHas('users', ['id' => $this->superadmin->id]);
    }
}
