@extends('errors.minimal')
@section('title', 'Sesi Berakhir')

@section('content')
<div class="mb-4">
    <i class="fa fa-hourglass-end fa-4x" style="color:#0f4c81; opacity:.4"></i>
</div>
<h3 class="fw-bold" style="color:#0f4c81">419 — Sesi Berakhir</h3>
<p class="text-muted mb-4">
    Sesi Anda sudah kedaluwarsa, kemungkinan karena halaman terbuka terlalu lama.<br>
    Silakan muat ulang halaman dan coba lagi.
</p>
<a href="{{ url()->previous() }}" class="btn btn-primary">
    <i class="fa fa-rotate-right me-1"></i>Muat Ulang
</a>
@endsection
