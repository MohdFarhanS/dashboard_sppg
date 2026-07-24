@extends('layouts.app')

@section('title', 'Dashboard — SPPG')
@section('page-title', 'Dashboard')

@push('styles')
<style>
.gizi-card { border-left: 4px solid #0f4c81; transition: transform .2s; }
.gizi-card:hover { transform: translateY(-2px); }
.progress-gizi { height: 10px; border-radius: 6px; }
.progress-bar-kurang { background: #dc3545; }
.progress-bar-cukup  { background: #0071e4; }
.progress-bar-lebih  { background: #ffc107; }
.badge-status-kurang { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
.badge-status-cukup  { background: #daeeff; color: #0f4c81; border: 1px solid #0071e4; }
.badge-status-lebih  { background: #f8d7da; color: #842029; border: 1px solid #dc3545; }
.chart-container { position: relative; height: 280px; }
</style>
@endpush

@section('content')

@php
    $isAhliGizi = auth()->user()->isAhliGizi();
    $isAkuntan  = auth()->user()->isAkuntan();
    $isKetua    = auth()->user()->isKetuaSppg();
    $bulanLabel = \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y');
@endphp

{{-- Header: Greeting + Filter Bulan --}}
<div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-2">
    <div>
        <h4 class="fw-700 mb-1" style="color:#0d2545;">
            Selamat datang, {{ Auth::user()->nama_lengkap ?? Auth::user()->name }}
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Rekap bulanan &mdash; {{ $bulanLabel }}
        </p>
    </div>
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="text-muted mb-0" style="font-size:.82rem; white-space:nowrap;">Filter Bulan:</label>
        <input type="month" name="bulan" value="{{ $bulan }}"
               class="form-control form-control-sm" style="width:160px;"
               onchange="this.form.submit()">
    </form>
</div>

{{-- STAT CARDS --}}
<div class="row g-3 mb-4">

    {{-- Total Menu Final --}}
    <div class="col-6 {{ $isAhliGizi ? '' : ($isAkuntan ? 'col-lg-4' : 'col-lg-3') }}">
        <div class="stat-card">
            <div class="stat-icon" style="background:#daeeff;">🍽️</div>
            <div>
                <div class="stat-label">Menu Final</div>
                <div class="stat-value">{{ $stats['total_menu_final'] }}</div>
                <div class="stat-sub">dari {{ $stats['total_menu_semua'] }} menu bulan ini</div>
            </div>
        </div>
    </div>

    {{-- Rata-rata Kalori (disembunyikan dari akuntan) --}}
    @if(!$isAkuntan)
    <div class="col-6 {{ $isAhliGizi ? '' : 'col-lg-3' }}">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3e0;">🔥</div>
            <div>
                <div class="stat-label">Rata-rata Energi</div>
                <div class="stat-value" style="color:{{ $stats['rata_kalori'] > $stats['target_kalori'] ? '#c62828' : '#0f4c81' }};">
                    {{ number_format($stats['rata_kalori']) }}
                </div>
                <div class="stat-sub">Target: {{ number_format($stats['target_kalori']) }} kkal</div>
            </div>
        </div>
    </div>
    @endif

    @if(!$isAhliGizi)
    {{-- Total Biaya Bulanan --}}
    <div class="col-6 {{ $isAkuntan ? 'col-lg-4' : 'col-lg-3' }}">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e3f2fd;">💰</div>
            <div>
                <div class="stat-label">Total Biaya</div>
                <div class="stat-value" style="font-size:1.1rem;">
                    Rp{{ number_format($stats['total_biaya'], 0, ',', '.') }}
                </div>
                <div class="stat-sub">
                    @if($stats['budget_total'] > 0)
                        Budget: Rp{{ number_format($stats['budget_total'], 0, ',', '.') }}
                    @else
                        Budget belum diset
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Status Budget --}}
    <div class="col-6 {{ $isAkuntan ? 'col-lg-4' : 'col-lg-3' }}">
        @php
            $sbg = $stats['status_budget'];
            $sbgIcon = $sbg === 'aman' ? '✅' : ($sbg === 'warning' ? '⚠️' : ($sbg === 'over' ? '🚨' : '—'));
            $sbgBg   = $sbg === 'aman' ? '#daeeff' : ($sbg === 'warning' ? '#fff8e1' : ($sbg === 'over' ? '#fce4e4' : '#f0f0f0'));
        @endphp
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $sbgBg }};">{{ $sbgIcon }}</div>
            <div>
                <div class="stat-label">Status Budget</div>
                <div class="stat-value" style="font-size:1.05rem;">
                    @if($sbg === 'aman') Aman
                    @elseif($sbg === 'warning') Mendekati Batas
                    @elseif($sbg === 'over') Melebihi
                    @else Belum Ada Data
                    @endif
                </div>
                <div class="stat-sub">
                    {{ $stats['total_alert'] }} peringatan bulan ini
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- ROW 2: Pemenuhan Gizi + Distribusi Biaya --}}
@if($jumlahHari > 0)
<div class="row g-3 mb-4">

    {{-- Rata-rata Pemenuhan Gizi (semua kecuali akuntan) --}}
    @if(!$isAkuntan)
    <div class="{{ $isAhliGizi ? 'col-12' : 'col-lg-5' }}">
        <div class="card card-mbg h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-heart-pulse me-2" style="color:#0f4c81;"></i>Rata-rata Gizi vs. AKG</span>
                <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.72rem;">{{ $jumlahHari }} hari</span>
            </div>
            <div class="card-body p-3">
                @php
                    $giziItems = [
                        ['label'=>'Energi',      'pct'=>$persenAkg['energi']      ?? 0, 'color'=>'#f57c00', 'icon'=>'🔥', 'val'=>number_format($rataGizi['energi']??0,1).' kkal'],
                        ['label'=>'Protein',     'pct'=>$persenAkg['protein']     ?? 0, 'color'=>'#0f4c81', 'icon'=>'🥩', 'val'=>($persenAkg['protein']??0).'%'],
                        ['label'=>'Karbohidrat', 'pct'=>$persenAkg['karbohidrat'] ?? 0, 'color'=>'#2196f3', 'icon'=>'🍚', 'val'=>($persenAkg['karbohidrat']??0).'%'],
                        ['label'=>'Lemak',       'pct'=>$persenAkg['lemak']       ?? 0, 'color'=>'#9c27b0', 'icon'=>'🧈', 'val'=>($persenAkg['lemak']??0).'%'],
                    ];
                @endphp
                @foreach($giziItems as $g)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.8rem;font-weight:600;color:#0d2545;">{{ $g['icon'] }} {{ $g['label'] }}</span>
                        <span style="font-size:.78rem;color:#6b8ba4;">{{ $g['val'] }}</span>
                    </div>
                    <div class="progress progress-gizi">
                        <div class="progress-bar" role="progressbar"
                             style="width:{{ min($g['pct'],100) }}%;background:{{ $g['color'] }};border-radius:6px;"
                             aria-valuenow="{{ $g['pct'] }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span style="font-size:.68rem;color:#adb5bd;">0%</span>
                        <span style="font-size:.7rem;font-weight:600;color:{{ $g['color'] }};">{{ $g['pct'] }}%</span>
                        <span style="font-size:.68rem;color:#adb5bd;">100%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Distribusi Biaya (semua kecuali ahli_gizi) --}}
    @if(!$isAhliGizi)
    <div class="{{ $isAkuntan ? 'col-12' : 'col-lg-7' }}">
        <div class="card card-mbg h-100">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2" style="color:#0f4c81;"></i>Distribusi Biaya per Kategori Bahan
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px;">
                @if(!empty($stats['distribusi_biaya']))
                    <canvas id="chartBiaya" style="max-height:220px;"></canvas>
                @else
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-pie fa-2x mb-2 d-block opacity-25"></i>
                        <div style="font-size:.85rem;">Belum ada data harga bahan bulan ini.</div>
                        <small>Pastikan harga bahan sudah diinput di <strong>Harga Bahan</strong></small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
@endif

{{-- ROW 3: Budget Alert + Tabel Menu Bulanan --}}
<div class="row g-3 mb-4">

    {{-- Budget Alert (semua kecuali ahli_gizi) --}}
    @if(!$isAhliGizi)
    <div class="col-lg-4">
        <div class="card card-mbg">
            <div class="card-header d-flex justify-content-between">
                <span><i class="fas fa-bell me-2" style="color:#f57c00;"></i>Budget Alert</span>
                @if($stats['total_alert'] > 0)
                    <span class="badge" style="background:#fce4e4;color:#c62828;">{{ $stats['total_alert'] }} Peringatan</span>
                @else
                    <span class="badge" style="background:#daeeff;color:#0f4c81;">Aman</span>
                @endif
            </div>
            <div class="card-body p-0">
                @forelse($stats['alert_list'] as $alert)
                <div class="d-flex gap-3 p-3 border-bottom" style="border-color:#e4eef8 !important;">
                    <div style="margin-top:.15rem;">
                        <span style="font-size:1rem;">{{ $alert['type'] === 'danger' ? '🚨' : '⚠️' }}</span>
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-size:.8rem;color:#0d2545;font-weight:500;">{{ $alert['msg'] }}</div>
                        <div style="font-size:.7rem;color:#adb5bd;margin-top:.2rem;">{{ $alert['time'] }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.85rem;">
                    <i class="fas fa-check-circle text-primary d-block mb-2 fs-4"></i>
                    Semua menu dalam batas anggaran
                </div>
                @endforelse
                <div class="p-3">
                    <a href="{{ route('biaya.dashboard') }}" class="btn btn-sm w-100"
                       style="background:#daeeff;color:#0f4c81;border-radius:8px;font-size:.8rem;font-weight:600;">
                        Lihat Dashboard Biaya →
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Tabel Menu Bulan Ini --}}
    <div class="{{ !$isAhliGizi ? 'col-lg-8' : 'col-12' }}">
        <div class="card card-mbg">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-2" style="color:#0f4c81;"></i>Menu Bulan Ini</span>
                <span class="badge bg-secondary">{{ $menus->count() }} menu</span>
            </div>
            <div class="card-body p-0">
                @if($menus->count())
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
                        <thead style="background:#f0f5fc;">
                            <tr>
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;">Tanggal</th>
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;">Nama Menu</th>
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;">Kelompok</th>
                                @if(!$isAkuntan)
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;text-align:right;">Energi</th>
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;text-align:center;">% AKG</th>
                                @endif
                                @if(!$isAhliGizi)
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;text-align:right;">Biaya/Porsi</th>
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;text-align:center;">Budget</th>
                                @endif
                                @if(!$isAkuntan)
                                <th style="padding:.65rem 1rem;color:#6b8ba4;font-weight:600;font-size:.75rem;border:none;text-align:center;width:50px;">Aksi</th>
                                @else
                                <th style="width:20px;border:none;"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($menus as $menu)
                            @php
                                $calc   = $menuCalc[$menu->id];
                                $g      = $calc['gizi'];
                                $b      = $calc['biaya'];
                                $status = $calc['status'];
                                $pctAkg = $calc['pctAkg'];
                                $clsAkg = $calc['clsAkg'];
                            @endphp
                            <tr>
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;color:#6b8ba4;white-space:nowrap;">
                                    {{ $menu->tanggal->translatedFormat('d M Y') }}
                                </td>
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;font-weight:500;color:#0d2545;">
                                    @if(!$isAkuntan)
                                    <a href="{{ route('menu-harian.show', $menu) }}" class="text-decoration-none text-dark">
                                        {{ $menu->nama_menu ?: '(tanpa nama)' }}
                                    </a>
                                    @else
                                    {{ $menu->nama_menu ?: '(tanpa nama)' }}
                                    @endif
                                    <div style="font-size:.7rem;color:#adb5bd;">{{ $menu->status === 'final' ? '🔒 Final' : '✏️ Draft' }}</div>
                                    @if($menu->catatan_anggaran)
                                    <div style="font-size:.7rem;color:#6b8ba4;">
                                        <i class="fas fa-school fa-xs me-1"></i>{{ $menu->catatan_anggaran }}
                                    </div>
                                    @endif
                                </td>
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;">
                                    @if($menu->kelompok === 'balita_sd3')
                                        <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.72rem;">
                                            <i class="fas fa-child me-1"></i>Balita s/d Kls 3 SD
                                        </span>
                                    @else
                                        <span class="badge" style="background:#d1f0e0;color:#1a6640;font-size:.72rem;">
                                            <i class="fas fa-user-graduate me-1"></i>Kls 4 SD s/d Ibu Menyusui
                                        </span>
                                    @endif
                                </td>
                                @if(!$isAkuntan)
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;text-align:right;color:#f57c00;font-weight:600;">
                                    {{ number_format($g['energi'], 0) }} kkal
                                </td>
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;text-align:center;">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <div class="progress flex-grow-1" style="height:7px;max-width:60px;">
                                            <div class="progress-bar progress-bar-{{ $clsAkg }}"
                                                 style="width:{{ min($pctAkg,100) }}%"></div>
                                        </div>
                                        <span class="small fw-semibold" style="min-width:34px;">{{ $pctAkg }}%</span>
                                    </div>
                                </td>
                                @endif
                                @if(!$isAhliGizi)
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;text-align:right;color:#2196f3;font-weight:600;">
                                    Rp{{ number_format($b['cost_per_porsi'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;text-align:center;">
                                    @if($status === 'over')
                                        <span class="badge" style="background:#fce4e4;color:#c62828;font-size:.72rem;">🚨 Over</span>
                                    @elseif($status === 'warning')
                                        <span class="badge" style="background:#fff8e1;color:#f57c00;font-size:.72rem;">⚠ Mendekati</span>
                                    @elseif($status === 'aman')
                                        <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.72rem;">✓ Aman</span>
                                    @else
                                        <span class="badge" style="background:#f0f0f0;color:#aaa;font-size:.72rem;">— Draft</span>
                                    @endif
                                </td>
                                @endif
                                @if(!$isAkuntan)
                                <td style="padding:.65rem 1rem;border-color:#e8f1fc;text-align:center;">
                                    <a href="{{ route('menu-harian.show', $menu) }}"
                                       class="btn btn-outline-secondary btn-sm py-0 px-2" title="Lihat detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                                @else
                                <td></td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="fas fa-utensils fa-2x mb-2 d-block opacity-25"></i>
                    <div style="font-size:.85rem;">Belum ada menu di bulan {{ $bulanLabel }}.</div>
                    @if($isAhliGizi)
                    <a href="{{ route('simulasi.index') }}" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus me-1"></i>Input Menu
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Tren Energi Harian --}}
@if(!$isAkuntan && $jumlahHari > 0)
    @if(count($trendData) > 1)
    <div class="card card-mbg mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-2" style="color:#0f4c81;"></i>Tren Energi Harian
            <span class="text-muted fw-normal small ms-2">({{ $bulanLabel }})</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartTrend"></canvas>
            </div>
        </div>
    </div>
    @endif
@endif

{{-- Rata-rata Gizi vs AKG Chart (semua kecuali akuntan) --}}
@if(!$isAkuntan && $jumlahHari > 0)
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card card-mbg h-100">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2" style="color:#0f4c81;"></i>Rata-rata Gizi vs. AKG Makan Siang
                <span class="badge bg-secondary ms-2">{{ $jumlahHari }} hari</span>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartAkg"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-mbg h-100">
            <div class="card-header">
                <i class="fas fa-list-check me-2" style="color:#0f4c81;"></i>Detail Rata-rata
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach(['energi','protein','lemak','karbohidrat','serat','kalsium','besi','vit_c'] as $k)
                    @php
                        $val   = $rataGizi[$k] ?? 0;
                        $pct   = $persenAkg[$k] ?? 0;
                        $acuan = \App\Constants\AKG::MAKAN_SIANG[$k];
                        $cls   = $pct < 70 ? 'kurang' : ($pct > 130 ? 'lebih' : 'cukup');
                        $info  = \App\Constants\AKG::LABEL[$k];
                    @endphp
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold">
                                <i class="fas {{ $info['icon'] }} me-1 opacity-75" style="color:#0071e4;"></i>
                                {{ $info['label'] }}
                            </span>
                            <span class="badge badge-status-{{ $cls }}">{{ $pct }}%</span>
                        </div>
                        <div class="progress progress-gizi mb-1">
                            <div class="progress-bar progress-bar-{{ $cls }}" style="width:{{ min($pct,100) }}%"></div>
                        </div>
                        <small class="text-muted">{{ number_format($val,1) }} / {{ $acuan }} {{ $info['satuan'] }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if(!$isAhliGizi && !empty($stats['distribusi_biaya']))
<script>
const distribusi = @json($stats['distribusi_biaya']);
const WARNA_KATEGORI = {
    'Serealia':'#0071e4','Daging':'#e53935','Ikan':'#1e88e5','Telur':'#fdd835',
    'Sayuran':'#43a047','Buah':'#fb8c00','Kacang':'#8d6e63','Umbi':'#f06292',
    'Susu':'#90caf9','Lemak':'#ffb74d','Bumbu':'#ce93d8','Gula':'#ef9a9a',
    'Minuman':'#80deea','Lainnya':'#b0bec5',
};
const labels = Object.keys(distribusi);
const values = Object.values(distribusi);
const colors = labels.map(l => WARNA_KATEGORI[l] || '#b0bec5');

new Chart(document.getElementById('chartBiaya').getContext('2d'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, hoverOffset: 8 }] },
    options: {
        cutout: '60%',
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true, pointStyle: 'circle',
                    font: { size: 11, family: 'Plus Jakarta Sans' },
                    color: '#0d2545', padding: 12,
                    generateLabels: (chart) => {
                        const d = chart.data;
                        return d.labels.map((label, i) => ({
                            text: `${label}  Rp ${Math.round(d.datasets[0].data[i]).toLocaleString('id-ID')}`,
                            fillStyle: d.datasets[0].backgroundColor[i],
                            strokeStyle: 'transparent', pointStyle: 'circle',
                        }));
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: (ctx) => {
                        const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                        const pct   = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0;
                        return ` Rp ${Math.round(ctx.raw).toLocaleString('id-ID')} (${pct}%)`;
                    }
                }
            }
        }
    }
});
</script>
@endif

@if(!$isAkuntan && $jumlahHari > 0)
<script>
const rataGizi  = @json($rataGizi);
const persenAkg = @json($persenAkg);
const akgSiang  = @json(\App\Constants\AKG::MAKAN_SIANG);
const trendData = @json($trendData);

const GREEN  = '#0071e4';
const YELLOW = '#ffc107';
const RED    = '#dc3545';
const GRAY   = 'rgba(0,113,228,.15)';

function warnaPct(pct) { return pct < 70 ? RED : pct > 130 ? YELLOW : GREEN; }

const labelKeys = ['energi','protein','lemak','karbohidrat','serat','kalsium','besi','vit_c'];
const labelMap  = {
    energi:'Energi', protein:'Protein', lemak:'Lemak',
    karbohidrat:'Karbo', serat:'Serat', kalsium:'Kalsium', besi:'Fe', vit_c:'Vit C'
};

new Chart(document.getElementById('chartAkg'), {
    type: 'bar',
    data: {
        labels: labelKeys.map(k => labelMap[k]),
        datasets: [
            {
                label: 'Rata-rata Aktual',
                data: labelKeys.map(k => rataGizi[k] || 0),
                backgroundColor: labelKeys.map(k => warnaPct(persenAkg[k] || 0)),
                borderRadius: 4,
            },
            {
                label: 'Target AKG Siang',
                data: labelKeys.map(k => akgSiang[k] || 0),
                backgroundColor: GRAY, borderColor: GREEN, borderWidth: 1.5,
                borderRadius: 4, type: 'bar',
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}` } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } },
            x: { grid: { display: false } }
        }
    }
});

@if(count($trendData) > 1)
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: trendData.map(d => d.tanggal),
        datasets: [
            {
                label: 'Energi (kkal)',
                data: trendData.map(d => d.energi),
                borderColor: GREEN, backgroundColor: 'rgba(0,113,228,.1)',
                fill: true, tension: 0.3,
                pointBackgroundColor: GREEN, pointRadius: 4,
            },
            {
                label: 'Target AKG',
                data: trendData.map(() => akgSiang.energi),
                borderColor: RED, borderDash: [6,3], borderWidth: 1.5,
                pointRadius: 0, fill: false,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)} kkal` } }
        },
        scales: {
            y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,.05)' } },
            x: { grid: { display: false } }
        }
    }
});
@endif
</script>
@endif
@endpush
