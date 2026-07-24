<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\MenuHarian;
use Illuminate\Http\Request;

class GiziController extends Controller
{
    public function dashboard(Request $request)
    {
        return redirect()->route('dashboard', $request->only('bulan'));
    }

    // API endpoint untuk chart AJAX
    public function apiTrend(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        [$tahun, $bln] = explode('-', $bulan);

        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->where('status', 'final')
            ->orderBy('tanggal');

        $menus = $query->get();

        $data = $menus->map(function ($m) {
            $g = $m->totalGizi();

            return ['tanggal' => $m->tanggal->format('d/m')] + $g;
        });

        return response()->json([
            'data' => $data,
            'akg' => AKG::MAKAN_SIANG,
            'label' => AKG::LABEL,
        ]);
    }
}
