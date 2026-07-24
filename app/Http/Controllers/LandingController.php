<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\MenuHarian;
use App\Models\PesanMasuk;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        // Menu final hari ini — untuk kalkulasi stat (tidak perlu foto)
        $todayStatsMenus = MenuHarian::where('status', 'final')
            ->whereDate('tanggal', today())
            ->with('detailBahans.bahanPangan')
            ->get();

        // Menu final hari ini yang punya foto — untuk card MENU HARI INI
        $todayMenus = MenuHarian::where('status', 'final')
            ->whereDate('tanggal', today())
            ->whereNotNull('foto_menu')
            ->get();

        // Total porsi bulan ini (reset tiap bulan)
        $totalPorsi = MenuHarian::where('status', 'final')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah_porsi');

        $avgEnergi = 0;
        $avgBiaya = 0;
        $avgPctAkg = 0;
        if ($todayStatsMenus->isNotEmpty()) {
            $avgEnergi = (int) round($todayStatsMenus->avg(fn ($m) => $m->totalGizi()['energi']));
            $avgBiaya = (int) round($todayStatsMenus->avg(fn ($m) => $m->totalBiaya()['cost_per_porsi'] ?? 0));
            $avgPctAkg = (int) round($todayStatsMenus->avg(function ($m) {
                $gizi = $m->totalGizi();
                $akg = array_merge(AKG::MAKAN_SIANG, $m->akgTarget('siang'));

                return $akg['energi'] > 0 ? ($gizi['energi'] / $akg['energi'] * 100) : 0;
            }));
        }

        return view('landing', compact('todayMenus', 'totalPorsi', 'avgEnergi', 'avgBiaya', 'avgPctAkg'));
    }

    public function kirimPesan(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'pesan' => 'required|string|max:1000',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid (contoh: 08123456789).',
            'pesan.required' => 'Pesan wajib diisi.',
            'pesan.max' => 'Pesan maksimal 1000 karakter.',
        ]);

        PesanMasuk::create($request->only('nama', 'no_hp', 'pesan'));

        return redirect()->route('landing')->with('pesan_terkirim', true);
    }
}
