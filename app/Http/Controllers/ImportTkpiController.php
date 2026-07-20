<?php

namespace App\Http\Controllers;

use App\Models\BahanPangan;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportTkpiController extends Controller
{
    // Mapping kolom CSV → kolom database (sesuai kolom tabel bahan_pangans)
    const KOLOM_MAP = [
        'nama_bahan' => ['nama_bahan', 'nama bahan', 'food name', 'bahan pangan', 'nama'],
        'kategori' => ['kategori', 'category', 'golongan', 'kelompok pangan'],
        'sub_kategori' => ['sub_kategori', 'sub_category', 'jenis', 'type'],
        'kode_lama' => ['kode_lama', 'kode lama', 'old code', 'kode_asli'],
        'sumber' => ['sumber', 'source', 'sumber data', 'referensi'],
        'bdd' => ['bdd', 'berat dapat dimakan', '%bdd', 'edible portion'],
        'air' => ['air', 'water', 'moisture'],
        'energi' => ['energi', 'energy', 'kalori', 'kal', 'kkal'],
        'protein' => ['protein'],
        'lemak' => ['lemak', 'fat', 'total fat'],
        'karbohidrat' => ['karbohidrat', 'carbohydrate', 'karbo', 'kh'],
        'serat' => ['serat', 'fiber', 'dietary fiber', 'serat pangan'],
        'abu' => ['abu', 'ash'],
        'kalsium' => ['kalsium', 'calcium', 'ca'],
        'fosfor' => ['fosfor', 'phosphorus', 'phosphor', 'p'],
        'besi' => ['besi', 'iron', 'fe', 'zat besi'],
        'natrium' => ['natrium', 'sodium', 'na'],
        'kalium' => ['kalium', 'potassium', 'k'],
        'tembaga' => ['tembaga', 'copper', 'cu'],
        'seng' => ['seng', 'zinc', 'zn'],
        'retinol' => ['retinol', 'vitamin a', 'vit a', 'vit_a'],
        'b_karoten' => ['b_karoten', 'b karoten', 'beta karoten', 'beta carotene', 'karoten'],
        'kar_total' => ['kar_total', 'karoten total', 'total carotene', 'total karoten'],
        'thiamin' => ['thiamin', 'thiamine', 'vitamin b1', 'vit b1', 'vit_b1', 'b1'],
        'riboflavin' => ['riboflavin', 'vitamin b2', 'vit b2', 'vit_b2', 'b2'],
        'niasin' => ['niasin', 'niacin', 'vitamin b3', 'vit b3', 'b3'],
        'vit_c' => ['vit_c', 'vitamin c', 'vit c', 'vitc', 'asam askorbat'],
    ];

    public function index()
    {
        // Route dilindungi middleware role:ketua_sppg
        $totalBahan = BahanPangan::count();
        $riwayat = ImportLog::with('user')->latest()->limit(10)->get();

        return view('import-tkpi.index', compact('totalBahan', 'riwayat'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:5120',
        ], [
            'csv_file.required' => 'File CSV wajib dipilih.',
            'csv_file.mimes' => 'Format harus .csv atau .txt',
            'csv_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $ekstensi = strtolower($request->file('csv_file')->getClientOriginalExtension());
        if (! in_array($ekstensi, ['csv', 'txt'])) {
            return back()->withErrors(['csv_file' => 'Format harus .csv atau .txt']);
        }

        $path = $request->file('csv_file')->getRealPath();
        [$headers, $rows, $error] = $this->parseCsv($path);

        if ($error) {
            return back()->withErrors(['csv_file' => $error]);
        }

        $mapped = $this->mapKolom($headers);
        $preview = array_slice($rows, 0, 10); // max 10 baris preview

        // Simpan path sementara di session
        $tmpPath = storage_path('app/tmp_import_'.auth()->id().'.csv');
        copy($path, $tmpPath);

        $originalFilename = $request->file('csv_file')->getClientOriginalName();
        session(['import_tmp' => $tmpPath, 'import_headers' => $headers, 'import_mapped' => $mapped, 'import_filename' => $originalFilename]);

        return view('import-tkpi.index', [
            'totalBahan' => BahanPangan::count(),
            'riwayat' => ImportLog::with('user')->latest()->limit(10)->get(),
            'headers' => $headers,
            'preview' => $preview,
            'mapped' => $mapped,
            'totalRows' => count($rows),
        ]);
    }

    public function import(Request $request)
    {
        $tmpPath = session('import_tmp');
        $mapped = session('import_mapped');
        $filename = session('import_filename', 'unknown.csv');
        $mode = $request->input('mode', 'skip'); // skip | update

        if (! $tmpPath || ! file_exists($tmpPath)) {
            return back()->withErrors(['csv_file' => 'Session preview sudah kedaluwarsa. Upload ulang file CSV.']);
        }

        [$headers, $rows] = $this->parseCsv($tmpPath);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $idx => $row) {
                $data = $this->mapRow($headers, $row, $mapped);

                if (empty($data['nama_bahan'])) {
                    $errors[] = 'Baris '.($idx + 2).': nama_bahan kosong, dilewati.';
                    $skipped++;

                    continue;
                }

                $existing = BahanPangan::whereRaw('LOWER(nama_bahan) = ?', [strtolower($data['nama_bahan'])])->first();

                if ($existing) {
                    if ($mode === 'update') {
                        $existing->update($this->filterData($data));
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    BahanPangan::create($this->filterData($data));
                    $inserted++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('import-tkpi.index')
                ->withErrors(['csv_file' => 'Gagal import: '.$e->getMessage()]);
        }

        // Hapus file tmp
        @unlink($tmpPath);
        session()->forget(['import_tmp', 'import_headers', 'import_mapped', 'import_filename']);

        // Simpan ringkasan log ke database
        try {
            ImportLog::create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'mode' => $mode,
            ]);
        } catch (\Exception $e) {
            // Log tidak kritis — data bahan_pangans tetap tersimpan
        }

        return redirect()->route('import-tkpi.index')
            ->with('success', "Import selesai: {$inserted} ditambah, {$updated} diupdate, {$skipped} dilewati.")
            ->with('import_errors', $errors);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [[], [], 'File tidak dapat dibaca.'];
        }

        // Deteksi delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $headers = [];
        $rows = [];

        while (($line = fgetcsv($handle, 2000, $delimiter)) !== false) {
            if (empty($headers)) {
                $headers = array_map(function ($h) {
                    // Strip BOM, whitespace, dan karakter tidak terlihat
                    $h = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim($h));

                    return strtolower($h);
                }, $line);

                continue;
            }
            if (count(array_filter($line)) === 0) {
                continue;
            }
            $rows[] = $line;
        }

        fclose($handle);

        if (empty($headers)) {
            return [[], [], 'File CSV kosong atau format tidak valid.'];
        }

        return [$headers, $rows, null];
    }

    private function mapKolom(array $headers): array
    {
        $mapped = [];
        foreach (self::KOLOM_MAP as $dbCol => $aliases) {
            foreach ($headers as $idx => $header) {
                if (in_array($header, $aliases)) {
                    $mapped[$dbCol] = $idx;
                    break;
                }
            }
        }

        return $mapped;
    }

    private function mapRow(array $headers, array $row, array $mapped): array
    {
        $data = [];
        foreach ($mapped as $dbCol => $idx) {
            $data[$dbCol] = isset($row[$idx]) ? trim($row[$idx]) : null;
        }

        return $data;
    }

    private function filterData(array $data): array
    {
        $numericCols = [
            'bdd', 'air', 'energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'abu',
            'kalsium', 'fosfor', 'besi', 'natrium', 'kalium', 'tembaga', 'seng',
            'retinol', 'b_karoten', 'kar_total', 'thiamin', 'riboflavin', 'niasin', 'vit_c',
        ];
        foreach ($numericCols as $col) {
            if (isset($data[$col])) {
                $data[$col] = (float) str_replace(',', '.', $data[$col]);
            }
        }

        // Generate kode maks 10 karakter, pastikan unik
        if (empty($data['kode'])) {
            $singkatan = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['nama_bahan'] ?? 'BHN'), 0, 6));
            do {
                $kode = substr($singkatan.rand(1000, 9999), 0, 10);
            } while (BahanPangan::where('kode', $kode)->exists());
            $data['kode'] = $kode;
        }

        // Default kategori
        if (empty($data['kategori'])) {
            $data['kategori'] = 'Umum';
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
