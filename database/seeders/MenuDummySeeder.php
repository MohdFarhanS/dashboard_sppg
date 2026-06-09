<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * MenuDummySeeder — v2 (fixed)
 *
 * Generate dummy data menu harian MBG untuk April sampai 10 Juni 2026.
 * Disesuaikan dengan skema database aktual hasil inspect migration.
 *
 * Cara pakai:
 *   php artisan db:seed --class=MenuDummySeeder
 *
 * Prasyarat: BahanPanganSeeder sudah dijalankan (data TKPI harus ada).
 */
class MenuDummySeeder extends Seeder
{
    private array $kelompokList = [
        'TK_PAUD' => 'balita_sd3',
        'SD_1_3'  => 'balita_sd3',
        'SD_4_6'  => 'sd4_ibu_menyusui',
        'SMP'     => 'sd4_ibu_menyusui',
        'SMA'     => 'sd4_ibu_menyusui',
    ];

    private array $anggaranPerKelompok = [
        'balita_sd3'       => 10000,
        'sd4_ibu_menyusui' => 15000,
    ];

    private array $jumlahPorsi = [
        'TK_PAUD' => 80,
        'SD_1_3'  => 150,
        'SD_4_6'  => 150,
        'SMP'     => 200,
        'SMA'     => 200,
    ];

    private array $gramIdx = [
        'TK_PAUD' => 0,
        'SD_1_3'  => 1,
        'SD_4_6'  => 2,
        'SMP'     => 3,
        'SMA'     => 4,
    ];

    private array $bahanConfig = [
        'nasi'         => ['keyword' => 'Nasi putih',      'harga' => 1500,  'gram' => [100, 120, 150, 175, 200]],
        'ayam'         => ['keyword' => 'Ayam',            'harga' => 3200,  'gram' => [40,  50,  60,  70,  80]],
        'ikan_nila'    => ['keyword' => 'Ikan nila',       'harga' => 2400,  'gram' => [40,  50,  60,  70,  80]],
        'ikan_lele'    => ['keyword' => 'Ikan lele',       'harga' => 2000,  'gram' => [40,  50,  60,  70,  80]],
        'ikan_tongkol' => ['keyword' => 'Tongkol',         'harga' => 2200,  'gram' => [40,  50,  60,  70,  80]],
        'telur'        => ['keyword' => 'Telur ayam',      'harga' => 2400,  'gram' => [50,  55,  60,  60,  60]],
        'sapi'         => ['keyword' => 'Daging sapi',     'harga' => 14000, 'gram' => [30,  40,  50,  60,  70]],
        'tempe'        => ['keyword' => 'Tempe',           'harga' => 900,   'gram' => [30,  40,  50,  50,  50]],
        'tahu'         => ['keyword' => 'Tahu putih',      'harga' => 700,   'gram' => [40,  50,  60,  60,  60]],
        'bayam'        => ['keyword' => 'Bayam',           'harga' => 400,   'gram' => [30,  40,  50,  60,  60]],
        'kangkung'     => ['keyword' => 'Kangkung',        'harga' => 350,   'gram' => [30,  40,  50,  60,  60]],
        'wortel'       => ['keyword' => 'Wortel',          'harga' => 500,   'gram' => [25,  30,  40,  50,  50]],
        'buncis'       => ['keyword' => 'Buncis',          'harga' => 600,   'gram' => [25,  30,  40,  50,  50]],
        'kentang'      => ['keyword' => 'Kentang',         'harga' => 800,   'gram' => [40,  50,  60,  70,  80]],
        'kc_panjang'   => ['keyword' => 'Kacang panjang',  'harga' => 500,   'gram' => [25,  30,  40,  50,  50]],
        'tauge'        => ['keyword' => 'Taoge',           'harga' => 400,   'gram' => [20,  25,  30,  40,  40]],
        'pisang'       => ['keyword' => 'Pisang ambon',    'harga' => 350,   'gram' => [80,  100, 100, 120, 120]],
        'pepaya'       => ['keyword' => 'Pepaya',          'harga' => 200,   'gram' => [80,  100, 120, 150, 150]],
        'jeruk'        => ['keyword' => 'Jeruk',           'harga' => 450,   'gram' => [80,  100, 100, 120, 120]],
        'semangka'     => ['keyword' => 'Semangka',        'harga' => 300,   'gram' => [100, 120, 150, 200, 200]],
        'minyak'       => ['keyword' => 'Minyak kelapa',   'harga' => 1600,  'gram' => [5,   5,   8,   10,  10]],
    ];

