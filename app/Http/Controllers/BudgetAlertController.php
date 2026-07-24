<?php

namespace App\Http\Controllers;

use App\Models\MenuHarian;
use Illuminate\Http\Request;

class BudgetAlertController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : today()->format('Y-m');
        $severity = $request->input('severity', '');

        [$tahun, $bulanAngka] = explode('-', $bulan);

        $menusFinal = MenuHarian::with('detailBahans.bahanPangan')
            ->where('status', 'final')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulanAngka)
            ->orderBy('tanggal', 'desc')
            ->get();

        // Klasifikasi tiap menu
        $alerts = [];
        $countOver = 0;
        $countWarning = 0;
        $countAman = 0;
        $countBelumAdaData = 0;
        $alertMenuIds = [];

        foreach ($menusFinal as $menu) {
            $status = $menu->statusAnggaran();
            $biaya = $menu->totalBiaya();

            if ($status === 'over') {
                $countOver++;
                $alertMenuIds[] = $menu->id;
            } elseif ($status === 'warning') {
                $countWarning++;
                $alertMenuIds[] = $menu->id;
            } elseif ($status === 'belum_ada_data') {
                // Anggaran/biaya belum diatur — jangan dihitung sebagai 'aman'.
                $countBelumAdaData++;
            } else {
                $countAman++;
            }

            // Filter severity
            if ($severity === 'over' && $status !== 'over') {
                continue;
            }
            if ($severity === 'warning' && $status !== 'warning') {
                continue;
            }
            if ($severity === 'aman' && $status !== 'aman') {
                continue;
            }

            // Hanya tampilkan over dan warning jika tidak ada filter
            if ($severity === '' && $status !== 'over' && $status !== 'warning') {
                continue;
            }

            $alerts[] = [
                'menu' => $menu,
                'status' => $status,
                'biaya' => $biaya,
                'cost_porsi' => $biaya['cost_per_porsi'],
                'anggaran' => $biaya['anggaran'],
                'selisih' => $biaya['selisih'],
                'persen' => $biaya['persen_anggaran'],
            ];
        }

        $adaAlertBelumDilihat = $this->hitungAlertBelumDilihat()->isNotEmpty();

        return view('budget-alert.index', compact(
            'alerts', 'bulan', 'severity',
            'countOver', 'countWarning', 'countAman', 'countBelumAdaData',
            'adaAlertBelumDilihat'
        ));
    }

    /**
     * Tandai alert bulan berjalan sebagai sudah dilihat (dismiss notifikasi sidebar/navbar).
     * Sengaja dipisah dari index() (GET) — GET tidak boleh punya efek samping,
     * jika tidak prefetch/refresh diam-diam bisa membuang alert tanpa aksi user.
     */
    public function dismiss(Request $request)
    {
        $alertMenuIds = $this->hitungAlertBelumDilihat()->all();

        $existing = session('dismissed_alert_ids', []);
        session(['dismissed_alert_ids' => array_unique(array_merge($existing, $alertMenuIds))]);

        return redirect()->back();
    }

    /**
     * Menu final bulan berjalan berstatus over/warning yang belum ditandai dilihat.
     * Dipakai bersama oleh index() (cek tombol dismiss) dan dismiss() (aksi dismiss),
     * sama seperti query yang dipakai view composer navbar/sidebar.
     */
    private function hitungAlertBelumDilihat()
    {
        $today = today();
        $dismissedIds = session('dismissed_alert_ids', []);

        return MenuHarian::where('status', 'final')
            ->whereYear('tanggal', $today->year)
            ->whereMonth('tanggal', $today->month)
            ->with('detailBahans.bahanPangan')
            ->get()
            ->filter(fn ($menu) => in_array($menu->statusAnggaran(), ['over', 'warning'])
                && ! in_array($menu->id, $dismissedIds))
            ->pluck('id');
    }
}
