@extends('layouts.app')
@section('title', 'Budget Alert')
@section('page-title', 'Budget Alert')

@push('styles')
<style>
.alert-card {
    border-left: 4px solid transparent;
    transition: transform .15s, box-shadow .15s;
}
.alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,.08);
}
.alert-card.over    { border-left-color: #dc3545; }
.alert-card.warning { border-left-color: #ffc107; }
.alert-card.aman    { border-left-color: #0f4c81; }
.alert-card.belum_ada_data { border-left-color: #adb5bd; }

.severity-badge.over    { background:#fce4e4; color:#c62828; }
.severity-badge.warning { background:#fff8e1; color:#f57c00; }
.severity-badge.aman    { background:#daeeff; color:#0f4c81; }
.severity-badge.belum_ada_data { background:#e9ecef; color:#495057; }

.stat-alert {
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    cursor: pointer;
    transition: transform .15s;
    text-decoration: none;
    color: inherit;
}
.stat-alert:hover { transform: translateY(-2px); color: inherit; }

.progress-thin { height: 6px; border-radius: 3px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:#0f4c81">
                <i class="fas fa-bell me-2"></i>Budget Alert
            </h4>
            <small class="text-muted">
                Monitoring menu yang melebihi atau mendekati batas anggaran
            </small>
        </div>
        <div class="d-flex gap-2">
            @if($adaAlertBelumDilihat ?? false)
            <form method="POST" action="{{ route('budget-alert.dismiss') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-check-double me-1"></i>Tandai Sudah Dilihat
                </button>
            </form>
            @endif
            <a href="{{ route('biaya.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i>Dashboard Biaya
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Bulan</label>
                    <input type="month" name="bulan" value="{{ $bulan }}"
                           class="form-control form-control-sm" onchange="this.form.submit()">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tingkat Keparahan</label>
                    <select name="severity" class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <option value=""      {{ $severity === ''      ? 'selected' : '' }}>
                            Over + Mendekati Batas
                        </option>
                        <option value="semua" {{ $severity === 'semua' ? 'selected' : '' }}>
                            Semua (termasuk Aman)
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('budget-alert.index') }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <a href="{{ request()->fullUrlWithQuery(['severity' => 'over']) }}"
               class="stat-alert d-flex">
                <div style="width:46px;height:46px;border-radius:10px;background:#fce4e4;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.3rem;flex-shrink:0;">🚨</div>
                <div>
                    <div style="font-size:.72rem;color:#6b8ba4;">Over Budget</div>
                    <div class="fw-bold fs-4" style="color:#c62828;line-height:1.1;">
                        {{ $countOver }}
                    </div>
                    <div style="font-size:.7rem;color:#adb5bd;">menu bulan ini</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4">
            <a href="{{ request()->fullUrlWithQuery(['severity' => 'warning']) }}"
               class="stat-alert d-flex">
                <div style="width:46px;height:46px;border-radius:10px;background:#fff8e1;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.3rem;flex-shrink:0;">⚠️</div>
                <div>
                    <div style="font-size:.72rem;color:#6b8ba4;">Mendekati Batas</div>
                    <div class="fw-bold fs-4" style="color:#f57c00;line-height:1.1;">
                        {{ $countWarning }}
                    </div>
                    <div style="font-size:.7rem;color:#adb5bd;">menu bulan ini</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4">
            <a href="{{ request()->fullUrlWithQuery(['severity' => 'aman']) }}"
               class="stat-alert d-flex">
                <div style="width:46px;height:46px;border-radius:10px;background:#daeeff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.3rem;flex-shrink:0;">✅</div>
                <div>
                    <div style="font-size:.72rem;color:#6b8ba4;">Dalam Anggaran</div>
                    <div class="fw-bold fs-4" style="color:#0f4c81;line-height:1.1;">
                        {{ $countAman }}
                    </div>
                    <div style="font-size:.7rem;color:#adb5bd;">menu bulan ini</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Daftar Alert --}}
    @if(count($alerts) === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-check-circle fa-3x mb-3 d-block" style="color:#0f4c81;opacity:.3;"></i>
            @if($severity === 'semua')
                <div class="fw-semibold">Belum ada menu final bulan ini</div>
                <small>Data akan muncul setelah menu difinalisasi.</small>
            @else
                <div class="fw-semibold">Tidak ada peringatan bulan ini</div>
                <small>Semua menu dalam batas anggaran 🎉</small>
            @endif
        </div>
    </div>
    @else
    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted fw-semibold">
            Menampilkan {{ count($alerts) }} menu
            @if($severity === 'semua') — filter: <strong>Semua (termasuk Aman)</strong>
            @endif
        </small>
    </div>

    <div class="row g-3">
        @foreach($alerts as $a)
        @php $menu = $a['menu']; @endphp
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm alert-card {{ $a['status'] }}">
                <div class="card-body">

                    {{-- Row 1: Judul + Badge --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold" style="color:#0d2545;">
                                {{ $menu->nama_menu ?? '(tanpa nama)' }}
                            </div>
                            <small class="text-muted">
                                {{ $menu->tanggal->translatedFormat('d F Y') }}
                            </small>
                        </div>
                        <span class="badge severity-badge {{ $a['status'] }} px-2 py-1"
                              style="font-size:.75rem;">
                            @if($a['status'] === 'over') 🚨 Over Budget
                            @elseif($a['status'] === 'warning') ⚠️ Mendekati
                            @elseif($a['status'] === 'belum_ada_data') ➖ Belum Diatur
                            @else ✅ Aman
                            @endif
                        </span>
                    </div>

                    {{-- Row 2: Angka biaya --}}
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-4">
                            <div style="font-size:.68rem;color:#7a9280;">Cost/Porsi</div>
                            <div class="fw-bold"
                                 style="color:{{ $a['status'] === 'over' ? '#c62828' : '#0f4c81' }};">
                                Rp {{ number_format($a['cost_porsi'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:.68rem;color:#7a9280;">Anggaran</div>
                            <div class="fw-bold">
                                Rp {{ number_format($a['anggaran'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="font-size:.68rem;color:#7a9280;">Selisih</div>
                            <div class="fw-bold"
                                 style="color:{{ $a['selisih'] >= 0 ? '#0f4c81' : '#c62828' }};">
                                {{ $a['selisih'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($a['selisih']), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Row 3: Progress bar --}}
                    <div class="mb-1 d-flex justify-content-between">
                        <small class="text-muted">Penyerapan anggaran</small>
                        <small class="fw-semibold"
                               style="color:{{ $a['status'] === 'over' ? '#c62828' : ($a['status'] === 'warning' ? '#f57c00' : '#0f4c81') }};">
                            {{ $a['persen'] }}%
                        </small>
                    </div>
                    <div class="progress progress-thin mb-3">
                        <div class="progress-bar"
                             style="width:{{ min($a['persen'], 100) }}%;
                                    background:{{ $a['status'] === 'over' ? '#dc3545' : ($a['status'] === 'warning' ? '#ffc107' : '#0071e4') }};">
                        </div>
                    </div>

                    {{-- Row 4: Aksi --}}
                    <div class="d-flex gap-2">
                        @if(!auth()->user()->isAkuntan())
                        <a href="{{ route('menu-harian.show', $menu) }}"
                           class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="fas fa-eye me-1"></i>Detail Menu
                        </a>
                        @endif
                        <a href="{{ route('biaya.detail-menu', $menu->id) }}"
                           class="btn btn-sm flex-fill"
                           style="background:var(--primary-pale);color:var(--primary);border:1px solid #b5d4f5;">
                            <i class="fas fa-coins me-1"></i>Breakdown Biaya
                        </a>
                    </div>

                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection