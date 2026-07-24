{{-- resources/views/biaya/harga-form.blade.php --}}
@extends('layouts.app')

@section('title', 'Tambah Tarif Harga Bahan')

@section('content')
<div class="container py-4" style="max-width:600px">

    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="{{ route('biaya.harga.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="fw-semibold mb-0">Tambah Tarif Harga Bahan</h4>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2 mb-3 py-2" style="font-size:.875rem">
        <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
        <div>
            Menambahkan tarif baru akan <strong>otomatis menutup</strong> tarif aktif sebelumnya untuk bahan yang sama
            — berlaku sampai = tanggal sebelum tarif baru mulai berlaku.
            Satu bahan hanya boleh punya <strong>satu tarif per tanggal mulai</strong>; tanggal yang sudah dipakai akan ditolak.
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('biaya.harga.store') }}">
                @csrf

                <div class="mb-3 position-relative">
                    <label class="form-label fw-medium">Bahan Pangan <span class="text-danger">*</span></label>
                    <input type="hidden" name="bahan_pangan_id" id="bahan-pangan-id" value="{{ old('bahan_pangan_id') }}">
                    <input type="text" id="bahan-search"
                           class="form-control @error('bahan_pangan_id') is-invalid @enderror"
                           placeholder="Ketik nama bahan untuk mencari..."
                           autocomplete="off"
                           value="">
                    <div id="bahan-dropdown"
                         class="list-group shadow-sm position-absolute w-100 z-3"
                         style="max-height:220px;overflow-y:auto;display:none;top:100%;left:0"></div>
                    @error('bahan_pangan_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Harga per kg (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_per_kg" step="1" min="0"
                           value="{{ old('harga_per_kg') }}"
                           class="form-control @error('harga_per_kg') is-invalid @enderror"
                           placeholder="Contoh: 15000"
                           required>
                    <div class="form-text">Masukkan harga pembelian per kilogram (kg).</div>
                    @error('harga_per_kg')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Berlaku Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="berlaku_mulai"
                           value="{{ old('berlaku_mulai') }}"
                           class="form-control @error('berlaku_mulai') is-invalid @enderror"
                           required>
                    <div class="form-text">Tarif berlaku mulai tanggal ini hingga ditetapkan tarif baru.</div>
                    @error('berlaku_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Keterangan</label>
                    <input type="text" name="keterangan" maxlength="200"
                           value="{{ old('keterangan') }}"
                           class="form-control @error('keterangan') is-invalid @enderror"
                           placeholder="Opsional...">
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Simpan
                    </button>
                    <a href="{{ route('biaya.harga.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>

            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const API_BAHAN_SEARCH_URL = @json(route('api.bahan-pangan.search'));
    const searchInput = document.getElementById('bahan-search');
    const hiddenInput = document.getElementById('bahan-pangan-id');
    const dropdown    = document.getElementById('bahan-dropdown');
    let debounceTimer;

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        hiddenInput.value = '';
        clearTimeout(debounceTimer);

        if (q.length < 2) { dropdown.style.display = 'none'; return; }

        dropdown.innerHTML = skeletonDropdownRows();
        dropdown.style.display = 'block';

        debounceTimer = setTimeout(async () => {
            try {
                const res  = await fetch(`${API_BAHAN_SEARCH_URL}?q=${encodeURIComponent(q)}&limit=15`);
                if (!res.ok) { dropdown.style.display = 'none'; return; }
                const data = await res.json();
                renderDropdown(data);
            } catch { dropdown.style.display = 'none'; }
        }, 250);
    });

    function skeletonDropdownRows(n = 3) {
        return Array.from({ length: n }).map(() => `
            <span class="list-group-item">
                <span class="skeleton d-block" style="width:70%;height:12px"></span>
            </span>`).join('');
    }

    function renderDropdown(items) {
        dropdown.innerHTML = '';
        if (!items.length) {
            dropdown.innerHTML = '<span class="list-group-item text-muted small">Tidak ditemukan</span>';
            dropdown.style.display = 'block';
            return;
        }
        items.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action small';
            btn.textContent = item.nama_bahan;
            btn.addEventListener('mousedown', () => {
                hiddenInput.value  = item.id;
                searchInput.value  = item.nama_bahan;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(btn);
        });
        dropdown.style.display = 'block';
    }

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && dropdown.children.length) {
            dropdown.style.display = 'block';
        }
    });
})();
</script>
@endpush
@endsection