    private array $menuTemplates = [
        ['nama' => 'Nasi Ayam Goreng Tumis Bayam',        'bahan' => ['nasi', 'ayam',         'bayam',     'tempe',  'pisang',   'minyak']],
        ['nama' => 'Nasi Ikan Nila Goreng Kangkung',      'bahan' => ['nasi', 'ikan_nila',    'kangkung',  'tahu',   'pepaya',   'minyak']],
        ['nama' => 'Nasi Telur Dadar Capcay',             'bahan' => ['nasi', 'telur',        'wortel',    'buncis', 'jeruk',    'minyak']],
        ['nama' => 'Nasi Ikan Lele Goreng Kangkung',      'bahan' => ['nasi', 'ikan_lele',    'kangkung',  'tempe',  'semangka', 'minyak']],
        ['nama' => 'Nasi Ayam Bakar Tumis Buncis',        'bahan' => ['nasi', 'ayam',         'buncis',    'tahu',   'pisang',   'minyak']],
        ['nama' => 'Nasi Tongkol Bumbu Merah Bayam',      'bahan' => ['nasi', 'ikan_tongkol', 'bayam',     'tempe',  'pepaya',   'minyak']],
        ['nama' => 'Nasi Telur Semur Tumis Wortel',       'bahan' => ['nasi', 'telur',        'wortel',    'tahu',   'jeruk',    'minyak']],
        ['nama' => 'Nasi Rendang Daging Sapi Sayuran',    'bahan' => ['nasi', 'sapi',         'kentang',   'buncis', 'semangka', 'minyak']],
        ['nama' => 'Nasi Ikan Nila Bakar Kangkung',       'bahan' => ['nasi', 'ikan_nila',    'kangkung',  'tempe',  'pisang',   'minyak']],
        ['nama' => 'Nasi Ayam Goreng Kacang Panjang',     'bahan' => ['nasi', 'ayam',         'kc_panjang','tahu',   'pepaya',   'minyak']],
        ['nama' => 'Nasi Lele Goreng Tumis Tauge',        'bahan' => ['nasi', 'ikan_lele',    'tauge',     'tempe',  'jeruk',    'minyak']],
        ['nama' => 'Nasi Telur Balado Tumis Buncis',      'bahan' => ['nasi', 'telur',        'buncis',    'tahu',   'semangka', 'minyak']],
        ['nama' => 'Nasi Ayam Kuah Sup Wortel Kentang',   'bahan' => ['nasi', 'ayam',         'wortel',    'kentang','pisang',   'minyak']],
        ['nama' => 'Nasi Tongkol Orek Tumis Bayam',       'bahan' => ['nasi', 'ikan_tongkol', 'bayam',     'tempe',  'pepaya',   'minyak']],
        ['nama' => 'Nasi Daging Sapi Tumis Kangkung',     'bahan' => ['nasi', 'sapi',         'kangkung',  'tahu',   'jeruk',    'minyak']],
        ['nama' => 'Nasi Ikan Nila Pepes Tumis Wortel',   'bahan' => ['nasi', 'ikan_nila',    'wortel',    'tempe',  'semangka', 'minyak']],
        ['nama' => 'Nasi Ayam Goreng Bayam Kuning',       'bahan' => ['nasi', 'ayam',         'bayam',     'tahu',   'pisang',   'minyak']],
        ['nama' => 'Nasi Perkedel Kentang Tumis Buncis',  'bahan' => ['nasi', 'telur',        'kentang',   'buncis', 'pepaya',   'minyak']],
        ['nama' => 'Nasi Lele Bakar Tauge Goreng',        'bahan' => ['nasi', 'ikan_lele',    'tauge',     'tempe',  'jeruk',    'minyak']],
        ['nama' => 'Nasi Tongkol Rica Tumis Kangkung',    'bahan' => ['nasi', 'ikan_tongkol', 'kangkung',  'tahu',   'semangka', 'minyak']],
    ];

    private array $bahanIdCache = [];
    private array $notFound     = [];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════╗');
        $this->command->info('║       MenuDummySeeder — v2 (fixed)       ║');
        $this->command->info('╚══════════════════════════════════════════╝');

        // Cek TKPI
        $totalBahan = DB::table('bahan_pangans')->count();
        $this->command->info("→ Data TKPI di database: {$totalBahan} bahan");
        if ($totalBahan === 0) {
            $this->command->error('Data TKPI kosong! Jalankan dulu:');
            $this->command->error('  php artisan db:seed --class=BahanPanganSeeder');
            return;
        }

        $ketuaId    = $this->getUserId('ketua@mbg.id', 'ketua_sppg')
           ?? (int) DB::table('users')->orderBy('id')->value('id');
        $ahliGiziId = $this->getUserId('gizi@mbg.id', 'ahli_gizi')
           ?? $ketuaId;
        $now        = now();

        $this->command->info('');
        $this->command->info('─── [1/3] Resolving Bahan Pangan dari TKPI ───');
        // Pre-resolve semua bahan sekarang agar log lebih bersih
        foreach (array_keys($this->bahanConfig) as $key) {
            $this->getBahanId($key);
        }

        if (!empty($this->notFound)) {
            $this->command->warn('');
            $this->command->warn('Bahan TIDAK DITEMUKAN di TKPI (cek keyword):');
            foreach ($this->notFound as $key => $kw) {
                $this->command->warn("  ⚠ {$key} → \"{$kw}\"");
            }
        }

        $this->command->info('');
        $this->command->info('─── [2/3] Harga Bahan & Anggaran Porsi ───');
        $this->seedHargaBahan($now);
        $this->seedAnggaranPorsi($ketuaId, $now);

        $this->command->info('');
        $this->command->info('─── [3/3] Menu Harian April - 10 Juni 2026 ───');
        $this->seedMenuHarian($ahliGiziId, $now);

