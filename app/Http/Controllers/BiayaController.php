<?php

namespace App\Http\Controllers;

use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuHarian;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BiayaController extends Controller
{
    // ─── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : now()->format('Y-m');
        [$tahun, $bln] = explode('-', $bulan);

        $alertSummary = ['over' => 0, 'warning' => 0, 'aman' => 0];

        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->where('status', 'final')
            ->orderBy('tanggal');

        $menus = $query->get();

        $rekapBiaya = $menus->map(function ($m) use (&$alertSummary) {
            // Hitung totalBiaya() sekali, derive status dari hasilnya — hindari
            // memanggil ulang totalBiaya() lewat statusAnggaran() (audit D3).
            $biaya = $m->totalBiaya();
            $status = MenuHarian::deriveStatusAnggaran($biaya);
            if (isset($alertSummary[$status])) {
                $alertSummary[$status]++;
            }

            return [
                'menu_id' => $m->id,
                'tanggal' => $m->tanggal->format('d/m/Y'),
                'menu' => $m->nama_menu,
                'biaya' => $biaya,
                'status' => $status,
            ];
        });

        $totalHari = $menus->count();
        $totalBiayaBulan = $rekapBiaya->sum(fn ($r) => $r['biaya']['total_seluruh']);
        $rataCostPorsi = $totalHari > 0 ? $rekapBiaya->avg(fn ($r) => $r['biaya']['cost_per_porsi']) : 0;
        $rataAnggaran = $totalHari > 0 ? $rekapBiaya->avg(fn ($r) => $r['biaya']['anggaran']) : 0;

        $trendBiaya = $rekapBiaya->map(fn ($r) => [
            'tanggal' => $r['tanggal'],
            'cost_per_porsi' => $r['biaya']['cost_per_porsi'],
            'anggaran' => $r['biaya']['anggaran'],
        ])->values();

        $overBudget = $rekapBiaya->filter(fn ($r) => $r['status'] === 'over')->count();
        $underBudget = $rekapBiaya->filter(fn ($r) => $r['status'] !== 'over')->count();

        return view('biaya.dashboard', compact(
            'bulan', 'rekapBiaya', 'trendBiaya',
            'totalHari', 'totalBiayaBulan', 'rataCostPorsi',
            'rataAnggaran', 'overBudget', 'underBudget',
            'alertSummary',
        ));
    }

    // ─── Manajemen Harga ───────────────────────────────────────────────────────
    public function indexHarga(Request $request)
    {
        // dilindungi middleware role:ketua_sppg,akuntan

        $q = $request->input('q');

        $hargaList = HargaBahan::with('bahanPangan')
            ->join('bahan_pangans', 'harga_bahans.bahan_pangan_id', '=', 'bahan_pangans.id')
            ->when($q, fn ($query) => $query->where('bahan_pangans.nama_bahan', 'like', "%{$q}%"))
            ->orderBy('bahan_pangans.nama_bahan')
            ->orderByDesc('harga_bahans.berlaku_mulai')
            ->select('harga_bahans.*')
            ->paginate(20)
            ->withQueryString();

        return view('biaya.harga-index', compact('hargaList', 'q'));
    }

    public function createHarga()
    {
        // dilindungi middleware role:akuntan
        return view('biaya.harga-form');
    }

    public function storeHarga(Request $request)
    {
        // dilindungi middleware role:akuntan

        // berlaku_sampai sengaja tidak diterima dari request: tarif immutable, penutupan
        // periode dihitung sistem (lihat trait PeriodeBerlaku) supaya tidak ada rentang
        // tumpang tindih yang bikin hargaAktif() non-deterministik.
        $data = $request->validate([
            'bahan_pangan_id' => 'required|exists:bahan_pangans,id',
            'harga_per_kg' => 'required|numeric|min:0',
            'berlaku_mulai' => 'required|date',
            'keterangan' => 'nullable|string|max:200',
        ]);

        $mulaiStr = Carbon::parse($data['berlaku_mulai'])->toDateString();

        try {
            // Penutupan tarif lama + pembuatan tarif baru harus satu unit: kegagalan di
            // tengah jalan meninggalkan bahan tanpa tarif aktif (hargaAktif() jatuh ke 0).
            DB::transaction(function () use ($data, $mulaiStr) {
                HargaBahan::tetapkanPeriode(
                    ['bahan_pangan_id' => $data['bahan_pangan_id']],
                    $mulaiStr,
                    [
                        'harga_per_100g' => $data['harga_per_kg'] / 10,
                        'keterangan' => $data['keterangan'] ?? null,
                    ]
                );
            });
        } catch (UniqueConstraintViolationException) {
            // Dua akuntan submit tanggal yang sama secara bersamaan — unique index DB
            // yang menahannya setelah pre-check lolos. Jawab sebagai error validasi.
            throw ValidationException::withMessages([
                'berlaku_mulai' => 'Sudah ada tarif untuk bahan ini yang berlaku mulai tanggal tersebut. Pilih tanggal lain.',
            ]);
        }

        return redirect()->route('biaya.harga.index')
            ->with('success', 'Harga bahan berhasil disimpan.');
    }

    public function editHarga(HargaBahan $harga)
    {
        // Tarif tidak bisa diedit — immutable setelah dibuat (seperti anggaran per porsi)
        return redirect()->route('biaya.harga.index')
            ->with('info', 'Tarif harga tidak dapat diedit. Tambahkan tarif baru untuk menggantinya — tarif lama akan otomatis ditutup.');
    }

    public function updateHarga(Request $request, HargaBahan $harga)
    {
        // Tidak ada jalur edit yang valid
        return redirect()->route('biaya.harga.index')
            ->with('info', 'Tarif harga tidak dapat diedit. Tambahkan tarif baru untuk menggantinya.');
    }

    public function destroyHarga(HargaBahan $harga)
    {
        // dilindungi middleware role:akuntan

        $isAktif = $harga->berlaku_sampai === null;
        $bahanId = $harga->bahan_pangan_id;

        // Hapus + pemulihan tarif aktif harus atomik: tanpa transaksi, kegagalan di
        // langkah kedua meninggalkan bahan tanpa tarif open-ended sama sekali.
        DB::transaction(function () use ($harga, $isAktif, $bahanId) {
            $harga->delete();

            // Jika yang dihapus adalah tarif aktif (open-ended), buka kembali tarif terakhir
            // yang tersisa — bukan sekadar "yang sebelumnya", supaya tidak tumpang tindih
            // dengan periode yang mulai setelahnya.
            if ($isAktif) {
                HargaBahan::pastikanPeriodeTerakhirTerbuka(['bahan_pangan_id' => $bahanId]);
            }
        });

        $pesan = $isAktif
            ? 'Tarif aktif dihapus. Tarif sebelumnya diaktifkan kembali.'
            : 'Data tarif historis dihapus.';

        return redirect()->route('biaya.harga.index')->with('success', $pesan);
    }

    // ─── Detail Biaya per Menu ─────────────────────────────────────────────────
    public function detailMenu(MenuHarian $menu)
    {
        $menu->load('detailBahans.bahanPangan');
        $biaya = $menu->totalBiaya();

        return view('biaya.detail-menu', compact('menu', 'biaya'));
    }

    // ─── API Estimasi ──────────────────────────────────────────────────────────
    public function apiEstimasi(Request $request)
    {
        $tanggal = $request->input('tanggal', today()->toDateString());
        $items = $request->input('items', []);
        $porsi = max((int) $request->input('jumlah_porsi', 1), 1);

        $total = 0;
        $detail = [];

        // Batch-load harga + nama bahan sekali per request, bukan per item (audit D3).
        $ids = collect($items)->map(fn ($item) => (int) ($item['bahan_pangan_id'] ?? 0))->filter()->unique()->all();
        $hargaMap = HargaBahan::hargaAktifBatch($ids, $tanggal);
        $bahanMap = BahanPangan::whereIn('id', $ids)->get(['id', 'nama_bahan'])->keyBy('id');

        foreach ($items as $item) {
            $id = (int) ($item['bahan_pangan_id'] ?? 0);
            $gram = (float) ($item['jumlah_gram'] ?? 0);
            if (! $id || ! $gram) {
                continue;
            }

            $harga = $hargaMap[$id] ?? 0.0;
            $biaya = ($gram / 100) * $harga * $porsi;
            $total += $biaya;

            $detail[] = [
                'bahan_pangan_id' => $id,
                'nama' => $bahanMap->get($id)?->nama_bahan,
                'gram' => $gram,
                'harga_per_100g' => $harga,
                'biaya' => round($biaya, 0),
            ];
        }

        return response()->json([
            'total_seluruh' => round($total, 0),
            'cost_per_porsi' => round($total / $porsi, 0),
            'detail' => $detail,
        ]);
    }

    private function authorizeUnit(string $unitSppg): void
    {
        // Single SPPG — semua pengguna terautentikasi boleh akses
    }
}
