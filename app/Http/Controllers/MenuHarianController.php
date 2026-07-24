<?php

namespace App\Http\Controllers;

use App\Models\AnggaranPorsi;
use App\Models\HargaBahan;
use App\Models\MenuHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MenuHarianController extends Controller
{
    public function index(Request $request)
    {
        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->orderByDesc('tanggal');

        if ($request->filled('bulan')) {
            [$tahun, $bln] = explode('-', $request->bulan);
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bln);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kelompok_sasaran')) {
            $query->where('kelompok_sasaran', $request->kelompok_sasaran);
        }

        $menus = $query->paginate(15)->withQueryString();

        $menuCalc = [];
        foreach ($menus as $menu) {
            $biaya = $menu->totalBiaya();
            $menuCalc[$menu->id] = [
                'gizi' => $menu->totalGizi(),
                'biaya' => $biaya,
                'status' => MenuHarian::deriveStatusAnggaran($biaya),
            ];
        }

        return view('menu-harian.index', compact('menus', 'menuCalc'));
    }

    public function create()
    {
        return redirect()->route('simulasi.index')
            ->with('info', 'Untuk membuat menu baru, gunakan fitur Simulasi Menu.');
    }

    public function store(Request $request)
    {
        return redirect()->route('simulasi.index')
            ->with('info', 'Untuk membuat menu baru, gunakan fitur Simulasi Menu.');
    }

    public function show(MenuHarian $menuHarian)
    {
        $this->authorizeUnit($menuHarian);
        $menuHarian->load('detailBahans.bahanPangan');

        return view('menu-harian.show', compact('menuHarian'));
    }

    public function edit(MenuHarian $menuHarian)
    {
        // Route dilindungi middleware role:ahli_gizi
        // Cek status
        if ($menuHarian->status === 'final') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah final, tidak bisa diedit.');
        }

        $menuHarian->load('detailBahans.bahanPangan');

        // Siapkan data existing bahans untuk JS prefill
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
            ])->values();

        return view('menu-harian.edit', compact('menuHarian', 'existingBahans'));
    }

    public function update(Request $request, MenuHarian $menuHarian)
    {
        $this->authorizeUnit($menuHarian);

        if ($menuHarian->status === 'final') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah final, tidak bisa diedit.');
        }

        $data = $request->validate([
            'nama_menu' => 'nullable|string|max:200',
            'catatan' => 'nullable|string|max:200',
            'status' => 'required|in:draft',
            'kelompok' => 'nullable|in:balita_sd3,sd4_ibu_menyusui',
            'bahans' => 'nullable|array',
            'bahans.*.bahan_pangan_id' => 'required_with:bahans|exists:bahan_pangans,id',
            'bahans.*.jumlah_gram' => 'required_with:bahans|numeric|min:0.01',
            'bahans.*.jumlah_porsi' => 'nullable|integer|min:1',
        ]);

        $bahans = $data['bahans'] ?? [];
        $kelompok = $data['kelompok'] ?? $menuHarian->kelompok ?? 'sd4_ibu_menyusui';

        $menuHarian->fill([
            'nama_menu' => $data['nama_menu'] ?? $menuHarian->nama_menu,
            'catatan' => $data['catatan'] ?? null,
            'kelompok' => $kelompok,
        ]);
        $menuHarian->status = $data['status'];
        $menuHarian->anggaran_per_porsi = AnggaranPorsi::aktif(
            $menuHarian->tanggal->toDateString(), $kelompok
        );
        $menuHarian->save();

        $menuHarian->detailBahans()->delete();

        $defaultJumlahPorsi = max((int) $menuHarian->jumlah_porsi, 1);

        foreach ($bahans as $b) {
            $menuHarian->detailBahans()->create([
                'bahan_pangan_id' => $b['bahan_pangan_id'],
                'jumlah_gram' => $b['jumlah_gram'],
                'jumlah_porsi' => $b['jumlah_porsi'] ?? $defaultJumlahPorsi,
            ]);
        }

        return redirect()->route('menu-harian.show', $menuHarian)
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(MenuHarian $menuHarian)
    {
        $this->authorizeUnit($menuHarian);

        if ($menuHarian->status === 'final') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah final, tidak bisa dihapus.');
        }

        $menuHarian->delete();

        return redirect()->route('menu-harian.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    private function authorizeUnit(MenuHarian $menu): void
    {
        // Single SPPG — semua pengguna terautentikasi boleh akses
    }

    public function uploadFoto(Request $request, MenuHarian $menuHarian)
    {
        if ($menuHarian->status === 'final') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah final, tidak bisa mengubah foto.');
        }

        $validator = Validator::make($request->all(), [
            'foto_menu' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'foto_menu.required' => 'Pilih foto menu terlebih dahulu.',
            'foto_menu.image' => 'File harus berupa gambar.',
            'foto_menu.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'foto_menu.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', $validator->errors()->first('foto_menu'));
        }

        if ($menuHarian->foto_menu) {
            Storage::disk('public')->delete($menuHarian->foto_menu);
        }

        $path = $request->file('foto_menu')->store('menu-foto', 'public');
        $menuHarian->update(['foto_menu' => $path]);

        return redirect()->route('menu-harian.show', $menuHarian)
            ->with('success', 'Foto menu berhasil diupload.');
    }

    public function finalize(MenuHarian $menuHarian)
    {
        // Route dilindungi middleware role:ahli_gizi
        // Hanya bisa finalisasi kalau masih draft
        if ($menuHarian->status !== 'draft') {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah berstatus final.');
        }

        if (! $menuHarian->foto_menu) {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Upload foto menu terlebih dahulu sebelum finalisasi.');
        }

        // Kunci baris di dalam transaksi + re-cek status: dua submit finalize
        // bersamaan pada menu yang sama tidak boleh keduanya lolos dan menimpa
        // snapshot harga/anggaran secara parsial.
        $sudahFinalDuluan = DB::transaction(function () use ($menuHarian) {
            $menu = MenuHarian::whereKey($menuHarian->id)->lockForUpdate()->first();

            if ($menu->status !== 'draft') {
                return true;
            }

            $tgl = $menu->tanggal->toDateString();

            $menu->status = 'final';
            $menu->anggaran_per_porsi = AnggaranPorsi::aktif($tgl, $menu->kelompok);
            $menu->save();

            // Kunci harga tiap bahan pada tanggal menu — snapshot agar tidak berubah
            // jika tarif harga bahan diperbarui di kemudian hari
            foreach ($menu->detailBahans as $detail) {
                if ($detail->harga_per_100g === null) {
                    $harga = HargaBahan::hargaAktif($detail->bahan_pangan_id, $tgl);
                    $detail->update(['harga_per_100g' => $harga]);
                }
            }

            return false;
        });

        if ($sudahFinalDuluan) {
            return redirect()->route('menu-harian.show', $menuHarian)
                ->with('error', 'Menu sudah berstatus final.');
        }

        return redirect()->route('menu-harian.show', $menuHarian)
            ->with('success', 'Menu berhasil difinalisasi.');
    }
}
