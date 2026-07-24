@extends('layouts.app')

@section('title', 'Kelola Anggaran Porsi')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0" style="color:#0f4c81">
            <i class="fas fa-wallet me-2"></i>Kelola Anggaran Per Porsi
        </h4>
        <a href="{{ route('anggaran.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Tetapkan Anggaran Baru
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Kelompok Penerima</th>
                            <th>Anggaran/Porsi</th>
                            <th>Berlaku Mulai</th>
                            <th>Berlaku Sampai</th>
                            <th>Keterangan</th>
                            <th>Ditetapkan Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayat as $item)
                        <tr>
                            <td>
                                @if($item->kelompok === 'balita_sd3')
                                    <span class="badge bg-primary">
                                        <i class="fas fa-child me-1"></i>Balita s/d Kelas 3 SD
                                    </span>
                                @elseif($item->kelompok === 'sd4_ibu_menyusui')
                                    <span class="badge bg-success">
                                        <i class="fas fa-user-graduate me-1"></i>Kelas 4 SD s/d Ibu Menyusui
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="fw-semibold">Rp {{ number_format($item->anggaran_per_porsi, 0, ',', '.') }}</td>
                            <td>{{ $item->berlaku_mulai->format('d/m/Y') }}</td>
                            <td>
                                @if($item->berlaku_sampai)
                                    {{ $item->berlaku_sampai->format('d/m/Y') }}
                                @else
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @endif
                            </td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                            <td>{{ $item->createdBy->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data anggaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($riwayat->hasPages())
        <div class="card-footer">{{ $riwayat->links() }}</div>
        @endif
    </div>
</div>
@endsection
