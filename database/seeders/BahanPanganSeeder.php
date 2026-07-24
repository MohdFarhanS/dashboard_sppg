<?php

namespace Database\Seeders;

use App\Models\BahanPangan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BahanPanganSeeder extends Seeder
{
    /**
     * Seed data TKPI (Tabel Komposisi Pangan Indonesia) - 845 bahan pangan
     * Data source: tkpi_seeder.json di database/seeders/data/
     */
    public function run(): void
    {
        $this->command->info('Memuat data TKPI...');

        // Hapus data lama — gunakan DELETE agar kompatibel dengan MySQL 8+ yang
        // memblokir TRUNCATE pada tabel dengan foreign key meski FK checks dinonaktifkan
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('bahan_pangans')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $jsonPath = database_path('seeders/data/tkpi_seeder.json');

        if (! file_exists($jsonPath)) {
            $this->command->error('File tidak ditemukan: '.$jsonPath);
            $this->command->warn('Pastikan file tkpi_seeder.json sudah ada di database/seeders/data/');

            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (! $data) {
            $this->command->error('Gagal membaca JSON atau data kosong.');

            return;
        }

        $this->command->info('Total data: '.count($data).' bahan pangan');

        // Insert batch per 100 untuk performa
        $chunks = array_chunk($data, 100);
        $total = 0;

        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $item) {
                $rows[] = [
                    'kode' => $item['kode'] ?? null,
                    'kode_lama' => $item['kode_lama'] ?? null,
                    'nama_bahan' => $item['nama_bahan'] ?? '-',
                    'kategori' => $item['kategori'] ?? 'Lainnya',
                    'sub_kategori' => $item['sub_kategori'] ?? null,
                    'sumber' => $item['sumber'] ?? null,
                    'bdd' => $this->toFloat($item['bdd'] ?? null),
                    'air' => $this->toFloat($item['air'] ?? null),
                    'energi' => $this->toFloat($item['energi'] ?? null),
                    'protein' => $this->toFloat($item['protein'] ?? null),
                    'lemak' => $this->toFloat($item['lemak'] ?? null),
                    'karbohidrat' => $this->toFloat($item['karbohidrat'] ?? null),
                    'serat' => $this->toFloat($item['serat'] ?? null),
                    'abu' => $this->toFloat($item['abu'] ?? null),
                    'kalsium' => $this->toFloat($item['kalsium'] ?? null),
                    'fosfor' => $this->toFloat($item['fosfor'] ?? null),
                    'besi' => $this->toFloat($item['besi'] ?? null),
                    'natrium' => $this->toFloat($item['natrium'] ?? null),
                    'kalium' => $this->toFloat($item['kalium'] ?? null),
                    'tembaga' => $this->toFloat($item['tembaga'] ?? null),
                    'seng' => $this->toFloat($item['seng'] ?? null),
                    'retinol' => $this->toFloat($item['retinol'] ?? null),
                    'b_karoten' => $this->toFloat($item['b_karoten'] ?? null),
                    'kar_total' => $this->toFloat($item['kar_total'] ?? null),
                    'thiamin' => $this->toFloat($item['thiamin'] ?? null),
                    'riboflavin' => $this->toFloat($item['riboflavin'] ?? null),
                    'niasin' => $this->toFloat($item['niasin'] ?? null),
                    'vit_c' => $this->toFloat($item['vit_c'] ?? null),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('bahan_pangans')->insert($rows);
            $total += count($rows);
            $this->command->info("  Inserted: {$total} records...");
        }

        $this->command->info('✅ Selesai! Total '.$total.' data TKPI berhasil dimuat.');

        // Summary per kategori
        $this->command->info('');
        $this->command->info('Ringkasan per kategori:');
        $summary = BahanPangan::selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->get();

        foreach ($summary as $row) {
            $this->command->line("  {$row->kategori}: {$row->total} bahan");
        }
    }

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        return (float) $value;
    }
}
