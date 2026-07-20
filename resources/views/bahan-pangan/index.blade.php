@extends('layouts.app')

@section('title', 'Data Bahan Pangan TKPI')

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--primary);">
                <i class="fas fa-database me-2"></i>Data Bahan Pangan TKPI
            </h4>
            <small class="text-muted">Tabel Komposisi Pangan Indonesia — {{ number_format($stats['total']) }} bahan pangan</small>
        </div>
        @if(auth()->user()->role === 'ketua_sppg')
        <div class="d-flex gap-2">
            <a href="{{ route('import-tkpi.index') }}" class="btn btn-outline-success btn-sm px-3">
                <i class="fas fa-file-csv me-1"></i> Import CSV
            </a>
            <a href="{{ route('bahan-pangan.create') }}" class="btn btn-primary btn-sm px-3">
                <i class="fas fa-plus me-1"></i> Tambah Bahan
            </a>
        </div>
        @endif
    </div>

    {{-- Alert --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="fas fa-check-circle me-2"></i>{!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('bahan-pangan.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    {{-- Search --}}
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Cari Bahan Pangan</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="cari" class="form-control border-start-0 ps-0"
                                   placeholder="Nama bahan / kode..."
                                   value="{{ request('cari') }}" autocomplete="off">
                        </div>
                    </div>

                    {{-- Filter Kategori --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Kategori</label>
                        <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">— Semua Kategori —</option>
                            @foreach(['Serealia','Umbi','Kacang','Sayuran','Buah','Daging','Ikan','Telur','Susu','Lemak','Gula','Bumbu','Minuman'] as $kat)
                            <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>
                                {{ $kat }}
                                @if(isset($stats['per_kategori'][$kat]))
                                    ({{ $stats['per_kategori'][$kat] }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Sub Kategori --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1">Jenis</label>
                        <select name="sub_kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">— Semua —</option>
                            <option value="TUNGGAL" {{ request('sub_kategori') === 'TUNGGAL' ? 'selected' : '' }}>Tunggal</option>
                            <option value="OLAHAN"  {{ request('sub_kategori') === 'OLAHAN'  ? 'selected' : '' }}>Olahan</option>
                        </select>
                    </div>

                    {{-- Tombol --}}
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-filter me-1"></i>Cari
                        </button>
                        <a href="{{ route('bahan-pangan.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="fas fa-undo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-dark">
                Menampilkan {{ $bahanPangans->firstItem() }}–{{ $bahanPangans->lastItem() }}
                dari {{ number_format($bahanPangans->total()) }} bahan
                @if(request('cari') || request('kategori'))
                    <span class="badge bg-warning text-dark ms-2">Filtered</span>
                @endif
            </span>
            <div class="d-flex gap-1">
                {{-- Sort links --}}
                @php
                    $sortBy  = request('sort', 'kode');
                    $sortDir = request('dir', 'asc');
                    $nextDir = $sortDir === 'asc' ? 'desc' : 'asc';
                    $sortUrl = fn($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => request('sort') === $col && request('dir', 'asc') === 'asc' ? 'desc' : 'asc'])
                @endphp
                <small class="text-muted me-2 align-self-center">Sort:</small>
                <a href="{{ $sortUrl('kode') }}" class="btn btn-outline-secondary btn-xs py-0 px-2 {{ $sortBy==='kode' ? 'active' : '' }}">Kode</a>
                <a href="{{ $sortUrl('nama_bahan') }}" class="btn btn-outline-secondary btn-xs py-0 px-2 {{ $sortBy==='nama_bahan' ? 'active' : '' }}">Nama</a>
                <a href="{{ $sortUrl('energi') }}" class="btn btn-outline-secondary btn-xs py-0 px-2 {{ $sortBy==='energi' ? 'active' : '' }}">Energi</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:90px">Kode</th>
                            <th>Nama Bahan</th>
                            <th>Kategori</th>
                            <th class="text-center" style="width:75px">
                                Energi<br><small class="fw-normal text-muted">(Kal)</small>
                            </th>
                            <th class="text-center" style="width:70px">
                                Protein<br><small class="fw-normal text-muted">(g)</small>
                            </th>
                            <th class="text-center" style="width:70px">
                                Lemak<br><small class="fw-normal text-muted">(g)</small>
                            </th>
                            <th class="text-center" style="width:70px">
                                Karbo<br><small class="fw-normal text-muted">(g)</small>
                            </th>
                            <th class="text-center" style="width:60px">BDD%</th>
                            <th class="text-center" style="width:50px">Status</th>
                            @if(auth()->user()->role !== 'ahli_gizi')
                            <th class="text-center" style="width:110px">
                                Harga/kg<br><small class="fw-normal text-muted">(Rp)</small>
                            </th>
                            @endif
                            <th class="text-center" style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bahanPangans as $bahan)
                        <tr class="{{ !$bahan->is_active ? 'table-secondary text-muted' : '' }}">
                            <td class="ps-3">
                                <code class="small" style="color: var(--primary);">{{ $bahan->kode }}</code>
                            </td>
                            <td>
                                <a href="{{ route('bahan-pangan.show', $bahan) }}"
                                   class="text-decoration-none fw-medium text-dark">
                                    {{ $bahan->nama_bahan }}
                                </a>
                                @if($bahan->sub_kategori)
                                <br><span class="badge bg-light text-secondary border small">
                                    {{ $bahan->sub_kategori }}
                                </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $katStyles = [
                                        'Serealia' => ['bg'=>'#daeeff', 'text'=>'#0f4c81'],  // biru
                                        'Umbi'     => ['bg'=>'#fff3cd', 'text'=>'#664d03'],  // kuning
                                        'Kacang'   => ['bg'=>'#cff4fc', 'text'=>'#055160'],  // biru muda
                                        'Sayuran'  => ['bg'=>'#d4f7e0', 'text'=>'#146e35'],  // hijau untuk sayuran
                                        'Buah'     => ['bg'=>'#f8d7da', 'text'=>'#58151c'],  // merah muda
                                        'Daging'   => ['bg'=>'#e2e3e5', 'text'=>'#2b2d2f'],  // abu
                                        'Ikan'     => ['bg'=>'#cfe2ff', 'text'=>'#052c65'],  // biru
                                        'Telur'    => ['bg'=>'#fff3cd', 'text'=>'#664d03'],  // kuning
                                        'Susu'     => ['bg'=>'#e2e3e5', 'text'=>'#41464b'],  // abu muda
                                        'Lemak'    => ['bg'=>'#f8f9fa', 'text'=>'#495057'],  // putih abu
                                        'Gula'     => ['bg'=>'#f8d7da', 'text'=>'#58151c'],  // merah muda
                                        'Bumbu'    => ['bg'=>'#fff3cd', 'text'=>'#664d03'],  // kuning
                                        'Minuman'  => ['bg'=>'#cff4fc', 'text'=>'#055160'],  // biru muda
                                    ];
                                    $style = $katStyles[$bahan->kategori] ?? ['bg'=>'#e2e3e5','text'=>'#41464b'];
                                @endphp
                                <span style="background-color: {{ $style['bg'] }}; color: {{ $style['text'] }}; 
                                             padding: 3px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600;
                                             white-space: nowrap;">
                                    {{ $bahan->kategori }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $bahan->energi !== null ? number_format($bahan->energi, 0) : '—' }}</span>
                            </td>
                            <td class="text-center text-muted small">{{ $bahan->protein !== null ? number_format($bahan->protein, 1) : '—' }}</td>
                            <td class="text-center text-muted small">{{ $bahan->lemak !== null ? number_format($bahan->lemak, 1) : '—' }}</td>
                            <td class="text-center text-muted small">{{ $bahan->karbohidrat !== null ? number_format($bahan->karbohidrat, 1) : '—' }}</td>
                            <td class="text-center text-muted small">{{ $bahan->bdd !== null ? $bahan->bdd : '—' }}</td>
                            <td class="text-center">
                                @if($bahan->is_active)
                                    <span class="badge bg-primary-subtle text-primary">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            @if(auth()->user()->role !== 'ahli_gizi')
                            <td class="text-center">
                                @if(isset($hargaMap[$bahan->id]))
                                    <span class="small fw-semibold text-dark">
                                        Rp {{ number_format($hargaMap[$bahan->id] * 10, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger" style="font-size:.7rem;">Belum ada</span>
                                @endif
                            </td>
                            @endif
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('bahan-pangan.show', $bahan) }}"
                                       class="btn btn-outline-primary btn-xs" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(auth()->user()->role === 'ketua_sppg')
                                    <a href="{{ route('bahan-pangan.edit', $bahan) }}"
                                       class="btn btn-outline-warning btn-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-xs"
                                            title="Hapus"
                                            onclick="confirmDelete('{{ $bahan->id }}', '{{ addslashes($bahan->nama_bahan) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role !== 'ahli_gizi' ? 11 : 10 }}" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                Tidak ada data yang cocok dengan pencarian.
                                <br>
                                <a href="{{ route('bahan-pangan.index') }}" class="btn btn-sm btn-outline-primary mt-2">Reset Filter</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($bahanPangans->hasPages())
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Halaman {{ $bahanPangans->currentPage() }} dari {{ $bahanPangans->lastPage() }}
                </small>
                {{ $bahanPangans->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal Delete Confirmation --}}
<form id="deleteForm" method="POST" action="">
    @csrf @method('DELETE')
</form>

@push('scripts')
<script>
function confirmDelete(id, nama) {
    if (confirm(`Hapus bahan pangan "${nama}"?\n\nTindakan ini tidak dapat dibatalkan.`)) {
        const form = document.getElementById('deleteForm');
        form.action = `/bahan-pangan/${id}`;
        form.submit();
    }
}

// Auto-submit filter saat ketik (debounce)
let searchTimeout;
document.querySelector('input[name="cari"]').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});
</script>
@endpush
@endsection