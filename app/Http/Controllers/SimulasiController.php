<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\AnggaranPorsi;
use App\Models\BahanPangan;
use App\Models\HargaBahan;
use App\Models\MenuDetailBahan;
use App\Models\MenuHarian;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SimulasiController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();
        $anggaranBalitaSd3 = AnggaranPorsi::aktif($today, 'balita_sd3');
        $anggaranSd4IbuMenyusui = AnggaranPorsi::aktif($today, 'sd4_ibu_menyusui');

        return view('simulasi.index', compact('anggaranBalitaSd3', 'anggaranSd4IbuMenyusui'));
    }

    public function kalkulasi(Request $request)
    {
        // Route dilindungi middleware role:ahli_gizi
        $request->validate([
            'bahans' => 'required|array|min:1',
            'bahans.*.id' => 'required|exists:bahan_pangans,id',
            'bahans.*.gram' => 'required|numeric|min:1',
            'bahans.*.porsi' => 'required|integer|min:1',
            'jumlah_porsi' => 'required|integer|min:1',
            'tanggal' => 'nullable|date',
            'kelompok' => 'nullable|string',
        ]);

        $kelompok = $request->input('kelompok', 'SD_4_6');
        if (! array_key_exists($kelompok, AKG::KELOMPOK)) {
            $kelompok = 'SD_4_6';
        }
        $akgTarget = AKG::targetSajian($kelompok, 'siang');

        $jumlahPorsi = (int) $request->jumlah_porsi;
        $tanggal = $request->tanggal ?? today()->toDateString();

        // giziTotal akumulasi nilai batch (× sajian), dibagi jumlahPorsi di akhir → gizi per orang
        $giziTotal = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0,
            'serat' => 0, 'kalsium' => 0, 'besi' => 0, 'vit_c' => 0];
        $biayaTotal = 0;
        $detail = [];

        // Batch-load bahan + harga sekali per request, bukan per item (audit D3).
        $bahanIds = collect($request->bahans)->pluck('id')->unique()->all();
        $bahanMap = BahanPangan::whereIn('id', $bahanIds)->get()->keyBy('id');
        $hargaMap = HargaBahan::hargaAktifBatch($bahanIds, $tanggal);

        foreach ($request->bahans as $item) {
            $b = $bahanMap->get($item['id']);
            if (! $b) {
                continue;
            }

            $gram = (float) $item['gram'];
            $porsi = (int) $item['porsi'];
            $bdd = ($b->bdd ?? 100) / 100;
            $faktor = ($gram * $bdd) / 100;

            $giziItemBatch = [];
            foreach (array_keys($giziTotal) as $k) {
                $val = $faktor * ($b->$k ?? 0) * $porsi; // nilai batch
                $giziTotal[$k] += $val;
                $giziItemBatch[$k] = $val;
            }

            $hargaVal = $hargaMap[$b->id] ?? 0.0;
            $biayaItem = $hargaVal > 0 ? ($gram * $porsi / 100) * $hargaVal : 0;
            $biayaTotal += $biayaItem;

            $detail[] = [
                'id' => $b->id,
                'nama' => $b->nama_bahan,
                'kode' => $b->kode,
                'kategori' => $b->kategori,
                'gram' => $gram,
                'porsi' => $porsi,
                'bdd' => $b->bdd,
                // gizi per orang untuk tampilan detail tabel simulasi
                'gizi' => array_map(fn ($v) => round($v / $jumlahPorsi, 2), $giziItemBatch),
                'harga_per_100g' => $hargaVal,
                'biaya' => round($biayaItem, 0),
                'ada_harga' => $hargaVal > 0,
            ];
        }

        $costPerPorsi = $jumlahPorsi > 0 ? $biayaTotal / $jumlahPorsi : 0;

        $anggaranPerKelompok = [
            'balita_sd3' => AnggaranPorsi::aktif($tanggal, 'balita_sd3'),
            'sd4_ibu_menyusui' => AnggaranPorsi::aktif($tanggal, 'sd4_ibu_menyusui'),
        ];
        $kelompokAnggaran = AKG::toAnggaranKelompok($kelompok);
        $anggaran = $anggaranPerKelompok[$kelompokAnggaran];
        $totalAngg = $anggaran * $jumlahPorsi;
        $selisih = $totalAngg - $biayaTotal;
        $persenAngg = $totalAngg > 0
            ? round($biayaTotal / $totalAngg * 100, 1) : 0;

        // persen_akg vs target 1 orang — bagi giziTotal dengan jumlahPorsi dulu
        $persenAkg = [];
        foreach (array_keys($giziTotal) as $k) {
            $giziPerOrang = $jumlahPorsi > 0 ? $giziTotal[$k] / $jumlahPorsi : 0;
            $target = ($akgTarget[$k] ?? 0) > 0 ? $akgTarget[$k] : (AKG::MAKAN_SIANG[$k] ?? 1);
            $persenAkg[$k] = $target > 0
                ? round($giziPerOrang / $target * 100, 1) : 0;
        }

        return response()->json([
            // gizi per orang (per porsi) — bukan total batch
            'gizi' => array_map(fn ($v) => round($v / $jumlahPorsi, 2), $giziTotal),
            'persen_akg' => $persenAkg,
            'akg_target' => $akgTarget,
            'detail' => $detail,
            'biaya' => [
                'total' => round($biayaTotal, 0),
                'cost_per_porsi' => round($costPerPorsi, 0),
                'anggaran' => $anggaran,
                'selisih' => round($selisih, 0),
                'persen_anggaran' => $persenAngg,
                'anggaran_per_kelompok' => $anggaranPerKelompok,
            ],
        ]);
    }

    public function editMenu(MenuHarian $menuHarian)
    {
        // Route dilindungi middleware role:ahli_gizi
        if ($menuHarian->status === 'final') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah final, tidak bisa diedit.');
        }

        $menuHarian->load('detailBahans.bahanPangan');

        $today = $menuHarian->tanggal->toDateString();

        // Batch-load harga sekali, bukan per detail bahan (audit D3).
        $bahanIds = $menuHarian->detailBahans->pluck('bahanPangan.id')->filter()->unique()->all();
        $hargaMap = HargaBahan::hargaAktifBatch($bahanIds, $today);

        $existingBahans = $menuHarian->detailBahans
            ->filter(fn ($d) => $d->bahanPangan)
            ->map(fn ($d) => [
                'id' => $d->bahanPangan->id,
                'kode' => $d->bahanPangan->kode,
                'nama_bahan' => $d->bahanPangan->nama_bahan,
                'kategori' => $d->bahanPangan->kategori,
                'energi' => $d->bahanPangan->energi,
                'protein' => $d->bahanPangan->protein,
                'lemak' => $d->bahanPangan->lemak,
                'karbohidrat' => $d->bahanPangan->karbohidrat,
                'bdd' => $d->bahanPangan->bdd,
                'jumlah_gram' => $d->jumlah_gram,
                'jumlah_porsi' => $d->jumlah_porsi,
                'harga_per_100g' => $hargaMap[$d->bahanPangan->id] ?? 0.0,
            ])->values();

        $anggaranBalitaSd3 = AnggaranPorsi::aktif($today, 'balita_sd3');
        $anggaranSd4IbuMenyusui = AnggaranPorsi::aktif($today, 'sd4_ibu_menyusui');

        return view('simulasi.index', compact(
            'menuHarian', 'existingBahans',
            'anggaranBalitaSd3', 'anggaranSd4IbuMenyusui'
        ));
    }

    public function simpan(Request $request)
    {
        // Route dilindungi middleware role:ahli_gizi
        $request->validate([
            'tanggal' => 'required|date',
            'nama_menu' => 'nullable|string|max:100',
            'catatan' => 'required|string|max:255',
            'jumlah_porsi' => 'required|integer|min:1',
            'bahans' => 'required|array|min:1',
            'bahans.*.id' => 'required|exists:bahan_pangans,id',
            'bahans.*.gram' => 'required|numeric|min:1',
            'bahans.*.porsi' => 'required|integer|min:1',
            'menu_id' => 'nullable|integer|exists:menu_harians,id',
            'kelompok' => 'nullable|in:balita_sd3,sd4_ibu_menyusui',
            'kelompok_sasaran' => 'nullable|string',
        ]);

        $kelompokSasaran = $request->input('kelompok_sasaran', 'SD_4_6');
        if (! array_key_exists($kelompokSasaran, AKG::KELOMPOK)) {
            $kelompokSasaran = 'SD_4_6';
        }

        // Tentukan kelompok anggaran secara otomatis dari kelompok_sasaran
        $kelompok = AKG::toAnggaranKelompok($kelompokSasaran);

        // ── Mode edit: perbarui menu yang sudah ada ─────────────────────────
        if ($request->filled('menu_id')) {
            $menu = MenuHarian::findOrFail($request->menu_id);

            if ($menu->status === 'final') {
                return response()->json([
                    'error' => 'Menu sudah final, tidak bisa diedit.',
                ], 422);
            }

            $tglMenu = $menu->tanggal->toDateString();

            // Pindah kelompok_sasaran bisa bentrok dengan menu lain di tanggal yang sama
            // (unique (tanggal, kelompok_sasaran)) — tolak sebelum transaksi menghapus detail.
            if ($kelompokSasaran !== $menu->kelompok_sasaran
                && $this->menuBentrok($tglMenu, $kelompokSasaran, $menu->id)) {
                return $this->responsDuplikat($tglMenu, $kelompokSasaran, $menu->id);
            }

            // Batch-load harga sekali sebelum transaksi (audit D3), bukan per bahan di dalamnya.
            $hargaMap = HargaBahan::hargaAktifBatch(
                collect($request->bahans)->pluck('id')->unique()->all(),
                $tglMenu
            );

            try {
                DB::transaction(function () use ($request, $menu, $kelompok, $kelompokSasaran, $hargaMap) {
                    $tgl = $menu->tanggal->toDateString();

                    $menu->fill([
                        'nama_menu' => $request->nama_menu,
                        'catatan_anggaran' => $request->catatan,
                        'jumlah_porsi' => $request->jumlah_porsi,
                        'kelompok' => $kelompok,
                        'kelompok_sasaran' => $kelompokSasaran,
                    ]);
                    $menu->anggaran_per_porsi = AnggaranPorsi::aktif($tgl, $kelompok);
                    $menu->save();

                    $menu->detailBahans()->delete();

                    foreach ($request->bahans as $item) {
                        $harga = $hargaMap[(int) $item['id']] ?? 0.0;
                        MenuDetailBahan::create([
                            'menu_harian_id' => $menu->id,
                            'bahan_pangan_id' => $item['id'],
                            'jumlah_gram' => $item['gram'],
                            'jumlah_porsi' => $item['porsi'],
                            'harga_per_100g' => $harga > 0 ? $harga : null,
                        ]);
                    }
                });
            } catch (UniqueConstraintViolationException) {
                // Menu lain menempati slot (tanggal, kelompok_sasaran) di sela pre-check
                // dan commit. Transaksi sudah rollback, jadi menu ini utuh.
                return $this->responsDuplikat($tglMenu, $kelompokSasaran, $menu->id);
            }

            return response()->json([
                'success' => 'Menu berhasil diperbarui.',
                'redirect' => route('menu-harian.show', $menu),
            ]);
        }

        // ── Mode tambah baru ─────────────────────────────────────────────────
        if ($this->menuBentrok($request->tanggal, $kelompokSasaran)) {
            return $this->responsDuplikat($request->tanggal, $kelompokSasaran);
        }

        // Batch-load harga sekali sebelum transaksi (audit D3), bukan per bahan di dalamnya.
        $hargaMap = HargaBahan::hargaAktifBatch(
            collect($request->bahans)->pluck('id')->unique()->all(),
            $request->tanggal
        );

        try {
            DB::transaction(function () use ($request, $kelompok, $kelompokSasaran, $hargaMap) {
                $tgl = $request->tanggal;
                $menu = MenuHarian::forceCreate([
                    'tanggal' => $tgl,
                    'nama_menu' => $request->nama_menu,
                    'catatan_anggaran' => $request->catatan,
                    'jumlah_porsi' => $request->jumlah_porsi,
                    'kelompok' => $kelompok,
                    'kelompok_sasaran' => $kelompokSasaran,
                    'anggaran_per_porsi' => AnggaranPorsi::aktif($tgl, $kelompok),
                    'status' => 'draft',
                    'user_id' => Auth::id(),
                ]);

                foreach ($request->bahans as $item) {
                    $harga = $hargaMap[(int) $item['id']] ?? 0.0;
                    MenuDetailBahan::create([
                        'menu_harian_id' => $menu->id,
                        'bahan_pangan_id' => $item['id'],
                        'jumlah_gram' => $item['gram'],
                        'jumlah_porsi' => $item['porsi'],
                        'harga_per_100g' => $harga > 0 ? $harga : null,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Submit paralel: request lain menyimpan slot yang sama setelah pre-check
            // di atas lolos. Unique index (tanggal, kelompok_sasaran) yang menahan,
            // transaksi rollback, lalu jawab 422 seperti duplikat biasa — bukan 500.
            return $this->responsDuplikat($request->tanggal, $kelompokSasaran);
        }

        return response()->json([
            'success' => 'Menu berhasil disimpan sebagai draft. Silakan upload foto menu sebelum melakukan finalisasi.',
            'redirect' => route('menu-harian.index'),
        ]);
    }

    /**
     * Apakah slot (tanggal, kelompok_sasaran) sudah ditempati menu lain?
     * $kecualiId dipakai saat mode edit agar menu itu sendiri tidak dihitung bentrok.
     */
    private function menuBentrok(string $tanggal, string $kelompokSasaran, ?int $kecualiId = null): bool
    {
        return $this->queryMenuBentrok($tanggal, $kelompokSasaran, $kecualiId)->exists();
    }

    /**
     * Respons 422 seragam untuk bentrok slot (tanggal, kelompok_sasaran),
     * lengkap dengan link edit ke menu yang sudah menempati slot tersebut.
     */
    private function responsDuplikat(string $tanggal, string $kelompokSasaran, ?int $kecualiId = null)
    {
        $existing = $this->queryMenuBentrok($tanggal, $kelompokSasaran, $kecualiId)->first();

        return response()->json([
            'error' => 'Menu untuk tanggal dan kelompok ini sudah ada. Silakan edit menu yang sudah ada.',
            'redirect' => $existing ? route('simulasi.edit-simulasi', $existing) : null,
        ], 422);
    }

    private function queryMenuBentrok(string $tanggal, string $kelompokSasaran, ?int $kecualiId = null)
    {
        return MenuHarian::whereDate('tanggal', $tanggal)
            ->where('kelompok_sasaran', $kelompokSasaran)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId));
    }
}