        $this->command->info('');
        $this->command->info('✅ Selesai!');
    }

    private function getUserId(string $email, string $role): ?int
    {
        $user = DB::table('users')->where('email', $email)->first();
        if ($user) return $user->id;
        $user = DB::table('users')->where('role', $role)->first();
        return $user?->id;
    }

    private function getBahanId(string $key): ?int
    {
        if (array_key_exists($key, $this->bahanIdCache)) {
            return $this->bahanIdCache[$key];
        }

        $keyword = $this->bahanConfig[$key]['keyword'];

        // Coba keyword lengkap
        $bahan = DB::table('bahan_pangans')
            ->whereRaw('LOWER(nama_bahan) LIKE ?', ['%' . strtolower($keyword) . '%'])
            ->where('is_active', true)
            ->orderByRaw('LENGTH(nama_bahan) ASC')
            ->first();

        // Fallback: kata pertama
        if (!$bahan) {
            $kata  = strtolower(explode(' ', $keyword)[0]);
            $bahan = DB::table('bahan_pangans')
                ->whereRaw('LOWER(nama_bahan) LIKE ?', ["%{$kata}%"])
                ->where('is_active', true)
                ->orderByRaw('LENGTH(nama_bahan) ASC')
                ->first();
        }

        if ($bahan) {
            $this->command->line("  ✓ [{$key}] → \"{$bahan->nama_bahan}\" (id={$bahan->id})");
            $this->bahanIdCache[$key] = $bahan->id;
        } else {
            $this->notFound[$key]     = $keyword;
            $this->bahanIdCache[$key] = null;
        }

        return $this->bahanIdCache[$key];
    }

    // Harga bahan — TANPA user_id (sesuai migration 2026_04_24_052918)
    private function seedHargaBahan($now): void
    {
        foreach (array_keys($this->bahanConfig) as $key) {
            $bahanId = $this->getBahanId($key);
            if (!$bahanId) continue;

            $exists = DB::table('harga_bahans')
                ->where('bahan_pangan_id', $bahanId)
                ->where('berlaku_mulai', '2026-04-01')
                ->exists();

            if (!$exists) {
                DB::table('harga_bahans')->insert([
                    'bahan_pangan_id' => $bahanId,
                    'harga_per_100g'  => $this->bahanConfig[$key]['harga'],
                    'berlaku_mulai'   => '2026-04-01',
                    'berlaku_sampai'  => null,
                    'keterangan'      => 'Dummy — seeder April-10 Juni 2026',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $this->command->line("  ✓ Harga [{$key}]: Rp {$this->bahanConfig[$key]['harga']}/100g");
            }
        }
    }

    // Anggaran porsi — kolom `kelompok` (enum), FK `created_by`
    // BUKAN kelompok_sasaran, BUKAN user_id
    private function seedAnggaranPorsi(?int $createdBy, $now): void
    {
        foreach ($this->anggaranPerKelompok as $kelompok => $anggaran) {
            $exists = DB::table('anggaran_porsis')
                ->where('kelompok', $kelompok)
                ->where('berlaku_mulai', '2026-04-01')
                ->exists();

            if (!$exists) {
                DB::table('anggaran_porsis')->insert([
                    'kelompok'           => $kelompok,
                    'anggaran_per_porsi' => $anggaran,
                    'berlaku_mulai'      => '2026-04-01',
                    'berlaku_sampai'     => null,
                    'keterangan'         => 'Dummy — seeder April-10 Juni 2026',
                    'created_by'         => $createdBy,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
                $this->command->line("  ✓ Anggaran [{$kelompok}]: Rp " . number_format($anggaran, 0, ',', '.') . "/porsi");
            } else {
                $this->command->line("  - [{$kelompok}] sudah ada, dilewati.");
            }
        }
    }

    private function seedMenuHarian(int $userId, $now): void
    {
        $startDate   = Carbon::create(2026, 4, 1);
        $endDate     = Carbon::create(2026, 6, 10);
        $templateIdx = 0;
        $created     = 0;
        $skipped     = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isWeekend()) continue;

            $template = $this->menuTemplates[$templateIdx % count($this->menuTemplates)];
            $templateIdx++;

            foreach ($this->kelompokList as $kelompokSasaran => $kelompokAnggaran) {

                // Cek duplikat (unique constraint: tanggal + kelompok_sasaran)
                $exists = DB::table('menu_harians')
                    ->where('tanggal', $date->toDateString())
                    ->where('kelompok_sasaran', $kelompokSasaran)
                    ->exists();

                if ($exists) { $skipped++; continue; }

                $menuId = DB::table('menu_harians')->insertGetId([
                    'tanggal'            => $date->toDateString(),
                    'user_id'            => $userId,
                    'nama_menu'          => $template['nama'],
                    'status'             => 'final',
                    'kelompok'           => $kelompokAnggaran,
                    'kelompok_sasaran'   => $kelompokSasaran,
                    'jumlah_porsi'       => $this->jumlahPorsi[$kelompokSasaran],
                    'anggaran_per_porsi' => $this->anggaranPerKelompok[$kelompokAnggaran],
                    'catatan_anggaran'   => null,
                    'foto_menu'          => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                // Detail bahan — kolom: jumlah_gram, harga_per_100g
                // BUKAN gram_per_porsi, BUKAN harga_snapshot
                $idx = $this->gramIdx[$kelompokSasaran];
                foreach ($template['bahan'] as $bahanKey) {
                    $bahanId = $this->getBahanId($bahanKey);
                    if (!$bahanId) continue;

                    DB::table('menu_detail_bahans')->insert([
                        'menu_harian_id'  => $menuId,
                        'bahan_pangan_id' => $bahanId,
                        'jumlah_gram'     => $this->bahanConfig[$bahanKey]['gram'][$idx],
                        'jumlah_porsi'    => $this->jumlahPorsi[$kelompokSasaran],
                        'harga_per_100g'  => $this->bahanConfig[$bahanKey]['harga'],
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }

                $created++;
            }
        }

        $this->command->info("  ✓ Menu berhasil dibuat : {$created}");
        $this->command->info("  - Menu dilewati (duplikat): {$skipped}");
        $this->command->info("  ✓ Kelompok : " . implode(', ', array_keys($this->kelompokList)));
        $this->command->info("  ✓ Periode  : Rabu 1 April - Rabu 10 Juni 2026 (hari kerja)");
    }
}
