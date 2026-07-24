@extends('layouts.app')

@section('title', 'Dashboard Biaya Produksi')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">
            <i class="fa fa-chart-line me-2" style="color:#0f4c81"></i>Biaya Produksi
        </h4>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" class="row g-2 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label small text-muted mb-1">Bulan</label>
            <input type="month" name="bulan" value="{{ $bulan }}" class="form-control form-control-sm">
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-filter me-1"></i>Tampilkan
            </button>
        </div>
    </form>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Hari Menu</div>
                    <div class="fs-4 fw-bold" style="color:#0f4c81">{{ $totalHari }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Biaya Bulan Ini</div>
                    <div class="fs-5 fw-bold">Rp {{ number_format($totalBiayaBulan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Rata-rata Cost/Porsi</div>
                    <div class="fs-5 fw-bold">Rp {{ number_format($rataCostPorsi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Over/Under Budget</div>
                    <div>
                        <span class="badge bg-danger-subtle text-danger">{{ $overBudget }} over</span>
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $underBudget }} under</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Budget Alert Summary --}}
    @if(isset($alertSummary) && ($alertSummary['over'] > 0 || $alertSummary['warning'] > 0))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-warning border-3">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
                    <i class="fas fa-bell text-warning"></i>
                    <h6 class="fw-bold mb-0">Ringkasan Budget Alert — {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-danger-subtle rounded-3">
                                <i class="fas fa-exclamation-triangle text-danger fs-3 me-3"></i>
                                <div>
                                    <div class="fw-bold fs-4 text-danger">{{ $alertSummary['over'] }}</div>
                                    <div class="text-muted small">Menu Melebihi Anggaran</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-warning-subtle rounded-3">
                                <i class="fas fa-exclamation-circle text-warning fs-3 me-3"></i>
                                <div>
                                    <div class="fw-bold fs-4 text-warning">{{ $alertSummary['warning'] }}</div>
                                    <div class="text-muted small">Mendekati Batas (≥85%)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center p-3 bg-primary-subtle rounded-3">
                                <i class="fas fa-check-circle text-primary fs-3 me-3"></i>
                                <div>
                                    <div class="fw-bold fs-4 text-primary">{{ $alertSummary['aman'] }}</div>
                                    <div class="text-muted small">Menu Dalam Anggaran</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Trend Chart --}}
    @if($trendBiaya->count())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="card-title fw-semibold mb-3">Tren Biaya vs Anggaran per Porsi</h6>
            <canvas id="chartTrend" height="100"></canvas>
        </div>
    </div>
    @endif

    {{-- Tabel Rekap --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Menu</th>
                            <th class="text-end">Cost/Porsi</th>
                            <th class="text-end">Anggaran</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapBiaya as $r)
                        @php $b = $r['biaya']; @endphp
                        <tr>
                            <td class="text-nowrap">{{ $r['tanggal'] }}</td>
                            <td>{{ $r['menu'] }}</td>
                            <td class="text-end">Rp {{ number_format($b['cost_per_porsi'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($b['anggaran'], 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if($b['selisih'] >= 0)
                                    <span style="color:#0f4c81" class="fw-semibold">+Rp {{ number_format($b['selisih'], 0, ',', '.') }}</span>
                                @else
                                    <span class="text-danger fw-semibold">-Rp {{ number_format(abs($b['selisih']), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($r['status'] === 'over')
                                    <span class="badge bg-danger">Over Budget</span>
                                @elseif($r['status'] === 'belum_ada_data')
                                    <span class="text-muted small">—</span>
                                @else
                                    <span class="badge bg-primary">On Budget</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('biaya.detail-menu', $r['menu_id']) }}"
                                    class="btn btn-sm btn-outline-secondary py-0 px-2">
                                    <i class="fa fa-eye me-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada data menu final untuk bulan ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@if($trendBiaya->count())
<script>
const ctx = document.getElementById('chartTrend').getContext('2d');
const trend = @json($trendBiaya);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trend.map(d => d.tanggal),
        datasets: [
            {
                label: 'Cost per Porsi',
                data: trend.map(d => d.cost_per_porsi),
                borderColor: '#0071e4',
                backgroundColor: 'rgba(0,113,228,0.08)',
                fill: true,
                tension: 0.3,
            },
            {
                label: 'Anggaran',
                data: trend.map(d => d.anggaran),
                borderColor: '#dc3545',
                borderDash: [5,5],
                fill: false,
                tension: 0.1,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: {
                ticks: {
                    callback: val => 'Rp ' + val.toLocaleString('id-ID')
                }
            }
        }
    }
});
</script>
@endif
@endpush