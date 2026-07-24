@extends('errors.minimal')
@section('title', 'Permintaan Tidak Valid')

@section('content')
<div class="mb-4">
    <i class="fa fa-circle-exclamation fa-4x" style="color:#0f4c81; opacity:.4"></i>
</div>
<h3 class="fw-bold" style="color:#0f4c81">{{ $exception->getStatusCode() }} — Permintaan Tidak Valid</h3>
<p class="text-muted mb-4">
    {{ $exception->getMessage() ?: 'Permintaan Anda tidak dapat diproses.' }}
</p>
<a href="{{ Auth::check() ? route('dashboard') : route('landing') }}" class="btn btn-primary">
    <i class="fa fa-home me-1"></i>{{ Auth::check() ? 'Ke Dashboard' : 'Ke Beranda' }}
</a>
@endsection
