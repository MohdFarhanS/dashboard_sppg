<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\HargaBahan;
use App\Models\MenuHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $bulan = $request->input('bulan', now()->format('Y-m'));
        [$tahun, $bln] = explode('-', $bulan);

        $keys = ['energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'kalsium', 'besi', 'vit_c'];

        $menus = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->orderBy('tanggal')
            ->get();

        $menusFinal = $menus->filter(fn ($m) => $m->status === 'final');
        $jumlahHari = $menusFinal->count();

        $totalGizi = array_fill_keys($keys, 0);
        $totalBiaya = 0;
        $budgetTotal = 0;
        $distribusiBiaya = [];
        $alertList = [];
        $alertOver = 0;
        $alertWarning = 0;
        $trendData = [];

        foreach ($menusFinal as $menu) {
            $gizi = $menu->totalGizi();
            $biaya = $menu->totalBiaya();

            foreach ($keys as $k) {
                $totalGizi[$k] += $gizi[$k] ?? 0;
            }

            $totalBiaya += $biaya['total_seluruh'] ?? 0;
            $budgetTotal += ($biaya['anggaran'] ?? 15000) * ($menu->jumlah_porsi ?? 1);

            $trendData[] = [
                'tanggal' => $menu->tanggal->format('d/m'),
                'energi' => round($gizi['energi'] ?? 0, 1),
            ];

            $status = $menu->statusAnggaran();
            if ($status === 'over') {
                $alertOver++;
                $alertList[] = [
                    'type' => 'danger',
                    'msg' => 'Menu '.($menu->nama_menu ?? $menu->tanggal->format('d/m/Y')).' melebihi anggaran',
                    'time' => $menu->tanggal->format('d/m/Y'),
                ];
            } elseif ($status === 'warning') {
                $alertWarning++;
                $alertList[] = [
                    'type' => 'warning',
                    'msg' => 'Menu '.($menu->nama_menu ?? $menu->tanggal->format('d/m/Y')).' mendekati batas anggaran',
                    'time' => $menu->tanggal->format('d/m/Y'),
                ];
            }

            foreach ($menu->detailBahans as $detail) {
                $bahan = $detail->bahanPangan;
                if (! $bahan) {
                    continue;
                }
                $kategori = $bahan->kategori ?? 'Lainnya';
                $harga = HargaBahan::hargaAktif($bahan->id, $menu->tanggal->toDateString());
                if (! $harga) {
                    continue;
                }
                $biayaBahan = ($detail->jumlah_gram / 100) * $harga * ($detail->jumlah_porsi ?? 1);
                $distribusiBiaya[$kategori] = ($distribusiBiaya[$kategori] ?? 0) + $biayaBahan;
            }
        }

        $rataGizi = [];
        $persenAkg = [];
        foreach ($keys as $k) {
            $rataGizi[$k] = $jumlahHari > 0 ? round($totalGizi[$k] / $jumlahHari, 1) : 0;
            $acuan = AKG::MAKAN_SIANG[$k] ?? 1;
            $persenAkg[$k] = $acuan > 0 ? min(round(($rataGizi[$k] / $acuan) * 100, 1), 200) : 0;
        }

        $tanpaLainnya = array_filter($distribusiBiaya, fn ($k) => $k !== 'Lainnya', ARRAY_FILTER_USE_KEY);
        arsort($tanpaLainnya);
        $top = array_slice($tanpaLainnya, 0, 6, true);
        $sisanya = array_sum($distribusiBiaya) - array_sum($top);
        if ($sisanya > 0) {
            $top['Lainnya'] = $sisanya;
        }

        $persenBiaya = $budgetTotal > 0 ? round($totalBiaya / $budgetTotal * 100) : 0;
        $statusBudget = $budgetTotal > 0
            ? ($persenBiaya > 100 ? 'over' : ($persenBiaya >= 85 ? 'warning' : 'aman'))
            : 'belum_ada_data';

        $stats = [
            'total_menu_final' => $jumlahHari,
            'total_menu_semua' => $menus->count(),
            'rata_kalori' => $jumlahHari > 0 ? round($totalGizi['energi'] / $jumlahHari) : 0,
            'target_kalori' => AKG::MAKAN_SIANG['energi'],
            'total_biaya' => round($totalBiaya),
            'budget_total' => $budgetTotal,
            'status_budget' => $statusBudget,
            'persen_biaya' => $persenBiaya,
            'total_alert' => $alertOver + $alertWarning,
            'alert_over' => $alertOver,
            'alert_list' => array_slice($alertList, 0, 5),
            'distribusi_biaya' => $top,
        ];

        return view('dashboard.index', compact(
            'user', 'stats', 'menus', 'bulan',
            'rataGizi', 'persenAkg', 'trendData', 'jumlahHari'
        ));
    }
}
