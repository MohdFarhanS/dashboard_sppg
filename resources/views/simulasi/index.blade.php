@extends('layouts.app')
@section('title', 'Simulasi Menu')

@push('styles')
<style>
    .bahan-row {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 8px;
        position: relative;
        border: 1px solid #e9ecef;
        transition: border-color .2s;
    }
    .bahan-row:hover { border-color: #7db8e8; }

    .autocomplete-list {
        position: absolute;
        z-index: 1050;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,.12);
        max-height: 240px;
        overflow-y: auto;
        width: 100%;
        left: 0;
        top: calc(100% + 4px);
    }
    .autocomplete-item {
        padding: 9px 14px;
        cursor: pointer;
        font-size: .855rem;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover { background: #daeeff; }
    .autocomplete-item .kode {
        color: var(--primary);
        font-size: .72rem;
        font-weight: 700;
        font-family: monospace;
        background: #daeeff;
        padding: 2px 6px;
        border-radius: 4px;
        flex-shrink: 0;
    }

    /* Panel kanan sticky */
    .panel-kanan { position: sticky; top: 76px; }

    /* Progress gizi */
    .progress-gizi { height: 8px; border-radius: 4px; }
    .bar-kurang  { background: #dc3545; }
    .bar-cukup   { background: #0071e4; }
    .bar-lebih   { background: #ffc107; }

    /* Status badge gizi */
    .badge-kurang { background:#fff3cd; color:#856404; border:1px solid #ffc107; }
    .badge-cukup  { background:#daeeff; color:#0f4c81; border:1px solid #0071e4; }
    .badge-lebih  { background:#f8d7da; color:#842029; border:1px solid #dc3545; }

    /* Tabel detail */
    #tabel-detail { font-size:.82rem; }

    /* Budget bar */
    .budget-bar-wrap { background:#e9ecef; height:10px; border-radius:5px; overflow:hidden; }
    .budget-bar-fill { height:100%; border-radius:5px; transition: width .4s ease; }

    /* Animasi panel */
    #panel-hasil { transition: opacity .3s; }
    #panel-hasil.loading { opacity: .5; pointer-events:none; }

    /* Nomor langkah */
    .step-badge {
        width:28px; height:28px; border-radius:50%;
        background:var(--primary); color:#fff;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:.8rem; font-weight:700; flex-shrink:0;
    }

    /* Stacked bar */
    .chart-bar-segment { height: 100%; transition: width .4s ease; }
    .chart-bar-segment:first-child { border-radius: 5px 0 0 5px; }
    .chart-bar-segment:last-child  { border-radius: 0 5px 5px 0; }
    .chart-bar-segment:only-child  { border-radius: 5px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-3">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="fas fa-flask me-2"></i>Simulasi Menu
            </h4>
            <small class="text-muted">Rakit kombinasi bahan pangan dan lihat estimasi gizi secara real-time</small>
        </div>
        <a href="{{ route('menu-harian.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="row g-4">

        {{-- ═══ KOLOM KIRI: Input ══════════════════════════════════════════════ --}}
        <div class="col-xl-7 col-lg-6">

            {{-- Langkah 1: Pengaturan --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header border-0 py-2" style="background:var(--primary-pale)">
                    <span class="fw-semibold" style="color:var(--primary)">
                        <span class="step-badge me-2">1</span>Pengaturan Menu
                    </span>
                </div>
                <div class="card-body pb-3">
                    @isset($menuHarian)
                    <div class="alert alert-info py-2 mb-3 d-flex align-items-center gap-2" style="font-size:.85rem">
                        <i class="fas fa-pen-to-square"></i>
                        Mode Edit — <strong>{{ $menuHarian->tanggal->translatedFormat('d F Y') }}</strong>
                        <a href="{{ route('menu-harian.show', $menuHarian) }}"
                           class="btn btn-sm btn-outline-secondary ms-auto py-0">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                    </div>
                    @endisset
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Tanggal</label>
                            <input type="date" id="sim-tanggal" class="form-control form-control-sm"
                                   value="{{ isset($menuHarian) ? $menuHarian->tanggal->format('Y-m-d') : today()->format('Y-m-d') }}"
                                   @isset($menuHarian) readonly style="background:#f8f9fa" @endisset>
                            @isset($menuHarian)
                            <div class="form-text"><i class="fas fa-lock fa-xs me-1"></i>Tanggal tidak dapat diubah.</div>
                            @endisset
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Jumlah Porsi</label>
                            <input type="number" id="sim-porsi" class="form-control form-control-sm"
                                   value="{{ isset($menuHarian) ? $menuHarian->jumlah_porsi : 1 }}"
                                   min="1" max="9999">
                            <div class="form-text">Jumlah penerima manfaat</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Nama Menu <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" id="sim-nama-menu" class="form-control form-control-sm"
                                   placeholder="cth. Nasi Ayam Sayur"
                                   value="{{ isset($menuHarian) ? ($menuHarian->nama_menu ?? '') : '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Langkah 2: Bahan --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center"
                     style="background:#daeeff">
                    <span class="fw-semibold" style="color:#0f4c81">
                        <span class="step-badge me-2">2</span>Bahan Pangan (TKPI)
                    </span>
                    <span class="text-muted small" id="label-jml-bahan">0 bahan</span>
                </div>
                <div class="card-body pb-3">
                    {{-- Header kolom --}}
                    <div class="row g-2 mb-2 px-1 d-none d-md-flex">
                        <div class="col-md-5"><small class="fw-semibold text-muted">Nama Bahan</small></div>
                        <div class="col-md-2"><small class="fw-semibold text-muted">Gram/sajian</small></div>
                        <div class="col-md-3"><small class="fw-semibold text-muted">Jumlah sajian</small></div>
                        <div class="col-md-2"></div>
                    </div>

                    <div id="bahan-list">
                        <div class="text-center text-muted py-4" id="empty-msg">
                            <i class="fas fa-carrot fa-2x mb-2 d-block opacity-25"></i>
                            Belum ada bahan — klik tombol di bawah untuk mulai
                        </div>
                    </div>

                    <button type="button" id="btn-tambah"
                            class="btn btn-outline-primary btn-sm mt-2"
                            style="border-color:var(--primary);color:var(--primary)">
                        <i class="fas fa-plus me-1"></i>Tambah Bahan
                    </button>
                </div>
                <div class="card-footer border-top bg-white py-2">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <button type="button" id="btn-hitung"
                                class="btn btn-primary btn-sm px-3"
                                style="background:var(--primary);border-color:var(--primary)" disabled>
                            <i class="fas fa-calculator me-1"></i>Hitung Estimasi
                        </button>
                        <button type="button" id="btn-reset" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-redo me-1"></i>Reset
                        </button>
                        <small class="text-muted" id="hint-hitung">Tambahkan minimal 1 bahan terlebih dahulu</small>
                    </div>
                </div>
            </div>

            {{-- Tabel Detail Bahan (muncul setelah hitung) --}}
            <div class="card border-0 shadow-sm d-none" id="card-detail">
                <div class="card-header border-0 py-2" style="background:var(--primary-pale)">
                    <span class="fw-semibold" style="color:var(--primary)">
                        <i class="fas fa-table me-2"></i>Detail Perhitungan per Bahan
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="tabel-detail">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Bahan</th>
                                    <th class="text-end">Gram</th>
                                    <th class="text-end">Sajian</th>
                                    <th class="text-end">% Energi</th>
                                    <th class="text-center pe-3">Status BDD</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detail"></tbody>
                            <tfoot id="tfoot-detail" style="background:var(--primary-pale)"></tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══ KOLOM KANAN: Hasil ══════════════════════════════════════════════ --}}
        <div class="col-xl-5 col-lg-6">
            <div class="panel-kanan">

                {{-- Placeholder sebelum hitung --}}
                <div class="card border-0 shadow-sm mb-3" id="panel-placeholder">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="fas fa-flask fa-3x mb-3 d-block opacity-15"></i>
                        <div class="fw-semibold">Hasil simulasi akan muncul di sini</div>
                        <small>Tambahkan bahan dan klik "Hitung Estimasi"</small>
                    </div>
                </div>

                {{-- Skeleton saat kalkulasi pertama kali (tersembunyi dulu) --}}
                <div id="panel-skeleton" class="d-none">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header border-0 py-2" style="background:var(--primary-pale)">
                            <span class="skeleton" style="width:200px;height:14px"></span>
                        </div>
                        <div class="card-body pb-2">
                            @for ($i = 0; $i < 4; $i++)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="skeleton" style="width:100px;height:10px"></span>
                                    <span class="skeleton" style="width:50px;height:10px"></span>
                                </div>
                                <span class="skeleton d-block" style="width:100%;height:8px"></span>
                            </div>
                            @endfor
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <span class="skeleton d-block mb-2" style="width:60%;height:12px"></span>
                            <span class="skeleton d-block" style="width:100%;height:34px;border-radius:8px"></span>
                        </div>
                    </div>
                </div>

                {{-- Panel hasil (tersembunyi dulu) --}}
                <div id="panel-hasil" class="d-none">

                    {{-- Ringkasan Gizi vs AKG --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center"
                             style="background:var(--primary-pale)">
                            <span class="fw-semibold small" style="color:var(--primary)">
                                <i class="fas fa-heart-pulse me-1"></i>Pemenuhan Gizi vs AKG Makan Siang
                            </span>
                            <span id="badge-gizi-summary"></span>
                        </div>
                        <div class="card-body pb-2">
                            <div id="gizi-list">
                                {{-- Di-render JS --}}
                            </div>
                        </div>
                    </div>

                    {{-- Kelompok Penerima --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header border-0 py-2" style="background:#daeeff">
                            <span class="fw-semibold small" style="color:#0f4c81">
                                <i class="fas fa-layer-group me-1"></i>Kelompok Penerima
                            </span>
                        </div>
                        <div class="card-body pb-3">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Grup Sasaran</label>
                                <select id="select-grup" class="form-select form-select-sm">
                                    @foreach(\App\Constants\AKG::cascadeOptions() as $grup => $keys)
                                    <option value="{{ $grup }}">{{ $grup }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label small fw-semibold">Kelompok Spesifik</label>
                                <select id="select-kelompok" class="form-select form-select-sm">
                                    {{-- Diisi oleh JavaScript --}}
                                </select>
                                <div class="form-text" id="label-akg-info"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Simpan --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold small mb-2">
                                <span class="step-badge me-1">3</span>
                                @isset($menuHarian)
                                    Perbarui Menu Harian
                                @else
                                    Simpan sebagai Menu Harian (Draft)
                                @endisset
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">
                                    Kelompok Penerima <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="sim-catatan" class="form-control form-control-sm"
                                       placeholder="cth. SD Negeri 01 Kelas 5A"
                                       value="{{ isset($menuHarian) ? ($menuHarian->catatan_anggaran ?? '') : '' }}">
                                <div class="invalid-feedback">Kelompok penerima wajib diisi.</div>
                                <div class="form-text">Tulis nama sekolah/kelompok penerima menu ini.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btn-simpan" class="btn btn-primary flex-fill"
                                        style="background:var(--primary);border-color:var(--primary)">
                                    @isset($menuHarian)
                                        <i class="fas fa-sync me-1"></i>Perbarui Menu
                                    @else
                                        <i class="fas fa-save me-1"></i>Simpan ke Menu Harian
                                    @endisset
                                </button>
                            </div>
                            <div class="alert alert-success mt-2 d-none py-2" id="alert-simpan"></div>
                            <div class="alert alert-danger mt-2 d-none py-2" id="alert-error"></div>
                        </div>
                    </div>

                </div>{{-- /panel-hasil --}}
            </div>
        </div>

    </div>
</div>

{{-- Template bahan-row --}}
<template id="tpl-bahan">
    <div class="bahan-row" data-idx="">
        <div class="row g-2 align-items-center">
            <div class="col-md-5 position-relative">
                <input type="text" class="form-control form-control-sm bahan-search"
                       placeholder="Ketik nama / kode TKPI…" autocomplete="off">
                <input type="hidden" class="bahan-id">
                <input type="hidden" class="bahan-energi"   value="0">
                <input type="hidden" class="bahan-protein"  value="0">
                <input type="hidden" class="bahan-lemak"    value="0">
                <input type="hidden" class="bahan-karbo"    value="0">
                <input type="hidden" class="bahan-bdd"      value="100">
                <input type="hidden" class="bahan-harga"    value="0">
                <div class="autocomplete-list d-none"></div>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control form-control-sm input-gram"
                       placeholder="gram" min="1" step="0.1">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control form-control-sm input-sajian"
                       placeholder="sajian" min="1" value="1">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus py-0">
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
// ═══════════════════════════════════════════════════════════════════════════════
// DATA AKG (dari PHP)
// ═══════════════════════════════════════════════════════════════════════════════
const AKG_SIANG    = @json(\App\Constants\AKG::MAKAN_SIANG);
const AKG_LABEL    = @json(\App\Constants\AKG::LABEL);
const AKG_KELOMPOK = @json(\App\Constants\AKG::KELOMPOK);
const API_BAHAN_SEARCH_URL = @json(route('api.bahan-pangan.search'));
let   akgTarget    = null;

// ═══════════════════════════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════════════════════════
let bahanCounter   = 0;
let hasilKalkulasi = null;   // simpan hasil AJAX terakhir

// Escape data dari DB (nama_bahan/kode bisa berisi HTML) sebelum masuk innerHTML.
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function skeletonAutocompleteRows(n = 3) {
    return Array.from({ length: n }).map(() => `
        <div class="autocomplete-item">
            <span class="skeleton" style="width:40px;height:16px"></span>
            <span class="skeleton flex-grow-1" style="height:12px"></span>
        </div>`).join('');
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAMBAH / HAPUS BAHAN
// ═══════════════════════════════════════════════════════════════════════════════
document.getElementById('btn-tambah').addEventListener('click', () => tambahBahan());

function tambahBahan(skipFocus = false) {
    const tpl  = document.getElementById('tpl-bahan');
    const clone = tpl.content.cloneNode(true);
    const idx   = bahanCounter++;
    const list  = document.getElementById('bahan-list');

    document.getElementById('empty-msg')?.remove();

    list.appendChild(clone);
    const row = list.lastElementChild;
    row.dataset.idx = idx;

    // Hapus bahan
    row.querySelector('.btn-hapus').addEventListener('click', function () {
        this.closest('.bahan-row').remove();
        if (!document.querySelector('.bahan-row')) {
            list.innerHTML =
                `<div class="text-center text-muted py-4" id="empty-msg">
                    <i class="fas fa-carrot fa-2x mb-2 d-block opacity-25"></i>
                    Belum ada bahan — klik tombol di bawah untuk mulai
                </div>`;
        }
        updateCountAndBtn();
    });

    // Autocomplete
    const searchInput = row.querySelector('.bahan-search');
    const acList      = row.querySelector('.autocomplete-list');
    let debounce;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { acList.classList.add('d-none'); return; }
        debounce = setTimeout(() => fetchBahan(q, acList, row), 280);
    });

    document.addEventListener('click', e => {
        if (!row.contains(e.target)) acList.classList.add('d-none');
    });

    row.querySelector('.input-gram').addEventListener('input',   () => recalcRow(row));
    row.querySelector('.input-sajian').addEventListener('input', () => recalcRow(row));

    updateCountAndBtn();
    if (!skipFocus) searchInput.focus();
}

async function fetchBahan(q, acList, row) {
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
                item.innerHTML =
                    `<span class="kode">${escapeHtml(b.kode)}</span>
                     <span class="flex-grow-1">${escapeHtml(b.nama_bahan)}</span>
                     <small class="text-muted">${escapeHtml(b.kategori)}</small>`;
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    pilihBahan(b, row, acList);
                });
                acList.appendChild(item);
            });
        }
        acList.classList.remove('d-none');
    } catch (e) { console.error(e); }
}

function pilihBahan(b, row, acList) {
    const bdd = b.bdd ?? 100;
    row.querySelector('.bahan-search').value  = `${b.kode} — ${b.nama_bahan}`;
    row.querySelector('.bahan-id').value       = b.id;
    row.querySelector('.bahan-energi').value   = b.energi        ?? 0;
    row.querySelector('.bahan-protein').value  = b.protein       ?? 0;
    row.querySelector('.bahan-lemak').value    = b.lemak         ?? 0;
    row.querySelector('.bahan-karbo').value    = b.karbohidrat   ?? 0;
    row.querySelector('.bahan-bdd').value      = bdd;
    row.querySelector('.bahan-harga').value    = b.harga_per_100g ?? 0;
    row.querySelector('.bahan-label').textContent =
        `${b.kategori} · Energi: ${b.energi ?? 0} kkal/100g · BDD: ${bdd}%`;
    if (acList) acList.classList.add('d-none');
    recalcRow(row);
    updateCountAndBtn();
}

function recalcRow(row) {
    updateCountAndBtn();
}

function updateCountAndBtn() {
    const rows = document.querySelectorAll('.bahan-row');
    document.getElementById('label-jml-bahan').textContent = `${rows.length} bahan`;

    const hasValid = Array.from(rows).some(r =>
        r.querySelector('.bahan-id').value &&
        parseFloat(r.querySelector('.input-gram').value) > 0
    );
    const btn = document.getElementById('btn-hitung');
    btn.disabled = !hasValid;
    document.getElementById('hint-hitung').textContent =
        hasValid ? '' : 'Tambahkan minimal 1 bahan dengan gram > 0';
}

// ═══════════════════════════════════════════════════════════════════════════════
// KALKULASI (AJAX)
// ═══════════════════════════════════════════════════════════════════════════════
document.getElementById('btn-hitung').addEventListener('click', async () => {
    const rows   = document.querySelectorAll('.bahan-row');
    const bahans = [];

    for (const row of rows) {
        const id   = row.querySelector('.bahan-id').value;
        const gram = parseFloat(row.querySelector('.input-gram').value) || 0;
        const sajian = parseInt(row.querySelector('.input-sajian').value) || 1;
        if (!id || gram <= 0) continue;
        bahans.push({ id, gram, porsi: sajian });
    }

    if (!bahans.length) return;

    const payload = {
        bahans,
        jumlah_porsi : parseInt(document.getElementById('sim-porsi').value) || 1,
        tanggal      : document.getElementById('sim-tanggal').value,
        kelompok     : getSelectedKelompok(),
        _token       : document.querySelector('meta[name="csrf-token"]')?.content
                        || '{{ csrf_token() }}',
    };

    // UI loading — panggilan pertama tampilkan skeleton, panggilan berikutnya redupkan panel yang ada
    const isFirstCalc = document.getElementById('panel-hasil').classList.contains('d-none');
    if (isFirstCalc) {
        document.getElementById('panel-placeholder').classList.add('d-none');
        document.getElementById('panel-skeleton').classList.remove('d-none');
    } else {
        document.getElementById('panel-hasil').classList.add('loading');
    }

    try {
        const res  = await fetch('{{ route("simulasi.kalkulasi") }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                       'X-CSRF-TOKEN': payload._token },
            body   : JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
            alert(data.message || 'Terjadi kesalahan.');
            return;
        }

        hasilKalkulasi = data;
        renderHasil(data);

    } catch (e) {
        console.error(e);
        alert('Gagal menghubungi server.');
    } finally {
        document.getElementById('panel-hasil').classList.remove('loading');
        document.getElementById('panel-skeleton').classList.add('d-none');
        if (document.getElementById('panel-hasil').classList.contains('d-none')) {
            document.getElementById('panel-placeholder').classList.remove('d-none');
        }
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// RENDER HASIL
// ═══════════════════════════════════════════════════════════════════════════════
function renderHasil(data) {
    akgTarget = data.akg_target || null;

    // Tampilkan panel
    document.getElementById('panel-placeholder').classList.add('d-none');
    document.getElementById('panel-hasil').classList.remove('d-none');
    document.getElementById('card-detail').classList.remove('d-none');

    renderGizi(data.gizi, data.persen_akg);
    renderBiaya(data.biaya);
    renderDetail(data.detail);
}

function renderGizi(gizi, persenAkg) {
    const keys   = ['energi','protein','lemak','karbohidrat','serat','kalsium','besi','vit_c'];
    let html     = '';
    let kurangCount = 0;

    keys.forEach(k => {
        const pct  = persenAkg[k] || 0;
        const info = AKG_LABEL[k] || { label: k, satuan: '', icon: 'fa-circle' };
        const cls  = pct < 70 ? 'kurang' : pct > 130 ? 'lebih' : 'cukup';
        const barW = Math.min(pct, 100);
        if (cls === 'kurang') kurangCount++;
        html += `
        <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span style="font-size:.8rem;font-weight:600">
                    <i class="fas ${info.icon} me-1" style="color:var(--primary);font-size:.7rem"></i>
                    ${info.label}
                </span>
                <div class="d-flex align-items-center gap-1">
                    <span style="font-size:.78rem;color:#7a9280">
                        ${(gizi[k]||0).toFixed(1)} / ${akgTarget?.[k] ?? AKG_SIANG[k]} ${info.satuan}
                    </span>
                    <span class="badge badge-${cls}" style="font-size:.65rem">${pct}%</span>
                </div>
            </div>
            <div class="progress-gizi" style="background:#e9ecef;border-radius:4px;height:8px">
                <div style="width:${barW}%;height:8px;border-radius:4px;background:${cls==='kurang'?'#dc3545':cls==='lebih'?'#ffc107':'#0071e4'};
                            transition:width .4s ease"></div>
            </div>
        </div>`;
    });

    document.getElementById('gizi-list').innerHTML = html;

    // Badge ringkasan
    const badgeEl = document.getElementById('badge-gizi-summary');
    if (kurangCount === 0) {
        badgeEl.innerHTML =
            `<span class="badge" style="background:#daeeff;color:#0f4c81;font-size:.7rem">
                <i class="fas fa-check-circle me-1"></i>Gizi Lengkap ✓
            </span>`;
    } else {
        badgeEl.innerHTML =
            `<span class="badge" style="background:#fff3cd;color:#664d03;font-size:.7rem">
                <i class="fas fa-exclamation-circle me-1"></i>${kurangCount} zat gizi kurang
            </span>`;
    }
}

function renderBiaya(b) {
    // placeholder — data biaya tersedia di b jika dibutuhkan di masa depan
}

function renderDetail(detail) {
    // Hitung total energi untuk persentase
    let totE = 0;
    detail.forEach(d => { totE += d.gizi.energi; });

    let rowsHtml = '';
    detail.forEach(d => {
        const energi    = d.gizi.energi;
        const pctEnergi = totE > 0 ? (energi / totE * 100).toFixed(1) : '0.0';

        const bdd = d.bdd ?? 100;
        let bddBadge;
        if (bdd >= 90) {
            bddBadge = `<span class="badge" style="background:#daeeff;color:#0f4c81">
                            <i class="fas fa-check-circle me-1"></i>Efisien (${bdd}%)
                        </span>`;
        } else if (bdd >= 70) {
            bddBadge = `<span class="badge" style="background:#fff3cd;color:#664d03">
                            <i class="fas fa-exclamation-circle me-1"></i>Sedang (${bdd}%)
                        </span>`;
        } else {
            bddBadge = `<span class="badge" style="background:#f8d7da;color:#842029">
                            <i class="fas fa-times-circle me-1"></i>Boros (${bdd}%)
                        </span>`;
        }

        rowsHtml += `
        <tr>
            <td class="ps-3">
                <div class="fw-semibold">${escapeHtml(d.nama)}</div>
                <small class="text-muted">${escapeHtml(d.kategori)}</small>
            </td>
            <td class="text-end">${d.gram}g</td>
            <td class="text-end">${d.porsi}x</td>
            <td class="text-end fw-semibold" style="color:var(--primary)">${pctEnergi}%</td>
            <td class="text-center pe-3">${bddBadge}</td>
        </tr>`;
    });

    document.getElementById('tbody-detail').innerHTML = rowsHtml;
    document.getElementById('tfoot-detail').innerHTML = `
        <tr class="fw-semibold">
            <td class="ps-3" colspan="3">Total</td>
            <td class="text-end" style="color:var(--primary)">${totE.toFixed(1)} kkal</td>
            <td></td>
        </tr>`;
}

// ═══════════════════════════════════════════════════════════════════════════════
// SIMPAN KE MENU HARIAN
// ═══════════════════════════════════════════════════════════════════════════════
const isEditMode = {{ isset($menuHarian) ? 'true' : 'false' }};

document.getElementById('btn-simpan').addEventListener('click', async () => {
    if (!hasilKalkulasi) return;

    // Validasi kelompok penerima
    const catatanEl = document.getElementById('sim-catatan');
    if (!catatanEl.value.trim()) {
        catatanEl.classList.add('is-invalid');
        catatanEl.focus();
        return;
    }
    catatanEl.classList.remove('is-invalid');

    const rows   = document.querySelectorAll('.bahan-row');
    const bahans = [];
    for (const row of rows) {
        const id    = row.querySelector('.bahan-id').value;
        const gram  = parseFloat(row.querySelector('.input-gram').value) || 0;
        const sajian = parseInt(row.querySelector('.input-sajian').value) || 1;
        if (!id || gram <= 0) continue;
        bahans.push({ id, gram, porsi: sajian });
    }

    const payload = {
        tanggal      : document.getElementById('sim-tanggal').value,
        nama_menu    : document.getElementById('sim-nama-menu').value,
        catatan      : document.getElementById('sim-catatan').value,
        jumlah_porsi      : parseInt(document.getElementById('sim-porsi').value) || 1,
        kelompok_sasaran  : getSelectedKelompok(),
        bahans,
        _token       : '{{ csrf_token() }}',
        @isset($menuHarian)
        menu_id      : {{ $menuHarian->id }},
        @endisset
    };

    const btn = document.getElementById('btn-simpan');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

    try {
        const res  = await fetch('{{ route("simulasi.simpan") }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                       'X-CSRF-TOKEN': payload._token },
            body   : JSON.stringify(payload),
        });
        const data = await res.json();

        const successEl = document.getElementById('alert-simpan');
        const errorEl   = document.getElementById('alert-error');

        if (res.ok && data.success) {
            successEl.classList.remove('d-none');
            successEl.innerHTML = `<i class="fas fa-check-circle me-1"></i>${data.success} Mengalihkan…`;
            errorEl.classList.add('d-none');
            setTimeout(() => { window.location.href = data.redirect; }, 1200);
        } else {
            errorEl.classList.remove('d-none');
            errorEl.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${data.error || 'Gagal menyimpan.'}
                ${data.redirect ? `<a href="${data.redirect}" class="alert-link ms-2">Edit menu →</a>` : ''}`;
            successEl.classList.add('d-none');
            btn.disabled = false;
            btn.innerHTML = isEditMode
                ? '<i class="fas fa-sync me-1"></i>Perbarui Menu'
                : '<i class="fas fa-save me-1"></i>Simpan ke Menu Harian';
        }
    } catch (e) {
        console.error(e);
        alert('Gagal menghubungi server.');
        btn.disabled = false;
        btn.innerHTML = isEditMode
            ? '<i class="fas fa-sync me-1"></i>Perbarui Menu'
            : '<i class="fas fa-save me-1"></i>Simpan ke Menu Harian';
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// CASCADING DROPDOWN KELOMPOK
// ═══════════════════════════════════════════════════════════════════════════════
const cascadeOptions = @json(\App\Constants\AKG::cascadeOptions());
const kelompokAll    = @json(\App\Constants\AKG::KELOMPOK);

function populateSubGrup(grupName) {
    const keys = cascadeOptions[grupName] || [];
    const sel  = document.getElementById('select-kelompok');
    sel.innerHTML = keys.map(k =>
        `<option value="${k}">${kelompokAll[k]?.label ?? k}</option>`
    ).join('');
    updateAkgInfo();
    rerenderGiziForKelompok();
}

function updateAkgInfo() {
    const key    = document.getElementById('select-kelompok').value;
    const data   = kelompokAll[key];
    const infoEl = document.getElementById('label-akg-info');
    if (data && infoEl) {
        const e = (data.energi * 0.325).toFixed(0);
        const p = (data.protein * 0.325).toFixed(1);
        infoEl.textContent = `Target makan siang: ${e} kkal · ${p}g protein`;
    }
}

function rerenderGiziForKelompok() {
    if (!hasilKalkulasi) return;

    const key  = getSelectedKelompok();
    const data = kelompokAll[key];
    if (!data) return;

    const pct = 0.325;
    akgTarget = {
        energi      : data.energi      * pct,
        protein     : data.protein     * pct,
        lemak       : data.lemak       * pct,
        karbohidrat : data.karbohidrat * pct,
    };

    const gizi     = hasilKalkulasi.gizi;
    const persenAkg = {};
    for (const k of Object.keys(gizi)) {
        const t    = (akgTarget[k] ?? 0) > 0 ? akgTarget[k] : (AKG_SIANG[k] ?? 1);
        persenAkg[k] = t > 0 ? Math.round(gizi[k] / t * 1000) / 10 : 0;
    }

    renderGizi(gizi, persenAkg);
}

document.getElementById('select-grup').addEventListener('change', function () {
    populateSubGrup(this.value);
});
document.getElementById('select-kelompok').addEventListener('change', () => {
    updateAkgInfo();
    rerenderGiziForKelompok();
});

// Inisialisasi dropdown pertama kali
populateSubGrup(Object.keys(cascadeOptions)[0]);

function getSelectedKelompok() {
    return document.getElementById('select-kelompok').value || 'SD_4_6';
}

// ═══════════════════════════════════════════════════════════════════════════════
// PRE-FILL (mode edit — hanya dijalankan jika $menuHarian tersedia)
// ═══════════════════════════════════════════════════════════════════════════════
@isset($menuHarian)
document.addEventListener('DOMContentLoaded', () => {
    // Pre-select kelompok sesuai menu yang diedit
    const kelompokMenu = '{{ $menuHarian->kelompok_sasaran ?? 'SD_4_6' }}';
    for (const [grup, keys] of Object.entries(cascadeOptions)) {
        if (keys.includes(kelompokMenu)) {
            document.getElementById('select-grup').value = grup;
            populateSubGrup(grup);
            document.getElementById('select-kelompok').value = kelompokMenu;
            updateAkgInfo();
            break;
        }
    }

    const bahansEdit = @json($existingBahans);
    bahansEdit.forEach(b => {
        tambahBahan(true); // skipFocus agar tidak scroll ke tiap baris
        const list = document.getElementById('bahan-list');
        const row  = list.lastElementChild;
        pilihBahan({
            id             : b.id,
            kode           : b.kode,
            nama_bahan     : b.nama_bahan,
            kategori       : b.kategori,
            energi         : b.energi,
            protein        : b.protein,
            lemak          : b.lemak,
            karbohidrat    : b.karbohidrat,
            bdd            : b.bdd,
            harga_per_100g : b.harga_per_100g,
        }, row, null);
        row.querySelector('.input-gram').value   = b.jumlah_gram;
        row.querySelector('.input-sajian').value = b.jumlah_porsi;
        recalcRow(row);
    });
});
@endisset

// ═══════════════════════════════════════════════════════════════════════════════
// RESET
// ═══════════════════════════════════════════════════════════════════════════════
document.getElementById('sim-catatan').addEventListener('input', function () {
    if (this.value.trim()) this.classList.remove('is-invalid');
});

document.getElementById('btn-reset').addEventListener('click', () => {
    if (!confirm('Reset semua bahan dan hasil simulasi?')) return;
    document.getElementById('bahan-list').innerHTML =
        `<div class="text-center text-muted py-4" id="empty-msg">
            <i class="fas fa-carrot fa-2x mb-2 d-block opacity-25"></i>
            Belum ada bahan — klik tombol di bawah untuk mulai
        </div>`;
    document.getElementById('panel-skeleton').classList.add('d-none');
    document.getElementById('panel-placeholder').classList.remove('d-none');
    document.getElementById('panel-hasil').classList.add('d-none');
    document.getElementById('card-detail').classList.add('d-none');
    document.getElementById('sim-nama-menu').value = '';
    document.getElementById('sim-catatan').value   = '';
    document.getElementById('sim-porsi').value     = '1';
    document.getElementById('alert-simpan').classList.add('d-none');
    document.getElementById('alert-error').classList.add('d-none');
    hasilKalkulasi = null;
    bahanCounter   = 0;
    updateCountAndBtn();
});
</script>
@endpush
@endsection