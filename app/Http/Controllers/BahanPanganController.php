<?php

namespace App\Http\Controllers;

use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BahanPanganController extends Controller
{
    // 13 kategori TKPI
    const KATEGORI_LIST = [
        'Serealia', 'Umbi', 'Kacang', 'Sayuran', 'Buah',
        'Daging', 'Ikan', 'Telur', 'Susu', 'Lemak',
        'Gula', 'Bumbu', 'Minuman',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Daftar bahan pangan dengan search & filter
     */
    public function index(Request $request)
    {
        $query = BahanPangan::query();

        // Search
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_bahan', 'like', "%{$cari}%")
                    ->orWhere('kode', 'like', "%{$cari}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter sub_kategori
        if ($request->filled('sub_kategori')) {
            $query->where('sub_kategori', $request->sub_kategori);
        }

        // Sort
        $sortBy = $request->get('sort', 'kode');
        $sortDir = $request->get('dir', 'asc');
        $allowed = ['kode', 'nama_bahan', 'kategori', 'energi', 'protein', 'lemak', 'karbohidrat'];
        if (! in_array($sortBy, $allowed)) {
            $sortBy = 'kode';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $bahanPangans = $query->orderBy($sortBy, $sortDir)->paginate(20)->withQueryString();

        // Stats untuk info panel
        $stats = [
            'total' => BahanPangan::count(),
            'per_kategori' => BahanPangan::selectRaw('kategori, COUNT(*) as total')
                ->groupBy('kategori')
                ->orderBy('kategori')
                ->pluck('total', 'kategori'),
        ];

        // Batch query harga aktif untuk bahan di halaman ini
        $tanggal = today()->toDateString();
        $bahanIds = $bahanPangans->pluck('id')->toArray();
        $hargaMap = HargaBahan::whereIn('bahan_pangan_id', $bahanIds)
            ->where('berlaku_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tanggal);
            })
            ->orderByDesc('berlaku_mulai')
            ->get()
            ->groupBy('bahan_pangan_id')
            ->map(fn ($items) => (float) $items->first()->harga_per_100g);

        return view('bahan-pangan.index', compact('bahanPangans', 'stats', 'hargaMap'));
    }

    /**
     * Form tambah bahan pangan
     */
    public function create()
    {
        $this->checkAdminRole();

        return view('bahan-pangan.form', [
            'bahan' => null,
            'kategoriList' => self::KATEGORI_LIST,
            'title' => 'Tambah Bahan Pangan',
        ]);
    }

    /**
     * Simpan bahan pangan baru
     */
    public function store(Request $request)
    {
        $this->checkAdminRole();

        $validated = $this->validateBahan($request);

        $bahan = DB::transaction(function () use ($validated) {
            $bahan = BahanPangan::create($validated);

            ImportLog::create([
                'user_id' => auth()->id(),
                'filename' => 'Manual Input',
                'inserted' => 1,
                'updated' => 0,
                'skipped' => 0,
                'mode' => 'manual',
            ]);

            return $bahan;
        });

        return redirect()->route('bahan-pangan.show', $bahan)
            ->with('success', 'Bahan pangan <strong>'.e($validated['nama_bahan']).'</strong> berhasil ditambahkan.');
    }

    /**
     * Detail bahan pangan
     */
    public function show(BahanPangan $bahanPangan)
    {
        return view('bahan-pangan.show', compact('bahanPangan'));
    }

    /**
     * Form edit
     */
    public function edit(BahanPangan $bahanPangan)
    {
        $this->checkAdminRole();

        return view('bahan-pangan.form', [
            'bahan' => $bahanPangan,
            'kategoriList' => self::KATEGORI_LIST,
            'title' => 'Edit Bahan Pangan',
        ]);
    }

    /**
     * Update bahan pangan
     */
    public function update(Request $request, BahanPangan $bahanPangan)
    {
        $this->checkAdminRole();

        $validated = $this->validateBahan($request, $bahanPangan->id);
        $bahanPangan->update($validated);

        return redirect()->route('bahan-pangan.index')
            ->with('success', 'Data <strong>'.e($bahanPangan->nama_bahan).'</strong> berhasil diperbarui.');
    }

    /**
     * Hapus bahan pangan
     */
    public function destroy(BahanPangan $bahanPangan)
    {
        $this->checkAdminRole();

        $nama = $bahanPangan->nama_bahan;
        $bahanPangan->delete();

        return redirect()->route('bahan-pangan.index')
            ->with('success', 'Bahan pangan <strong>'.e($nama).'</strong> berhasil dihapus.');
    }

    /**
     * Toggle status aktif
     */
    public function toggleStatus(BahanPangan $bahanPangan)
    {
        $this->checkAdminRole();

        $bahanPangan->update(['is_active' => ! $bahanPangan->is_active]);
        $status = $bahanPangan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Bahan pangan berhasil {$status}.");
    }

    /**
     * API: Search untuk Select2 / autocomplete
     */
    public function apiSearch(Request $request)
    {
        $q = $request->input('q', '');
        $limit = $request->input('limit', 8);

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $tanggal = today()->toDateString();

        $results = BahanPangan::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('nama_bahan', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            })
            ->select([
                'id', 'kode', 'nama_bahan', 'kategori',
                'energi', 'protein', 'lemak', 'karbohidrat', 'bdd',
            ])
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($tanggal) {
                $item->bdd = $item->bdd ?? 100;
                $item->harga_per_100g = HargaBahan::hargaAktif($item->id, $tanggal) ?: null;

                return $item;
            });

        return response()->json($results);
    }

    /**
     * Validasi input bahan pangan
     */
    private function validateBahan(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'kode' => 'required|string|max:10|unique:bahan_pangans,kode'.($ignoreId ? ",{$ignoreId}" : ''),
            'kode_lama' => 'nullable|string|max:10',
            'nama_bahan' => 'required|string|max:255',
            'kategori' => 'required|in:'.implode(',', self::KATEGORI_LIST),
            'sub_kategori' => 'nullable|in:TUNGGAL,OLAHAN',
            'sumber' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];

        // Semua kolom nutrisi: nullable|numeric
        $nutrisiCols = [
            'bdd', 'air', 'energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'abu',
            'kalsium', 'fosfor', 'besi', 'natrium', 'kalium', 'tembaga', 'seng',
            'retinol', 'b_karoten', 'kar_total', 'thiamin', 'riboflavin', 'niasin', 'vit_c',
        ];
        foreach ($nutrisiCols as $col) {
            $rules[$col] = 'nullable|numeric|min:0';
        }

        return $request->validate($rules);
    }

    /**
     * Helper cadangan Ã¢â‚¬â€ route sudah dilindungi middleware role:ketua_sppg
     */
    private function checkAdminRole(): void
    {
        if (auth()->user()->role !== 'ketua_sppg') {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk Ketua SPPG.');
        }
    }
}
