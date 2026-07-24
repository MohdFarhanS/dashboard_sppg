<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\MenuHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : now()->format('Y-m');
        [$tahun, $bln] = explode('-', $bulan);

        $keys = ['energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'kalsium', 'besi', 'vit_c'];

        $menus = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->orderBy('tanggal')
            ->get();

        $menusFinal = $menus->filter(fn ($m) => $m->status === 'final');
        $jumlahHari = $menusFinal->count();

        // Hitung gizi/biaya/status/AKG sekali per menu (semua status), keyed by id.
        // Dipakai ulang untuk kartu ringkasan (final saja) & tabel view (semua) — hindari N+1.
        $menuCalc = [];
        foreach ($menus as $menu) {
            $gizi = $menu->totalGizi();
            $biaya = $menu->totalBiaya();
            $status = MenuHarian::deriveStatusAnggaran($biaya);
            $akgMenu = array_merge(AKG::MAKAN_SIANG, $menu->akgTarget('siang'));
            $pctAkg = $akgMenu['energi'] > 0 ? round($gizi['energi'] / $akgMenu['energi'] * 100) : 0;
            $clsAkg = $pctAkg < 70 ? 'kurang' : ($pctAkg > 130 ? 'lebih' : 'cukup');

            $menuCalc[$menu->id] = compact('gizi', 'biaya', 'status', 'pctAkg', 'clsAkg');
        }

        $totalGizi = array_fill_keys($keys, 0);
        $totalBiaya = 0;
        $budgetTotal = 0;
        $distribusiBiaya = [];
        $alertList = [];
        $alertOver = 0;
        $alertWarning = 0;
        $trendData = [];

        foreach ($menusFinal as $menu) {
            $gizi = $menuCalc[$menu->id]['gizi'];
            $biaya = $menuCalc[$menu->id]['biaya'];

            foreach ($keys as $k) {
                $totalGizi[$k] += $gizi[$k] ?? 0;
            }

            $totalBiaya += $biaya['total_seluruh'] ?? 0;
            $budgetTotal += ($biaya['anggaran'] ?? 0) * ($menu->jumlah_porsi ?? 1);

            $trendData[] = [
                'tanggal' => $menu->tanggal->format('d/m'),
                'energi' => round($gizi['energi'] ?? 0, 1),
            ];

            $status = $menuCalc[$menu->id]['status'];
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

            // Reuse detail biaya yang sudah dihitung sekali di $menuCalc (totalBiaya() di
            // atas) — hindari query hargaAktif() ulang per bahan (audit D3).
            foreach ($biaya['detail'] as $d) {
                if ($d['biaya'] <= 0) {
                    continue;
                }
                $kategori = $d['kategori'] ?? 'Lainnya';
                $distribusiBiaya[$kategori] = ($distribusiBiaya[$kategori] ?? 0) + $d['biaya'];
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
            'user', 'stats', 'menus', 'bulan', 'menuCalc',
            'rataGizi', 'persenAkg', 'trendData', 'jumlahHari'
        ));
    }
}
