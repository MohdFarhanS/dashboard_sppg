<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Biaya {{ $bulanLabel }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; margin: 0; padding: 0; }
        body { padding: 15px; }

        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #0f4c81; padding-bottom: 10px; }
        .header h2 { font-size: 14pt; color: #0f4c81; font-weight: bold; }
        .header p  { font-size: 9pt; color: #555; margin-top: 3px; }

        table.main {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.main th {
            background: #0f4c81;
            color: #fff;
            padding: 5px 6px;
            font-size: 8pt;
        }
        table.main td {
            padding: 4px 6px;
            border-bottom: 1px solid #eee;
            font-size: 8pt;
        }
        table.main tr:nth-child(even) td { background: #f0f5fc; }
        table.main tfoot td {
            background: #daeeff;
            font-weight: bold;
            border-top: 2px solid #0f4c81;
        }
        .badge-aman    { color: #0f4c81; background: #daeeff; padding: 1px 5px; border-radius: 3px; }
        .badge-over    { color: #842029; background: #f8d7da; padding: 1px 5px; border-radius: 3px; }
        .badge-warning { color: #664d03; background: #fff3cd; padding: 1px 5px; border-radius: 3px; }

        .footer { margin-top: 20px; font-size: 8pt; color: #aaa; text-align: right; }
        .ttd { margin-top: 30px; text-align: right; font-size: 8.5pt; }
        .ttd .garis { margin-top: 40px; border-top: 1px solid #333; width: 180px; display: inline-block; }
    </style>
</head>
<body>

<div class="header">
    <h2>LAPORAN BIAYA PRODUKSI MENU</h2>
    <p>Program Makan Bergizi Gratis (MBG) — {{ $bulanLabel }}</p>
    <p>Unit SPPG: {{ config('app.unit_sppg', 'SPPG') }}</p>
</div>

@php $totalMenu = $menus->count(); @endphp

<table class="main">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Menu</th>
            <th style="text-align:right">Porsi</th>
            <th style="text-align:right">Total Bahan (Rp)</th>
            <th style="text-align:right">Cost/Porsi (Rp)</th>
            <th style="text-align:right">Anggaran (Rp)</th>
            <th style="text-align:right">Selisih (Rp)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($menus as $i => $menu)
        @php
            $b      = $menuCalc[$menu->id]['biaya'];
            $status = $menuCalc[$menu->id]['status'];
            $lbl    = match($status) {
                'over'    => 'Over',
                'warning' => 'Mendekati',
                'aman'    => 'Aman',
                default   => '-',
            };
        @endphp
        <tr>
            <td style="text-align:center">{{ $i + 1 }}</td>
            <td style="text-align:center">{{ $menu->tanggal->format('d/m/Y') }}</td>
            <td>{{ $menu->nama_menu ?? '-' }}</td>
            <td style="text-align:right">{{ number_format($menu->jumlah_porsi ?? 1) }}</td>
            <td style="text-align:right">{{ number_format($b['total_seluruh'], 0, ',', '.') }}</td>
            <td style="text-align:right;font-weight:bold;color:#0f4c81">{{ number_format($b['cost_per_porsi'], 0, ',', '.') }}</td>
            <td style="text-align:right">{{ number_format($b['anggaran'], 0, ',', '.') }}</td>
            <td style="text-align:right;color:{{ $b['selisih'] >= 0 ? '#0f4c81' : '#dc3545' }}">
                {{ $b['selisih'] >= 0 ? '+' : '-' }}{{ number_format(abs($b['selisih']), 0, ',', '.') }}
            </td>
            <td style="text-align:center"><span class="badge-{{ $status === 'belum_ada_data' ? 'aman' : $status }}">{{ $lbl }}</span></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#aaa;padding:12px">Tidak ada data</td></tr>
        @endforelse
    </tbody>
    @if($totalMenu)
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right">Total</td>
            <td style="text-align:right">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="ttd">
    <p>{{ config('app.unit_sppg', 'SPPG') }}, {{ now()->translatedFormat('d F Y') }}</p>
    <p>{{ \App\Models\User::roleLabel($user->role) }},</p>
    <div class="garis"></div>
    <p style="margin-top:3px">{{ $user->name }}</p>
</div>

<div class="footer">
    Dicetak oleh sistem Dashboard SPPG — {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>