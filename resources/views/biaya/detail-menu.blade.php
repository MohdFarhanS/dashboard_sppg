@extends('layouts.app')
@section('title', 'Detail Biaya Menu')

@section('content')
<div class="container py-4" style="max-width:720px">
    <div class="d-flex align-items-center mb-4 gap-2">
        <a href="{{ route('biaya.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h5 class="fw-bold mb-0" style="color:#0f4c81">Detail Biaya: {{ $menu->nama_menu }}</h5>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Total Bahan</div>
                <div class="fw-bold fs-5">Rp {{ number_format($biaya['total_seluruh'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Jumlah Porsi</div>
                <div class="fw-bold fs-5">{{ number_format($biaya['jumlah_porsi']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Cost/Porsi</div>
                <div class="fw-bold fs-5 {{ $biaya['selisih'] < 0 ? 'text-danger' : 'text-primary' }}">
                    Rp {{ number_format($biaya['cost_per_porsi'], 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Anggaran</div>
                <div class="fw-bold fs-5">Rp {{ number_format($biaya['anggaran'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @php
        $statusAnggaran = $menu->statusAnggaran();
        $over = $biaya['selisih'] < 0;
    @endphp
    <div class="alert {{ $statusAnggaran === 'belum_ada_data' ? 'alert-secondary' : ($over ? 'alert-danger' : 'alert-success') }} mb-4">
        <i class="fa {{ $statusAnggaran === 'belum_ada_data' ? 'fa-circle-info' : ($over ? 'fa-triangle-exclamation' : 'fa-circle-check') }} me-2"></i>
        @if($statusAnggaran === 'belum_ada_data')
            Anggaran atau biaya belum diatur untuk menu ini.
        @elseif($over)
            <strong>Over budget</strong> — melebihi anggaran sebesar
            <strong>Rp {{ number_format(abs($biaya['selisih']), 0, ',', '.') }}</strong>
            ({{ $biaya['persen_anggaran'] }}% dari anggaran).
        @else
            <strong>Aman</strong> — sisa anggaran
            <strong>Rp {{ number_format($biaya['selisih'], 0, ',', '.') }}</strong>
            ({{ $biaya['persen_anggaran'] }}% terpakai).
        @endif
    </div>

    {{-- Foto Menu --}}
    @if($menu->foto_menu)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-0 fw-semibold" style="background:#daeeff;color:#0f4c81">
            <i class="fas fa-image me-2"></i>Foto Menu
        </div>
        <div class="card-body text-center py-3">
            <img src="{{ Storage::url($menu->foto_menu) }}"
                 class="img-fluid rounded shadow-sm"
                 style="max-height:300px;object-fit:contain"
                 alt="Foto menu {{ $menu->nama_menu }}">
        </div>
    </div>
    @endif

    {{-- Tabel Breakdown --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold border-0">Breakdown Biaya per Bahan</div>
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Bahan</th>
                        <th class="text-end">Gram</th>
                        <th class="text-end">Harga/100g</th>
                        <th class="text-end">Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($biaya['detail'] as $d)
                    <tr>
                        <td>{{ $d['nama'] }}</td>
                        <td class="text-end">{{ $d['gram'] }}g</td>
                        <td class="text-end">
                            @if($d['harga_per_100g'] > 0)
                                Rp {{ number_format($d['harga_per_100g'], 0, ',', '.') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($d['biaya'] > 0)
                                Rp {{ number_format($d['biaya'], 0, ',', '.') }}
                            @else
                                <span class="badge bg-warning text-dark">Belum ada harga</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">Total Seluruh Bahan</td>
                        <td class="text-end">Rp {{ number_format($biaya['total_seluruh'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">Cost per Porsi (÷{{ $biaya['jumlah_porsi'] }} porsi)</td>
                        <td class="text-end" style="color:#0f4c81">
                            Rp {{ number_format($biaya['cost_per_porsi'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection