@extends('layouts.app')
@section('title', 'Manajemen User')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="fas fa-users me-2"></i>Manajemen User
            </h4>
            <small class="text-muted">Kelola akses pengguna sistem Dashboard SPPG</small>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Tambah User
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="ps-4 fw-semibold">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-secondary ms-1" style="font-size:.65rem">Anda</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            @php
                                $roleStyles = [
                                    'superadmin' => ['bg' => '#f0e6ff', 'color' => '#5b21b6', 'icon' => 'fa-shield-halved', 'label' => 'Super Admin'],
                                    'ketua_sppg' => ['bg' => '#cff4fc', 'color' => '#055160', 'icon' => 'fa-crown',         'label' => 'Ketua SPPG'],
                                    'ahli_gizi'  => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-heart-pulse',   'label' => 'Ahli Gizi'],
                                    'akuntan'    => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-calculator',    'label' => 'Akuntan'],
                                ];
                                $rs = $roleStyles[$user->role] ?? ['bg' => '#e5e7eb', 'color' => '#374151', 'icon' => 'fa-user', 'label' => $user->role];
                            @endphp
                            <span class="badge" style="background:{{ $rs['bg'] }};color:{{ $rs['color'] }}">
                                <i class="fas {{ $rs['icon'] }} me-1"></i>{{ $rs['label'] }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>

                                {{-- Reset Password --}}
                                <button type="button" class="btn btn-info btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalReset{{ $user->id }}"
                                        title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>

                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus user {{ addslashes($user->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            Belum ada user.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal Reset Password (di luar tabel agar markup valid) --}}
@foreach($users as $user)
<div class="modal fade" id="modalReset{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Reset Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('users.reset-password', $user) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Reset password untuk <strong>{{ $user->name }}</strong>
                    </p>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Password Baru</label>
                        <input type="password" name="password"
                               class="form-control form-control-sm"
                               placeholder="Min. 8 karakter" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Konfirmasi</label>
                        <input type="password" name="password_confirmation"
                               class="form-control form-control-sm"
                               placeholder="Ulangi password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm"
                            data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection