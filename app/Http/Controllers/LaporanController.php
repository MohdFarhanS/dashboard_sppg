<?php

namespace App\Http\Controllers;

use App\Constants\AKG;
use App\Models\MenuHarian;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rap2hpoutre\FastExcel\FastExcel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : now()->format('Y-m');
        $jenis = $request->input('jenis', $user->isAkuntan() ? 'biaya' : 'gizi');

        if ($user->isAkuntan()) {
            $jenis = 'biaya';
        }
        if ($user->isAhliGizi()) {
            $jenis = 'gizi';
        }

        [$tahun, $bln] = explode('-', $bulan);

        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->where('status', 'final')
            ->orderBy('tanggal');

        $menus = $query->get();

        $menuCalc = $this->hitungMenuCalc($menus);

        $totalMenu = $menus->count();
        $rataGizi = $this->hitungRataGizi($menus, $menuCalc);

        $totalBiaya = collect($menuCalc)->sum(fn ($c) => $c['biaya']['total_seluruh']);
        $rataCost = $totalMenu > 0 ? collect($menuCalc)->avg(fn ($c) => $c['biaya']['cost_per_porsi']) : 0;

        return view('laporan.index', compact(
            'menus', 'bulan', 'jenis',
            'totalMenu', 'rataGizi', 'totalBiaya', 'rataCost',
            'tahun', 'bln', 'menuCalc'
        ));
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : now()->format('Y-m');
        $jenis = $request->input('jenis', $user->isAkuntan() ? 'biaya' : 'gizi');

        if ($user->isAkuntan()) {
            $jenis = 'biaya';
        }
        if ($user->isAhliGizi()) {
            $jenis = 'gizi';
        }
        $nama = 'Laporan_'.ucfirst($jenis).'_'.$bulan.'.xlsx';

        [$tahun, $bln] = explode('-', $bulan);

        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->where('status', 'final')
            ->orderBy('tanggal');

        $menus = $query->get();

        if ($jenis === 'biaya') {
            $rows = $menus->map(function ($menu, $i) {
                $b = $menu->totalBiaya();
                $ks = $menu->kelompok_sasaran ?? 'SD_4_6';
                $ksLabel = AKG::KELOMPOK[$ks]['label'] ?? $ks;
                $status = match ($menu->statusAnggaran()) {
                    'over' => 'Over Budget',
                    'warning' => 'Mendekati Batas',
                    'aman' => 'Aman',
                    default => '-',
                };

                return [
                    'No' => $i + 1,
                    'Tanggal' => $menu->tanggal->format('d/m/Y'),
                    'Kelompok' => $ksLabel,
                    'Nama Menu' => $menu->nama_menu ?? '-',
                    'Jumlah Porsi' => $menu->jumlah_porsi ?? 1,
                    'Total Biaya (Rp)' => round($b['total_seluruh']),
                    'Cost/Porsi (Rp)' => round($b['cost_per_porsi']),
                    'Anggaran/Porsi (Rp)' => round($b['anggaran']),
                    'Selisih (Rp)' => round($b['selisih']),
                    '% Anggaran' => $b['persen_anggaran'].'%',
                    'Status' => $status,
                ];
            });
        } else {
            $rows = $menus->map(function ($menu, $i) {
                $g = $menu->totalGizi();
                $ks = $menu->kelompok_sasaran ?? 'SD_4_6';
                $ksLabel = AKG::KELOMPOK[$ks]['label'] ?? $ks;
                $akgRef = array_merge(AKG::MAKAN_SIANG, $menu->akgTarget('siang'));

                return [
                    'No' => $i + 1,
                    'Tanggal' => $menu->tanggal->format('d/m/Y'),
                    'Kelompok' => $ksLabel,
                    'Nama Menu' => $menu->nama_menu ?? '-',
                    'Energi (kkal)' => round($g['energi'], 1),
                    '% AKG Energi' => round($g['energi'] / $akgRef['energi'] * 100).'%',
                    'Protein (g)' => round($g['protein'], 1),
                    'Lemak (g)' => round($g['lemak'], 1),
                    'Karbohidrat (g)' => round($g['karbohidrat'], 1),
                    'Serat (g)' => round($g['serat'], 1),
                    'Kalsium (mg)' => round($g['kalsium'], 1),
                    'Fe (mg)' => round($g['besi'], 2),
                    'Vit C (mg)' => round($g['vit_c'], 1),
                ];
            });
        }

        return (new FastExcel($rows))->download($nama);
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = $request->filled('bulan') ? $request->input('bulan') : now()->format('Y-m');
        $jenis = $request->input('jenis', $user->isAkuntan() ? 'biaya' : 'gizi');

        if ($user->isAkuntan()) {
            $jenis = 'biaya';
        }
        if ($user->isAhliGizi()) {
            $jenis = 'gizi';
        }

        [$tahun, $bln] = explode('-', $bulan);

        $query = MenuHarian::with('detailBahans.bahanPangan')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bln)
            ->where('status', 'final')
            ->orderBy('tanggal');

        $menus = $query->get();
        $menuCalc = $this->hitungMenuCalc($menus);
        $rataGizi = $this->hitungRataGizi($menus, $menuCalc);
        $totalBiaya = collect($menuCalc)->sum(fn ($c) => $c['biaya']['total_seluruh']);
        $bulanLabel = Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y');

        $view = $jenis === 'biaya' ? 'laporan.pdf-biaya' : 'laporan.pdf-gizi';
        $nama = 'Laporan_'.ucfirst($jenis).'_'.$bulan.'.pdf';

        $pdf = Pdf::loadView($view, compact(
            'menus', 'bulan', 'bulanLabel',
            'rataGizi', 'totalBiaya', 'user', 'menuCalc'
        ))->setPaper('a4', 'landscape');

        return $pdf->download($nama);
    }

    /**
     * Precompute gizi/biaya/status sekali per menu, keyed by id, agar view
     * tak perlu panggil ulang totalBiaya()/statusAnggaran() per baris (N+1).
     */
    private function hitungMenuCalc($menus): array
    {
        $menuCalc = [];
        foreach ($menus as $menu) {
            $biaya = $menu->totalBiaya();
            $menuCalc[$menu->id] = [
                'gizi' => $menu->totalGizi(),
                'biaya' => $biaya,
                'status' => MenuHarian::deriveStatusAnggaran($biaya),
            ];
        }

        return $menuCalc;
    }

    private function hitungRataGizi($menus, array $menuCalc = []): array
    {
        $keys = ['energi', 'protein', 'lemak', 'karbohidrat', 'serat', 'kalsium', 'besi', 'vit_c'];
        $total = array_fill_keys($keys, 0);
        $count = $menus->count();

        foreach ($menus as $menu) {
            $gizi = $menuCalc[$menu->id]['gizi'] ?? $menu->totalGizi();
            foreach ($keys as $k) {
                $total[$k] += $gizi[$k] ?? 0;
            }
        }

        if ($count === 0) {
            return $total;
        }

        return array_map(fn ($v) => round($v / $count, 1), $total);
    }
}
