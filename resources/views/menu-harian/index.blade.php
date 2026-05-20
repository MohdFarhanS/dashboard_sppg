@extends('layouts.app')
@section('title', 'Menu Harian')
@section('page-title', 'Menu Harian')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--primary)">
                <i class="fas fa-utensils me-2"></i>Menu Harian
            </h4>
        </div>
        @if(auth()->user()->role === 'ahli_gizi')
        <a href="{{ route('simulasi.index') }}" class="btn btn-primary"
           style="background:var(--primary);border-color:var(--primary)">
            <i class="fas fa-flask me-1"></i> Buat Menu Baru
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bulan</label>
                    <input type="month" name="bulan" class="form-control"
                           value="{{ request('bulan', now()->format('Y-m')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                        <option value="final" {{ request('status')=='final'?'selected':'' }}>Final</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kelompok</label>
                    <select name="kelompok_sasaran" class="form-select">
                        <option value="">Semua Kelompok</option>
                        @foreach(\App\Constants\AKG::KELOMPOK as $key => $data)
                        <option value="{{ $key }}" {{ request('kelompok_sasaran')===$key?'selected':'' }}>
                            {{ $data['label'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"
                            style="background:var(--primary);border-color:var(--primary)">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('menu-harian.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:var(--primary-pale)">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Nama Menu</th>
                            <th>Kelompok</th>
                            <th>Jumlah Bahan</th>
                            <th>Estimasi Energi</th>
                            <th>Status</th>
                            <th>Anggaran</th>
                            @if(auth()->user()->role === 'ahli_gizi')
                            <th class="text-center">Foto Menu</th>
                            @endif
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                        @php $gizi = $menu->totalGizi(); @endphp
                        <tr>
                            <td class="ps-4 fw-semibold">
                                {{ $menu->tanggal->translatedFormat('d F Y') }}
                                @if($menu->tanggal->isToday())
                                    <span class="badge ms-1"
                                          style="background:var(--primary);font-size:.65rem">Hari ini</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $menu->nama_menu ?? '-' }}</div>
                                @if($menu->catatan_anggaran)
                                <small class="text-muted">
                                    <i class="fas fa-school fa-xs me-1"></i>{{ $menu->catatan_anggaran }}
                                </small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $ks = $menu->kelompok_sasaran ?? 'SD_4_6';
                                    $ksLabel = \App\Constants\AKG::KELOMPOK[$ks]['label'] ?? $ks;
                                @endphp
                                <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.72rem"
                                      title="{{ $ksLabel }}">
                                    {{ Str::limit($ksLabel, 22) }}
                                </span>
                            </td>
                            <td class="text-muted">{{ $menu->detailBahans->count() }} bahan</td>
                            <td>
                                <span class="fw-semibold" style="color:var(--primary)">
                                    {{ number_format($gizi['energi'], 0) }}
                                </span>
                                <span class="text-muted small">kkal</span>
                            </td>
                            <td>
                                @if($menu->status === 'final')
                                    <span class="badge" style="background:#daeeff;color:#0f4c81">
                                        <i class="fas fa-lock me-1"></i>Final
                                    </span>
                                @else
                                    <span class="badge" style="background:#fff3cd;color:#664d03">
                                        <i class="fas fa-pencil me-1"></i>Draft
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($menu->status === 'final')
                                    @php $statusAnggaran = $menu->statusAnggaran(); @endphp
                                    @if($statusAnggaran === 'over')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Over
                                        </span>
                                    @elseif($statusAnggaran === 'warning')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-circle me-1"></i>Mendekati
                                        </span>
                                    @elseif($statusAnggaran === 'aman')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-check me-1"></i>Aman
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            @if(auth()->user()->role === 'ahli_gizi')
                            <td class="text-center">
                                @if($menu->status === 'final')
                                    @if($menu->foto_menu)
                                        <a href="{{ Storage::url($menu->foto_menu) }}" target="_blank"
                                           class="btn btn-sm btn-outline-success" title="Lihat foto">
                                            <i class="fas fa-image"></i>
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                @else
                                    @if($menu->foto_menu)
                                        <button type="button"
                                                class="btn btn-sm btn-success"
                                                title="Foto sudah diupload — klik untuk ganti"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalFoto{{ $menu->id }}">
                                            <i class="fas fa-check me-1"></i>Foto
                                        </button>
                                    @else
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Upload foto menu"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalFoto{{ $menu->id }}">
                                            <i class="fas fa-camera me-1"></i>Upload
                                        </button>
                                    @endif
                                @endif
                            </td>
                            @endif
                            <td class="text-end pe-4">
                                <a href="{{ route('menu-harian.show', $menu) }}"
                                   class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->role === 'ahli_gizi' && $menu->status !== 'final')
                                <a href="{{ route('simulasi.edit-simulasi', $menu) }}"
                                   class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('menu-harian.destroy', $menu) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Hapus menu tanggal ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'ahli_gizi' ? 9 : 8 }}" class="text-center text-muted py-5">
                                <i class="fas fa-utensils fa-2x mb-2 d-block opacity-25"></i>
                                Belum ada menu untuk bulan ini.
                                @if(auth()->user()->role === 'ahli_gizi')
                                    <a href="{{ route('simulasi.index') }}">Buat menu via Simulasi</a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($menus->hasPages())
        <div class="card-footer bg-white border-0 d-flex justify-content-center">
            {{ $menus->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Upload Foto (di luar tabel agar Bootstrap dapat render dengan benar) --}}
    @if(auth()->user()->role === 'ahli_gizi')
        @foreach($menus as $menu)
            @if($menu->status !== 'final')
            <div class="modal fade" id="modalFoto{{ $menu->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title fw-bold">
                                <i class="fas fa-camera me-2"></i>Upload Foto Menu
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('menu-harian.upload-foto', $menu) }}"
                              method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    Menu: <strong>{{ $menu->nama_menu ?? $menu->tanggal->translatedFormat('d F Y') }}</strong>
                                </p>
                                @if($menu->foto_menu)
                                <div class="mb-3 text-center">
                                    <img src="{{ Storage::url($menu->foto_menu) }}"
                                         class="img-thumbnail" style="max-height:180px"
                                         alt="Foto saat ini">
                                    <p class="text-muted small mt-1">Foto saat ini</p>
                                </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        {{ $menu->foto_menu ? 'Ganti Foto' : 'Pilih Foto Menu' }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="foto_menu"
                                           class="form-control" accept="image/*" required>
                                    <div class="form-text">Format JPG/PNG/WebP, maks 2 MB</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="background:var(--primary);border-color:var(--primary)">
                                    <i class="fas fa-upload me-1"></i>Upload Foto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @endif

</div>
@endsection