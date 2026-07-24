@extends('errors.minimal')
@section('title', 'Halaman Tidak Ditemukan')

@section('content')
<div class="mb-4">
    <i class="fa fa-map-signs fa-4x" style="color:#0f4c81; opacity:.4"></i>
</div>
<h3 class="fw-bold" style="color:#0f4c81">404 — Halaman Tidak Ditemukan</h3>
<p class="text-muted mb-4">
    Halaman yang Anda cari tidak ada atau sudah dipindahkan.<br>
    Periksa kembali alamat URL yang dituju.
</p>
<a href="{{ Auth::check() ? route('dashboard') : route('landing') }}" class="btn btn-primary">
    <i class="fa fa-home me-1"></i>{{ Auth::check() ? 'Ke Dashboard' : 'Ke Beranda' }}
</a>
@endsection
