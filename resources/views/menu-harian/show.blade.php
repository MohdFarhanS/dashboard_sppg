@extends('layouts.app')
@section('title', 'Detail Menu')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ auth()->user()->isAkuntan() ? route('dashboard') : route('menu-harian.index') }}"
               class="btn btn-sm btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--primary)">
                    Detail Menu — {{ $menuHarian->tanggal->translatedFormat('d F Y') }}
                </h4>
                <small class="text-muted">
                    @if($menuHarian->nama_menu)
                        · <span class="fw-semibold">{{ $menuHarian->nama_menu }}</span>
                    @endif
                </small>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Badge kelompok --}}
            @php
                $ks = $menuHarian->kelompok_sasaran ?? 'SD_4_6';
                $ksLabel = \App\Constants\AKG::KELOMPOK[$ks]['label'] ?? $ks;
            @endphp
            <span class="badge p-2" style="background:#daeeff;color:#0f4c81;font-size:.8rem">
                <i class="fas fa-users me-1"></i>{{ $ksLabel }}
            </span>
            @if($menuHarian->catatan_anggaran)
            <span class="badge p-2" style="background:#f0f5fc;color:#0d2545;font-size:.8rem;border:1px solid #d0e4f7">
                <i class="fas fa-school me-1"></i>{{ $menuHarian->catatan_anggaran }}
            </span>
            @endif

            @if($menuHarian->status === 'final')
                <span class="badge p-2" style="background:#e2e8f0;color:#475569;font-size:.85rem">
                    <i class="fas fa-lock me-1"></i>Final
                </span>
            @else
                <span class="badge p-2" style="background:#fff3cd;color:#664d03;font-size:.85rem">
                    <i class="fas fa-pencil me-1"></i>Draft
                </span>
                @if(auth()->user()->isAhliGizi())
                <a href="{{ route('simulasi.edit-simulasi', $menuHarian) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                {{-- Tombol upload foto --}}
                <button type="button" class="btn btn-sm {{ $menuHarian->foto_menu ? 'btn-success' : 'btn-outline-warning' }}"
                        data-bs-toggle="modal" data-bs-target="#modalUploadFoto"
                        title="{{ $menuHarian->foto_menu ? 'Ganti foto menu' : 'Upload foto menu' }}">
                    <i class="fas fa-camera me-1"></i>{{ $menuHarian->foto_menu ? 'Ganti Foto' : 'Upload Foto' }}
                </button>
                {{-- Tombol finalisasi — disabled jika belum ada foto --}}
                @if($menuHarian->foto_menu)
                <form action="{{ route('menu-harian.finalize', $menuHarian) }}" method="POST"
                      onsubmit="return confirm('Finalisasi menu? Tidak bisa diedit setelah ini.')">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-primary"
                            style="background:var(--primary);border-color:var(--primary)">
                        <i class="fas fa-lock me-1"></i>Finalisasi
                    </button>
                </form>
                @else
                <button class="btn btn-sm btn-secondary" disabled
                        title="Upload foto menu terlebih dahulu">
                    <i class="fas fa-lock me-1"></i>Finalisasi
                </button>
                @endif
                @endif
            @endif
        </div>
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

    {{-- Notifikasi belum upload foto (untuk draft ahli_gizi) --}}
    @if($menuHarian->status === 'draft' && auth()->user()->isAhliGizi() && !$menuHarian->foto_menu)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-camera fs-5 flex-shrink-0"></i>
        <div>
            <strong>Foto menu belum diupload.</strong>
            Upload foto menu terlebih dahulu sebelum melakukan finalisasi.
        </div>
    </div>
    @endif

    @php
        $isAkuntan = auth()->user()->isAkuntan();
        $gizi = $menuHarian->totalGizi();
        // Gunakan target AKG kelompok_sasaran untuk 4 makro, MAKAN_SIANG untuk mikronutrien
        $akg  = array_merge(\App\Constants\AKG::MAKAN_SIANG, $menuHarian->akgTarget('siang'));
        $giziMeta = [
            ['key'=>'energi',      'label'=>'Energi',      'unit'=>'kkal', 'color'=>'#0f4c81', 'icon'=>'fa-fire',      'bg'=>'#daeeff'],
            ['key'=>'protein',     'label'=>'Protein',     'unit'=>'g',    'color'=>'#0d6efd', 'icon'=>'fa-dumbbell',  'bg'=>'#e7f0ff'],
            ['key'=>'lemak',       'label'=>'Lemak',       'unit'=>'g',    'color'=>'#fd7e14', 'icon'=>'fa-droplet',   'bg'=>'#fff3e0'],
            ['key'=>'karbohidrat', 'label'=>'Karbohidrat', 'unit'=>'g',    'color'=>'#6f42c1', 'icon'=>'fa-wheat-awn', 'bg'=>'#f3eeff'],
            ['key'=>'serat',       'label'=>'Serat',       'unit'=>'g',    'color'=>'#20c997', 'icon'=>'fa-leaf',      'bg'=>'#e6faf5'],
            ['key'=>'vit_c',       'label'=>'Vit. C',      'unit'=>'mg',   'color'=>'#dc3545', 'icon'=>'fa-lemon',     'bg'=>'#fff0f0'],
        ];
    @endphp

    {{-- Stat gizi --}}
    @if(!$isAkuntan)
    <div class="row g-3 mb-4">
        @foreach($giziMeta as $m)
        @php
            $nilai  = $gizi[$m['key']] ?? 0;
            $target = $akg[$m['key']]  ?? 1;
            $pct    = $target > 0 ? round($nilai / $target * 100, 1) : 0;
            $barW   = min($pct, 100);
            if ($pct < 70)       $status = ['label'=>'Kurang', 'color'=>'#842029', 'bg'=>'#f8d7da'];
            elseif ($pct > 130)  $status = ['label'=>'Lebih',  'color'=>'#664d03', 'bg'=>'#fff3cd'];
            else                 $status = ['label'=>'Cukup',  'color'=>'#0f4c81', 'bg'=>'#daeeff'];
        @endphp
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100" style="overflow:hidden">
                <div class="card-body p-3">
                    {{-- Icon + label --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:32px;height:32px;background:{{ $m['bg'] }}">
                            <i class="fas {{ $m['icon'] }}" style="color:{{ $m['color'] }};font-size:.8rem"></i>
                        </div>
                        <div style="min-width:0">
                            <div class="fw-semibold text-truncate" style="font-size:.8rem;line-height:1.2">{{ $m['label'] }}</div>
                            <div class="text-muted" style="font-size:.68rem">Target {{ $target }} {{ $m['unit'] }}</div>
                        </div>
                    </div>
                    {{-- Nilai --}}
                    <div class="fw-bold lh-1" style="font-size:1.35rem;color:{{ $m['color'] }}">
                        {{ number_format($nilai, 1) }}
                        <span class="fw-normal text-muted" style="font-size:.72rem">{{ $m['unit'] }}</span>
                    </div>
                    {{-- Badge + bar --}}
                    <div class="d-flex align-items-center justify-content-between mt-2 mb-1">
                        <span class="badge" style="font-size:.62rem;background:{{ $status['bg'] }};color:{{ $status['color'] }}">
                            {{ $status['label'] }}
                        </span>
                        <span style="font-size:.7rem;font-weight:600;color:{{ $m['color'] }}">{{ $pct }}%</span>
                    </div>
                    <div style="height:5px;background:#e9ecef;border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $barW }}%;background:{{ $m['color'] }};border-radius:3px"></div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- BUDGET ALERT — tampil di antara stat gizi dan tabel bahan    --}}
    {{-- ============================================================ --}}
    @php
        $biaya          = $menuHarian->totalBiaya();
        $statusAnggaran = $menuHarian->statusAnggaran();
        $persenAnggaran = $biaya['persen_anggaran'];
        $anggaranAktif  = $biaya['anggaran'];
        $costPerPorsi   = $biaya['cost_per_porsi'];
        $selisih        = $biaya['selisih'];
    @endphp

    @if(!auth()->user()->isAhliGizi() && $statusAnggaran !== 'belum_ada_data')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">

            {{-- Header row --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="fas fa-wallet" style="color:var(--primary)"></i>
                    <span class="fw-semibold">Realisasi Biaya vs Anggaran Per Porsi</span>
                    @if($menuHarian->kelompok === 'balita_sd3')
                        <span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.72rem">
                            <i class="fas fa-child me-1"></i>Anggaran: Balita s/d Kls 3 SD
                        </span>
                    @else
                        <span class="badge" style="background:#d1f0e0;color:#1a6640;font-size:.72rem">
                            <i class="fas fa-user-graduate me-1"></i>Anggaran: Kls 4 SD s/d Ibu Menyusui
                        </span>
                    @endif
                    @if($statusAnggaran === 'over')
                        <span class="badge bg-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>Melebihi Anggaran
                        </span>
                    @elseif($statusAnggaran === 'warning')
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-exclamation-circle me-1"></i>Mendekati Batas
                        </span>
                    @else
                        <span class="badge bg-primary">
                            <i class="fas fa-check me-1"></i>Dalam Anggaran
                        </span>
                    @endif
                </div>
                <div class="text-end">
                    <span class="fw-bold fs-5
                        @if($statusAnggaran === 'over') text-danger
                        @elseif($statusAnggaran === 'warning') text-warning
                        @else text-primary @endif">
                        Rp {{ number_format($costPerPorsi, 0, ',', '.') }}
                    </span>
                    <span class="text-muted small"> / Rp {{ number_format($anggaranAktif, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Progress bar --}}
            <div class="progress mb-2" style="height:10px;border-radius:8px;">
                <div class="progress-bar
                    @if($statusAnggaran === 'over') bg-danger
                    @elseif($statusAnggaran === 'warning') bg-warning
                    @else bg-primary @endif"
                    role="progressbar"
                    style="width:{{ min($persenAnggaran, 100) }}%;border-radius:8px;"
                    aria-valuenow="{{ $persenAnggaran }}"
                    aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>

            {{-- Label bawah progress --}}
            <div class="d-flex justify-content-between">
                <small class="text-muted">Rp 0</small>
                <small class="fw-semibold
                    @if($statusAnggaran === 'over') text-danger
                    @elseif($statusAnggaran === 'warning') text-warning
                    @else text-primary @endif">
                    {{ $persenAnggaran }}% dari anggaran
                </small>
                <small class="text-muted">Rp {{ number_format($anggaranAktif, 0, ',', '.') }}</small>
            </div>

        </div>
    </div>

    {{-- Alert banner over / warning --}}
    @if($statusAnggaran === 'over')
    <div class="alert alert-danger d-flex align-items-start gap-3 mb-4">
        <i class="fas fa-exclamation-triangle fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <div class="fw-semibold mb-1">Biaya per porsi melebihi anggaran!</div>
            Biaya saat ini <strong>Rp {{ number_format($costPerPorsi, 0, ',', '.') }}</strong>
            melebihi anggaran sebesar
            <strong class="text-danger">Rp {{ number_format(abs($selisih), 0, ',', '.') }}</strong>.
            Pertimbangkan untuk mengurangi porsi atau mengganti bahan.
        </div>
    </div>
    @elseif($statusAnggaran === 'warning')
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-4">
        <i class="fas fa-info-circle fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <div class="fw-semibold mb-1">Mendekati batas anggaran.</div>
            Sisa anggaran per porsi:
            <strong>Rp {{ number_format($selisih, 0, ',', '.') }}</strong>
            ({{ round(100 - $persenAnggaran, 1) }}% tersisa).
        </div>
    </div>
    @endif
    @endif
    {{-- ============================================================ --}}

    {{-- Tabel bahan --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 d-flex justify-content-between align-items-center"
             style="background:#daeeff">
            <span class="fw-semibold" style="color:#0f4c81">
                <i class="fas fa-cloud-sun me-2"></i>Bahan Pangan — Makan Siang
            </span>
            <small class="text-muted">{{ $menuHarian->detailBahans->count() }} bahan</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Bahan Pangan</th>
                            <th>Kategori</th>
                            <th class="text-end">Gram</th>
                            <th class="text-end">Porsi</th>
                            <th class="text-end">BDD</th>
                            @if(!$isAkuntan)
                            <th class="text-end">Energi</th>
                            <th class="text-end">Protein</th>
                            <th class="text-end">Lemak</th>
                            <th class="text-end">Karbo</th>
                            <th class="text-end">Serat</th>
                            <th class="text-end pe-4">Vit. C</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menuHarian->detailBahans as $detail)
                        @php
                            $b = $detail->bahanPangan;
                            // Bagi dengan jumlah_porsi menu → kontribusi gizi per orang untuk bahan ini
                            $jumlahPorsiMenu = max((int) $menuHarian->jumlah_porsi, 1);
                            $f = ($detail->jumlah_gram * (($b->bdd ?? 100) / 100)) / 100
                                 * $detail->jumlah_porsi / $jumlahPorsiMenu;
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $b->nama_bahan }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $b->kode }}</div>
                            </td>
                            <td><small class="text-muted">{{ $b->kategori }}</small></td>
                            <td class="text-end">{{ $detail->jumlah_gram }}g</td>
                            <td class="text-end">{{ $detail->jumlah_porsi }}x</td>
                            <td class="text-end text-muted small">{{ $b->bdd }}%</td>
                            @if(!$isAkuntan)
                            <td class="text-end fw-semibold" style="color:var(--primary)">
                                {{ number_format($f * ($b->energi ?? 0), 1) }}
                            </td>
                            <td class="text-end">{{ number_format($f * ($b->protein ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format($f * ($b->lemak ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format($f * ($b->karbohidrat ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format($f * ($b->serat ?? 0), 2) }}</td>
                            <td class="text-end pe-4">{{ number_format($f * ($b->vit_c ?? 0), 2) }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $isAkuntan ? 5 : 11 }}" class="text-center text-muted py-4">Belum ada bahan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($menuHarian->detailBahans->count() && !$isAkuntan)
                    <tfoot style="background:var(--primary-pale)">
                        <tr class="fw-semibold">
                            <td class="ps-4" colspan="5">Total Gizi</td>
                            <td class="text-end" style="color:var(--primary)">
                                {{ number_format($gizi['energi'], 1) }} kkal
                            </td>
                            <td class="text-end">{{ number_format($gizi['protein'], 2) }} g</td>
                            <td class="text-end">{{ number_format($gizi['lemak'], 2) }} g</td>
                            <td class="text-end">{{ number_format($gizi['karbohidrat'], 2) }} g</td>
                            <td class="text-end">{{ number_format($gizi['serat'], 2) }} g</td>
                            <td class="text-end pe-4">{{ number_format($gizi['vit_c'], 2) }} mg</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if($menuHarian->catatan_anggaran ?? $menuHarian->catatan)
        <div class="card-footer bg-white border-top">
            <small>
                <i class="fas fa-school me-1" style="color:var(--primary)"></i>
                <span class="fw-semibold text-muted">Kelompok Penerima:</span>
                {{ $menuHarian->catatan_anggaran ?? $menuHarian->catatan }}
            </small>
        </div>
        @endif
    </div>

    {{-- Card Foto Menu --}}
    @if($menuHarian->foto_menu || ($menuHarian->status === 'draft' && auth()->user()->isAhliGizi()))
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header border-0 d-flex justify-content-between align-items-center"
             style="background:#daeeff">
            <span class="fw-semibold" style="color:#0f4c81">
                <i class="fas fa-image me-2"></i>Foto Menu
            </span>
            @if($menuHarian->status === 'draft' && auth()->user()->isAhliGizi())
            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#modalUploadFoto">
                <i class="fas fa-camera me-1"></i>{{ $menuHarian->foto_menu ? 'Ganti Foto' : 'Upload Foto' }}
            </button>
            @endif
        </div>
        <div class="card-body text-center py-4">
            @if($menuHarian->foto_menu)
                <img src="{{ Storage::url($menuHarian->foto_menu) }}"
                     class="img-fluid rounded shadow-sm"
                     style="max-height:400px;object-fit:contain"
                     alt="Foto menu {{ $menuHarian->nama_menu }}">
            @else
                <div class="text-muted py-3">
                    <i class="fas fa-camera fa-3x mb-3 d-block opacity-25"></i>
                    Foto menu belum diupload.
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Modal Upload Foto (hanya untuk draft + ahli_gizi) --}}
    @if($menuHarian->status === 'draft' && auth()->user()->isAhliGizi())
    <div class="modal fade" id="modalUploadFoto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">
                        <i class="fas fa-camera me-2"></i>Upload Foto Menu
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('menu-harian.upload-foto', $menuHarian) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @if($menuHarian->foto_menu)
                        <div class="mb-3 text-center">
                            <img src="{{ Storage::url($menuHarian->foto_menu) }}"
                                 class="img-thumbnail" style="max-height:200px"
                                 alt="Foto saat ini">
                            <p class="text-muted small mt-1">Foto saat ini</p>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                {{ $menuHarian->foto_menu ? 'Ganti Foto Menu' : 'Pilih Foto Menu' }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="foto_menu"
                                   class="form-control"
                                   accept="image/*" required>
                            <div class="form-text">Format JPG/PNG/WebP, maksimal 2 MB</div>
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

</div>
@endsection