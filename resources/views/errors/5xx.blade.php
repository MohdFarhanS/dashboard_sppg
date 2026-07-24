@extends('errors.minimal')
@section('title', 'Terjadi Kesalahan')

@section('content')
<div class="mb-4">
    <i class="fa fa-triangle-exclamation fa-4x" style="color:#c62828; opacity:.4"></i>
</div>
<h3 class="fw-bold text-danger">{{ $exception->getStatusCode() }} — Terjadi Kesalahan</h3>
<p class="text-muted mb-4">
    Terjadi kesalahan pada server. Tim kami sudah diberi tahu.<br>
    Silakan coba lagi beberapa saat lagi.
</p>
<a href="{{ Auth::check() ? route('dashboard') : route('landing') }}" class="btn btn-primary">
    <i class="fa fa-home me-1"></i>{{ Auth::check() ? 'Ke Dashboard' : 'Ke Beranda' }}
</a>
@endsection
