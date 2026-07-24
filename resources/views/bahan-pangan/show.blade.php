@extends('layouts.app')

@section('title', $bahanPangan->nama_bahan)

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="{{ route('bahan-pangan.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">{{ $bahanPangan->nama_bahan }}</h4>
            <small class="text-muted">{{ $bahanPangan->kode }}</small>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">
        {{-- Info Utama --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="mb-3 p-3 rounded-3" style="background: var(--primary-pale);">
                        <code class="fs-6 fw-bold" style="color: var(--primary);">{{ $bahanPangan->kode }}</code>
                        @if($bahanPangan->kode_lama)
                        <br><small class="text-muted">Kode lama: {{ $bahanPangan->kode_lama }}</small>
                        @endif
                    </div>

                    <h5 class="fw-bold mb-1">{{ $bahanPangan->nama_bahan }}</h5>

                    <div class="mt-3 d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Kategori</span>
                            <span class="badge bg-primary">{{ $bahanPangan->kategori }}</span>
                        </div>
                        @if($bahanPangan->sub_kategori)
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Jenis</span>
                            <span class="badge bg-light text-dark border">{{ $bahanPangan->sub_kategori }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Sumber Data</span>
                            <span class="small text-dark">{{ $bahanPangan->sumber ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">BDD</span>
                            <span class="small fw-semibold">{{ $bahanPangan->bdd !== null ? $bahanPangan->bdd . '%' : '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Status</span>
                            @if($bahanPangan->is_active)
                                <span class="badge bg-primary">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </div>
                    </div>

                    {{-- Makronutrien besar --}}
                    <hr>
                    <p class="small text-muted mb-2">Per 100 gram BDD:</p>

                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 rounded-2" style="background:#fff3cd;">
                                <div class="fw-bold fs-5">{{ $bahanPangan->energi !== null ? number_format($bahanPangan->energi, 0) : '—' }}</div>
                                <div class="small text-muted">Energi (Kal)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2" style="background:#daeeff;">
                                <div class="fw-bold fs-5">{{ $bahanPangan->protein !== null ? number_format($bahanPangan->protein, 1) : '—' }}</div>
                                <div class="small text-muted">Protein (g)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2" style="background:#f8d7da;">
                                <div class="fw-bold fs-5">{{ $bahanPangan->lemak !== null ? number_format($bahanPangan->lemak, 1) : '—' }}</div>
                                <div class="small text-muted">Lemak (g)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2" style="background:#cff4fc;">
                                <div class="fw-bold fs-5">{{ $bahanPangan->karbohidrat !== null ? number_format($bahanPangan->karbohidrat, 1) : '—' }}</div>
                                <div class="small text-muted">Karbo (g)</div>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->isKetuaSppg())
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('bahan-pangan.edit', $bahanPangan) }}"
                           class="btn btn-warning btn-sm flex-fill">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <form method="POST" action="{{ route('bahan-pangan.destroy', $bahanPangan) }}"
                              onsubmit="return confirm('Hapus bahan pangan ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detail Nutrisi --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-flask me-2" style="color:var(--primary)"></i>
                        Komposisi Zat Gizi per 100 gram BDD
                    </h6>
                </div>
                <div class="card-body">
                    @php
                    $nutrisiGroups = [
                        'Proksimat' => [
                            ['label' => 'Air', 'key' => 'air', 'unit' => 'g'],
                            ['label' => 'Energi', 'key' => 'energi', 'unit' => 'Kal'],
                            ['label' => 'Protein', 'key' => 'protein', 'unit' => 'g'],
                            ['label' => 'Lemak', 'key' => 'lemak', 'unit' => 'g'],
                            ['label' => 'Karbohidrat', 'key' => 'karbohidrat', 'unit' => 'g'],
                            ['label' => 'Serat', 'key' => 'serat', 'unit' => 'g'],
                            ['label' => 'Abu', 'key' => 'abu', 'unit' => 'g'],
                        ],
                        'Mineral' => [
                            ['label' => 'Kalsium (Ca)', 'key' => 'kalsium', 'unit' => 'mg'],
                            ['label' => 'Fosfor (P)', 'key' => 'fosfor', 'unit' => 'mg'],
                            ['label' => 'Besi (Fe)', 'key' => 'besi', 'unit' => 'mg'],
                            ['label' => 'Natrium (Na)', 'key' => 'natrium', 'unit' => 'mg'],
                            ['label' => 'Kalium (K)', 'key' => 'kalium', 'unit' => 'mg'],
                            ['label' => 'Tembaga (Cu)', 'key' => 'tembaga', 'unit' => 'mg'],
                            ['label' => 'Seng (Zn)', 'key' => 'seng', 'unit' => 'mg'],
                        ],
                        'Vitamin' => [
                            ['label' => 'Retinol', 'key' => 'retinol', 'unit' => 'mcg'],
                            ['label' => 'β-Karoten', 'key' => 'b_karoten', 'unit' => 'mcg'],
                            ['label' => 'Karoten Total', 'key' => 'kar_total', 'unit' => 'mcg'],
                            ['label' => 'Thiamin (B1)', 'key' => 'thiamin', 'unit' => 'mg'],
                            ['label' => 'Riboflavin (B2)', 'key' => 'riboflavin', 'unit' => 'mg'],
                            ['label' => 'Niasin (B3)', 'key' => 'niasin', 'unit' => 'mg'],
                            ['label' => 'Vitamin C', 'key' => 'vit_c', 'unit' => 'mg'],
                        ],
                    ];
                    @endphp

                    <div class="row g-4">
                        @foreach($nutrisiGroups as $groupName => $items)
                        <div class="col-md-4">
                            <h6 class="fw-semibold text-muted mb-2 border-bottom pb-1">{{ $groupName }}</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    @foreach($items as $item)
                                    <tr>
                                        <td class="text-muted small ps-0">{{ $item['label'] }}</td>
                                        <td class="text-end fw-semibold small pe-0">
                                            @php $val = $bahanPangan->{$item['key']}; @endphp
                                            {!! $val !== null ? number_format($val, 2) . ' ' . $item['unit'] : '<span class="text-muted">—</span>' !!}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection