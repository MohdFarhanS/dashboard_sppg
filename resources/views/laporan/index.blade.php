@extends('layouts.app')
@section('title', 'Laporan')

@push('styles')
<style>
    .stat-laporan {
        border-radius: 12px;
        padding: 1.1rem 1.3rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        background: #fff;
    }
    .stat-laporan .icon {
        width: 46px; height: 46px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .tab-jenis .btn { border-radius: 8px; font-size: .85rem; font-weight: 600; }
    .tab-jenis .btn.active {
        background: var(--primary); color: #fff; border-color: var(--primary);
    }
</style>
@endpush

@section('content')
@php
    $isAhliGiziLap = auth()->user()->isAhliGizi();
    $isAkuntanLap  = auth()->user()->isAkuntan();
@endphp
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="fas fa-chart-bar me-2"></i>Laporan
            </h4>
            <small class="text-muted">Rekap data menu final per bulan</small>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end" id="filterForm">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Bulan</label>
                    <input type="month" name="bulan" class="form-control form-control-sm"
                           value="{{ $bulan }}" onchange="this.form.submit()">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis Laporan</label>
                    <div class="tab-jenis d-flex gap-1">
                        @if(!$isAkuntanLap)
                        <a href="{{ request()->fullUrlWithQuery(['jenis' => 'gizi']) }}"
                           class="btn btn-sm btn-outline-primary {{ $jenis === 'gizi' ? 'active' : '' }}">
                            <i class="fas fa-heart-pulse me-1"></i>Gizi
                        </a>
                        @endif
                        @if(!$isAhliGiziLap)
                        <a href="{{ request()->fullUrlWithQuery(['jenis' => 'biaya']) }}"
                           class="btn btn-sm btn-outline-primary {{ $jenis === 'biaya' ? 'active' : '' }}">
                            <i class="fas fa-coins me-1"></i>Biaya
                        </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 d-flex gap-2 flex-wrap">
                    <a href="{{ route('laporan.export-excel', ['bulan' => $bulan, 'jenis' => $jenis]) }}"
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="{{ route('laporan.export-pdf', ['bulan' => $bulan, 'jenis' => $jenis]) }}"
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <button onclick="window.print()" type="button" class="btn btn-secondary btn-sm">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-laporan">
                <div class="icon" style="background:#daeeff">📋</div>
                <div>
                    <div class="text-muted" style="font-size:.72rem">Total Menu Final</div>
                    <div class="fw-bold fs-5">{{ $totalMenu }} menu</div>
                </div>
            </div>
        </div>
        @if(!$isAkuntanLap)
        <div class="col-6 col-md-3">
            <div class="stat-laporan">
                <div class="icon" style="background:#fff3e0">🔥</div>
                <div>
                    <div class="text-muted" style="font-size:.72rem">Rata-rata Energi</div>
                    <div class="fw-bold fs-5" style="color:var(--primary)">
                        {{ number_format($rataGizi['energi'], 0) }} kkal
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(!$isAhliGiziLap)
        <div class="col-6 col-md-3">
            <div class="stat-laporan">
                <div class="icon" style="background:#e3f2fd">💰</div>
                <div>
                    <div class="text-muted" style="font-size:.72rem">Total Biaya Bulan Ini</div>
                    <div class="fw-bold" style="font-size:1rem">
                        Rp {{ number_format($totalBiaya, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-laporan">
                <div class="icon" style="background:#f3e5f5">📊</div>
                <div>
                    <div class="text-muted" style="font-size:.72rem">Rata-rata Cost/Porsi</div>
                    <div class="fw-bold" style="font-size:1rem">
                        Rp {{ number_format($rataCost, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 py-2 d-flex justify-content-between"
             style="background:var(--primary-pale)">
            <span class="fw-semibold" style="color:var(--primary)">
                <i class="fas fa-table me-2"></i>
                @if($jenis === 'biaya') Laporan Biaya Produksi
                @else Laporan Pemenuhan Gizi
                @endif
                — {{ \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y') }}
            </span>
            <span class="text-muted small">{{ $totalMenu }} menu final</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if($jenis === 'biaya')
                {{-- ═══ TABEL BIAYA ═══ --}}
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">No</th>
                            <th>Tanggal</th>
                            <th>Kelompok</th>
                            <th>Nama Menu</th>
                            <th class="text-end">Porsi</th>
                            <th class="text-end">Total Bahan</th>
                            <th class="text-end">Cost/Porsi</th>
                            <th class="text-end">Anggaran</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $i => $menu)
                        @php
                            $b      = $menuCalc[$menu->id]['biaya'];
                            $status = $menuCalc[$menu->id]['status'];
                            $ks     = $menu->kelompok_sasaran ?? 'SD_4_6';
                            $ksLabel = \App\Constants\AKG::KELOMPOK[$ks]['label'] ?? $ks;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td>{{ $menu->tanggal->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.7rem">
                                    {{ Str::limit($ksLabel, 22) }}
                                </span>
                            </td>
                            <td>{{ $menu->nama_menu ?? '-' }}</td>
                            <td class="text-end">{{ number_format($menu->jumlah_porsi ?? 1) }}</td>
                            <td class="text-end">Rp {{ number_format($b['total_seluruh'], 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold" style="color:var(--primary)">
                                Rp {{ number_format($b['cost_per_porsi'], 0, ',', '.') }}
                            </td>
                            <td class="text-end">Rp {{ number_format($b['anggaran'], 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if($b['selisih'] >= 0)
                                    <span style="color:#0f4c81" class="fw-semibold">+Rp {{ number_format($b['selisih'], 0, ',', '.') }}</span>
                                @else
                                    <span class="text-danger fw-semibold">-Rp {{ number_format(abs($b['selisih']), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($status === 'over')
                                    <span class="badge bg-danger">Over</span>
                                @elseif($status === 'warning')
                                    <span class="badge bg-warning text-dark">Mendekati</span>
                                @elseif($status === 'aman')
                                    <span class="badge bg-primary">Aman</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-25"></i>
                            Tidak ada menu final di bulan ini.
                        </td></tr>
                        @endforelse
                    </tbody>
                    @if($menus->count())
                    <tfoot style="background:var(--primary-pale)">
                        <tr class="fw-semibold">
                            <td class="ps-3" colspan="6">Total</td>
                            <td class="text-end">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($rataCost, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                @else
                {{-- ═══ TABEL GIZI ═══ --}}
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">No</th>
                            <th>Tanggal</th>
                            <th>Kelompok</th>
                            <th>Nama Menu</th>
                            <th class="text-end">Energi (kkal)</th>
                            <th class="text-end">% AKG</th>
                            <th class="text-end">Protein (g)</th>
                            <th class="text-end">Lemak (g)</th>
                            <th class="text-end">Karbo (g)</th>
                            <th class="text-center">Status Gizi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $i => $menu)
                        @php
                            $g      = $menuCalc[$menu->id]['gizi'];
                            $ks     = $menu->kelompok_sasaran ?? 'SD_4_6';
                            $ksLabel = \App\Constants\AKG::KELOMPOK[$ks]['label'] ?? $ks;
                            $akgRef = array_merge(\App\Constants\AKG::MAKAN_SIANG, $menu->akgTarget('siang'));
                            $pct    = $akgRef['energi'] > 0
                                ? round($g['energi'] / $akgRef['energi'] * 100) : 0;
                            $cls    = $pct < 70 ? 'kurang' : ($pct > 130 ? 'lebih' : 'cukup');
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                            <td>{{ $menu->tanggal->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.7rem">
                                    {{ Str::limit($ksLabel, 22) }}
                                </span>
                            </td>
                            <td>{{ $menu->nama_menu ?? '-' }}</td>
                            <td class="text-end fw-semibold" style="color:var(--primary)">
                                {{ number_format($g['energi'], 1) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeColor = $cls === 'kurang' ? 'bg-danger' : ($cls === 'lebih' ? 'bg-warning text-dark' : 'bg-primary');
                                @endphp
                                <span class="badge {{ $badgeColor }}">{{ $pct }}%</span>
                            </td>
                            <td class="text-end">{{ number_format($g['protein'], 1) }}</td>
                            <td class="text-end">{{ number_format($g['lemak'], 1) }}</td>
                            <td class="text-end">{{ number_format($g['karbohidrat'], 1) }}</td>
                            <td class="text-center">
                                @if($cls === 'kurang')
                                    <span class="badge bg-danger">Kurang</span>
                                @elseif($cls === 'lebih')
                                    <span class="badge bg-warning text-dark">Lebih</span>
                                @else
                                    <span class="badge bg-primary">Cukup</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block opacity-25"></i>
                            Tidak ada menu final di bulan ini.
                        </td></tr>
                        @endforelse
                    </tbody>
                    @if($menus->count())
                    <tfoot style="background:var(--primary-pale)">
                        <tr class="fw-semibold">
                            <td class="ps-3" colspan="5">Rata-rata</td>
                            <td class="text-end" style="color:var(--primary)">
                                {{ number_format($rataGizi['energi'], 1) }} kkal
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection