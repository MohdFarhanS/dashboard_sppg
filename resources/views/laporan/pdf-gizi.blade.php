<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Gizi {{ $bulanLabel }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; margin: 0; padding: 0; }
        body { padding: 15px; }

        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #0f4c81; padding-bottom: 10px; }
        .header h2 { font-size: 14pt; color: #0f4c81; font-weight: bold; }
        .header p  { font-size: 9pt; color: #555; margin-top: 3px; }

        .meta { margin-bottom: 12px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 6px; font-size: 8.5pt; }
        .meta td:first-child { width: 150px; color: #666; }

        .stat-row { display: table; width: 100%; margin-bottom: 14px; }
        .stat-box {
            display: table-cell;
            width: 25%;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .stat-box + .stat-box { margin-left: 6px; }
        .stat-box .val { font-size: 13pt; font-weight: bold; color: #0f4c81; }
        .stat-box .lbl { font-size: 7.5pt; color: #888; }

        table.main {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.main th {
            background: #0f4c81;
            color: #fff;
            padding: 5px 6px;
            text-align: center;
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
        .badge-cukup  { color: #0f4c81; background: #daeeff; padding: 1px 5px; border-radius: 3px; }
        .badge-kurang { color: #842029; background: #f8d7da; padding: 1px 5px; border-radius: 3px; }
        .badge-lebih  { color: #664d03; background: #fff3cd; padding: 1px 5px; border-radius: 3px; }

        .footer { margin-top: 20px; font-size: 8pt; color: #aaa; text-align: right; }
        .ttd { margin-top: 30px; text-align: right; font-size: 8.5pt; }
        .ttd .garis { margin-top: 40px; border-top: 1px solid #333; width: 180px; display: inline-block; }
    </style>
</head>
<body>

<div class="header">
    <h2>LAPORAN PEMENUHAN GIZI MAKAN SIANG</h2>
    <p>Program Makan Bergizi Gratis (MBG) — {{ $bulanLabel }}</p>
    <p>Unit SPPG: {{ config('app.unit_sppg', 'SPPG') }}</p>
</div>

@php
    $akgRataRef = \App\Constants\AKG::MAKAN_SIANG; // untuk rata-rata lintas kelompok
    $totalMenu  = $menus->count();
    $pctEnergi  = $akgRataRef['energi'] > 0
        ? round($rataGizi['energi'] / $akgRataRef['energi'] * 100) : 0;
@endphp

<table class="main">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Kelompok</th>
            <th>Nama Menu</th>
            <th>Energi (kkal)</th>
            <th>% AKG</th>
            <th>Protein (g)</th>
            <th>Lemak (g)</th>
            <th>Karbo (g)</th>
            <th>Serat (g)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($menus as $i => $menu)
        @php
            $g       = $menuCalc[$menu->id]['gizi'];
            $ks      = $menu->kelompok_sasaran ?? 'SD_4_6';
            $ksLabel = \App\Constants\AKG::KELOMPOK[$ks]['label'] ?? $ks;
            $akgRef  = array_merge(\App\Constants\AKG::MAKAN_SIANG, $menu->akgTarget('siang'));
            $pct     = $akgRef['energi'] > 0 ? round($g['energi'] / $akgRef['energi'] * 100) : 0;
            $cls     = $pct < 70 ? 'kurang' : ($pct > 130 ? 'lebih' : 'cukup');
            $lbl     = $cls === 'kurang' ? 'Kurang' : ($cls === 'lebih' ? 'Lebih' : 'Cukup');
        @endphp
        <tr>
            <td style="text-align:center">{{ $i + 1 }}</td>
            <td style="text-align:center">{{ $menu->tanggal->format('d/m/Y') }}</td>
            <td style="font-size:7.5pt">{{ $ksLabel }}</td>
            <td>{{ $menu->nama_menu ?? '-' }}</td>
            <td style="text-align:right">{{ number_format($g['energi'], 1) }}</td>
            <td style="text-align:center">{{ $pct }}%</td>
            <td style="text-align:right">{{ number_format($g['protein'], 1) }}</td>
            <td style="text-align:right">{{ number_format($g['lemak'], 1) }}</td>
            <td style="text-align:right">{{ number_format($g['karbohidrat'], 1) }}</td>
            <td style="text-align:right">{{ number_format($g['serat'], 1) }}</td>
            <td style="text-align:center"><span class="badge-{{ $cls }}">{{ $lbl }}</span></td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center;color:#aaa;padding:12px">Tidak ada data</td></tr>
        @endforelse
    </tbody>
    @if($totalMenu)
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right">Rata-rata per Porsi</td>
            <td style="text-align:right">{{ number_format($rataGizi['energi'], 1) }}</td>
            <td style="text-align:center">{{ $pctEnergi }}%</td>
            <td style="text-align:right">{{ number_format($rataGizi['protein'], 1) }}</td>
            <td style="text-align:right">{{ number_format($rataGizi['lemak'], 1) }}</td>
            <td style="text-align:right">{{ number_format($rataGizi['karbohidrat'], 1) }}</td>
            <td style="text-align:right">{{ number_format($rataGizi['serat'], 1) }}</td>
            <td></td>
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