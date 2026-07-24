@extends('layouts.app')
@section('title', 'Edit Menu Harian')

@push('styles')
{{-- Sama persis dengan create --}}
<style>
    .bahan-row { background: #f8f9fa; border-radius: 8px; padding: 10px; margin-bottom: 8px; position: relative; }
    .autocomplete-list { position: absolute; z-index: 1000; background: white;
        border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,.1);
        max-height: 220px; overflow-y: auto; width: 100%; left: 0; top: 100%; }
    .autocomplete-item { padding: 8px 12px; cursor: pointer; font-size: .875rem;
        border-bottom: 1px solid #f0f0f0; }
    .autocomplete-item:hover { background: var(--primary-pale); }
    .autocomplete-item .kode { color: var(--primary); font-size: .75rem; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('menu-harian.show', $menuHarian) }}"
           class="btn btn-sm btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                Edit Menu — {{ $menuHarian->tanggal->translatedFormat('d F Y') }}
            </h4>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('menu-harian.update', $menuHarian) }}" method="POST" id="formMenu">
        @csrf
        @method('PUT')
        <div class="row g-4">

            {{-- Kolom kiri --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                    <div class="card-header border-0 fw-semibold" style="background:var(--primary-pale)">
                        <i class="fas fa-info-circle me-2" style="color:var(--primary)"></i>Informasi Menu
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input type="text" class="form-control bg-light"
                                   value="{{ $menuHarian->tanggal->translatedFormat('d F Y') }}" disabled>
                            {{-- Tanggal tidak bisa diubah saat edit --}}
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Menu</label>
                            <input type="text" name="nama_menu" class="form-control"
                                   placeholder="Contoh: Nasi Ayam Sayur"
                                   value="{{ old('nama_menu', $menuHarian->nama_menu) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"
                                      placeholder="Opsional...">{{ old('catatan', $menuHarian->catatan) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kelompok Penerima</label>
                            <select name="kelompok" class="form-select">
                                <option value="balita_sd3"
                                    {{ ($menuHarian->kelompok ?? 'sd4_ibu_menyusui') === 'balita_sd3' ? 'selected' : '' }}>
                                    <i class="fas fa-child"></i> Balita s/d Kelas 3 SD
                                </option>
                                <option value="sd4_ibu_menyusui"
                                    {{ ($menuHarian->kelompok ?? 'sd4_ibu_menyusui') === 'sd4_ibu_menyusui' ? 'selected' : '' }}>
                                    Kelas 4 SD s/d Ibu Menyusui
                                </option>
                            </select>
                            <div class="form-text">Mempengaruhi batas anggaran per porsi.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" selected>
                                    Draft (bisa diedit)
                                </option>
                            </select>
                            <div class="form-text">Finalisasi dilakukan lewat tombol "Finalisasi" di halaman detail menu.</div>
                        </div>

                        {{-- Estimasi gizi --}}
                        <div class="card border-0" style="background:var(--primary-pale)">
                            <div class="card-body p-3">
                                <div class="fw-semibold small mb-2" style="color:var(--primary)">
                                    <i class="fas fa-calculator me-1"></i>Estimasi Gizi (Makan Siang)
                                </div>
                                <div class="row g-1 text-center">
                                    <div class="col-6">
                                        <div class="bg-white rounded p-2">
                                            <div class="fw-bold fs-5" id="sum-energi">0</div>
                                            <div class="text-muted" style="font-size:.7rem">Energi (kkal)</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white rounded p-2">
                                            <div class="fw-bold fs-5" id="sum-protein">0</div>
                                            <div class="text-muted" style="font-size:.7rem">Protein (g)</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-1">
                                        <div class="bg-white rounded p-2">
                                            <div class="fw-bold fs-5" id="sum-lemak">0</div>
                                            <div class="text-muted" style="font-size:.7rem">Lemak (g)</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-1">
                                        <div class="bg-white rounded p-2">
                                            <div class="fw-bold fs-5" id="sum-karbo">0</div>
                                            <div class="text-muted" style="font-size:.7rem">Karbo (g)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-grid gap-2">
                        <button type="submit" class="btn btn-primary"
                                style="background:var(--primary);border-color:var(--primary)">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                        <a href="{{ route('menu-harian.show', $menuHarian) }}"
                           class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>

            {{-- Kolom kanan: bahan --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center"
                         style="background:#daeeff">
                        <span class="fw-semibold" style="color:#0f4c81">
                            <i class="fas fa-cloud-sun me-2"></i>Bahan Pangan — Makan Siang
                        </span>
                        <span class="text-muted small" id="jumlah-bahan-label">0 bahan</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-2 px-1">
                            <div class="col-md-5"><small class="fw-semibold text-muted">Nama Bahan (TKPI)</small></div>
                            <div class="col-md-2"><small class="fw-semibold text-muted">Gram/Porsi</small></div>
                            <div class="col-md-2"><small class="fw-semibold text-muted">Jml Porsi</small></div>
                            <div class="col-md-2"><small class="fw-semibold text-muted">Energi</small></div>
                            <div class="col-md-1"></div>
                        </div>
                        <div id="bahan-list"></div>
                        <button type="button" id="btn-tambah-bahan"
                                class="btn btn-outline-primary btn-sm mt-3"
                                style="border-color:var(--primary);color:var(--primary)">
                            <i class="fas fa-plus me-1"></i> Tambah Bahan
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Template sama persis dengan create --}}
<template id="tpl-bahan">
    <div class="bahan-row" data-idx="">
        <div class="row g-2 align-items-center">
            <div class="col-md-5 position-relative">
                <input type="text" class="form-control form-control-sm bahan-search"
                       placeholder="Cari nama / kode TKPI..." autocomplete="off">
                <input type="hidden" class="bahan-id">
                <input type="hidden" class="bahan-energi" value="0">
                <input type="hidden" class="bahan-protein" value="0">
                <input type="hidden" class="bahan-lemak" value="0">
                <input type="hidden" class="bahan-karbo" value="0">
                <input type="hidden" class="bahan-bdd" value="100">
                <div class="autocomplete-list d-none"></div>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control form-control-sm jumlah-gram"
                       placeholder="gram" min="1" step="0.1">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control form-control-sm jumlah-porsi"
                       placeholder="porsi" min="1" value="1">
            </div>
            <div class="col-md-2">
                <small class="energi-preview text-muted">— kkal</small>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="mt-1 ps-1">
            <small class="bahan-label text-muted fst-italic">Pilih bahan terlebih dahulu</small>
        </div>
    </div>
</template>

@push('scripts')
<script>
const existingBahans = @json($existingBahans);
const menuJumlahPorsi = {{ (int) $menuHarian->jumlah_porsi }};
const API_BAHAN_SEARCH_URL = @json(route('api.bahan-pangan.search'));

// ===== JS sama persis dengan create.blade.php =====
let bahanCounter = 0;
let nutrisiState = {};

// Escape data dari DB (nama_bahan/kode bisa berisi HTML) sebelum masuk innerHTML.
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function skeletonAutocompleteRows(n = 3) {
    return Array.from({ length: n }).map(() => `
        <div class="autocomplete-item">
            <span class="skeleton d-block" style="width:70%;height:12px"></span>
        </div>`).join('');
}

document.getElementById('btn-tambah-bahan').addEventListener('click', () => tambahBahan());

function tambahBahan(prefillData = null) {
    const tpl = document.getElementById('tpl-bahan');
    const clone = tpl.content.cloneNode(true);
    const idx = bahanCounter++;
    const row = clone.querySelector('.bahan-row');
    row.dataset.idx = idx;

    row.querySelector('.bahan-id').name    = `bahans[${idx}][bahan_pangan_id]`;
    row.querySelector('.jumlah-gram').name = `bahans[${idx}][jumlah_gram]`;
    row.querySelector('.jumlah-porsi').name= `bahans[${idx}][jumlah_porsi]`;

    const list = document.getElementById('bahan-list');

    clone.querySelector('.btn-hapus').addEventListener('click', function () {
        this.closest('.bahan-row').remove();
        delete nutrisiState[idx];
        updateGiziSummary();
        updateJumlahBahan();
    });

    const searchInput = clone.querySelector('.bahan-search');
    const acList = clone.querySelector('.autocomplete-list');
    let debounce;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { acList.classList.add('d-none'); return; }
        debounce = setTimeout(() => fetchBahan(q, acList, row, idx), 300);
    });

    document.addEventListener('click', e => {
        if (!row.contains(e.target)) acList.classList.add('d-none');
    });

    clone.querySelector('.jumlah-gram').addEventListener('input', () => recalcRow(row, idx));
    clone.querySelector('.jumlah-porsi').addEventListener('input', () => recalcRow(row, idx));

    list.appendChild(clone);
    updateJumlahBahan();

    if (prefillData) {
        pilihBahan(prefillData, row, idx, null);
        row.querySelector('.jumlah-gram').value  = prefillData.jumlah_gram;
        row.querySelector('.jumlah-porsi').value = prefillData.jumlah_porsi;
        recalcRow(row, idx);
    } else {
        // Bahan baru: default Jml Porsi ke jumlah_porsi menu ini, bukan hardcoded 1.
        row.querySelector('.jumlah-porsi').value = menuJumlahPorsi;
        searchInput.focus();
    }
}

async function fetchBahan(q, acList, row, idx) {
    acList.innerHTML = skeletonAutocompleteRows();
    acList.classList.remove('d-none');
    try {
        const res = await fetch(`${API_BAHAN_SEARCH_URL}?q=${encodeURIComponent(q)}&limit=8`);
        if (!res.ok) {
            acList.innerHTML = '<div class="autocomplete-item text-muted">Gagal memuat data.</div>';
            acList.classList.remove('d-none');
            return;
        }
        const data = await res.json();
        acList.innerHTML = '';
        if (!data.length) {
            acList.innerHTML = '<div class="autocomplete-item text-muted">Tidak ditemukan</div>';
        } else {
            data.forEach(b => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `<span class="kode">${escapeHtml(b.kode)}</span> — ${escapeHtml(b.nama_bahan)}
                    <span class="text-muted ms-2" style="font-size:.7rem">${escapeHtml(b.kategori)}</span>`;
                item.addEventListener('click', () => pilihBahan(b, row, idx, acList));
                acList.appendChild(item);
            });
        }
        acList.classList.remove('d-none');
    } catch (e) { console.error(e); }
}

function pilihBahan(b, row, idx, acList) {
    const bdd      = b.bdd      ?? 100;   // ← tambah fallback
    const energi   = b.energi   ?? 0;
    const protein  = b.protein  ?? 0;
    const lemak    = b.lemak    ?? 0;
    const karbo    = b.karbohidrat ?? 0;

    row.querySelector('.bahan-search').value = `${b.kode} — ${b.nama_bahan}`;
    row.querySelector('.bahan-id').value      = b.id;
    row.querySelector('.bahan-energi').value  = energi;
    row.querySelector('.bahan-protein').value = protein;
    row.querySelector('.bahan-lemak').value   = lemak;
    row.querySelector('.bahan-karbo').value   = karbo;
    row.querySelector('.bahan-bdd').value     = bdd;
    row.querySelector('.bahan-label').textContent =
        `${b.kategori} | Energi: ${energi} kkal | Protein: ${protein} g | BDD: ${bdd}%`;

    if (acList) acList.classList.add('d-none');
    recalcRow(row, idx);
}

function recalcRow(row, idx) {
    const gram  = parseFloat(row.querySelector('.jumlah-gram').value)  || 0;
    const porsi = parseInt(row.querySelector('.jumlah-porsi').value)   || 1;
    const bdd   = parseFloat(row.querySelector('.bahan-bdd').value)    || 100;
    const f     = (gram * (bdd / 100)) / 100;

    const e = f * (parseFloat(row.querySelector('.bahan-energi').value)  || 0) * porsi;
    const p = f * (parseFloat(row.querySelector('.bahan-protein').value) || 0) * porsi;
    const l = f * (parseFloat(row.querySelector('.bahan-lemak').value)   || 0) * porsi;
    const k = f * (parseFloat(row.querySelector('.bahan-karbo').value)   || 0) * porsi;

    nutrisiState[idx] = { e, p, l, k };
    row.querySelector('.energi-preview').textContent = `${e.toFixed(1)} kkal`;
    updateGiziSummary();
}

function updateGiziSummary() {
    let totE=0, totP=0, totL=0, totK=0;
    Object.values(nutrisiState).forEach(n => { totE+=n.e; totP+=n.p; totL+=n.l; totK+=n.k; });
    document.getElementById('sum-energi').textContent  = totE.toFixed(1);
    document.getElementById('sum-protein').textContent = totP.toFixed(1);
    document.getElementById('sum-lemak').textContent   = totL.toFixed(1);
    document.getElementById('sum-karbo').textContent   = totK.toFixed(1);
}

function updateJumlahBahan() {
    const n = document.querySelectorAll('.bahan-row').length;
    document.getElementById('jumlah-bahan-label').textContent = `${n} bahan`;
}

// ✅ AUTO-POPULATE data existing saat halaman edit dibuka
document.addEventListener('DOMContentLoaded', () => {
    existingBahans.forEach(b => tambahBahan(b));
});
</script>
@endpush
@endsection