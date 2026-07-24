<nav id="sidebar" style="display:flex; flex-direction:column; height:100vh; overflow:hidden;">
    {{-- Brand --}}
    <div class="sidebar-brand" style="flex-shrink:0;">
        <img src="{{ asset('images/logo_bgn.png') }}" alt="Logo" width="100">
        <h5>Dashboard SPPG</h5>
        <small>Monitoring Gizi & Biaya Produksi</small>
    </div>

    {{-- User info --}}
    <div style="flex-shrink:0; padding:.75rem 1.25rem; background:rgba(255,255,255,.07); margin:.75rem; border-radius:10px;">
        <div style="font-size:.68rem; color:rgba(255,255,255,.45); font-weight:600; text-transform:uppercase; letter-spacing:.06em;">
            {{ \App\Models\User::roleLabel(Auth::user()->role) }}
        </div>
        <div style="font-size:.82rem; color:#fff; font-weight:600; margin-top:.2rem;">
            {{ Auth::user()->name }}
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-grow-1 mt-1" style="overflow-y:auto; overflow-x:hidden; min-height:0;
        scrollbar-width:thin;
        scrollbar-color:rgba(255,255,255,.2) transparent;">

        @php $user = Auth::user(); @endphp

        {{-- ── Superadmin: hanya manajemen akun ─────────────────────── --}}
        @if($user->isSuperAdmin())
            <div class="nav-section">Administrasi</div>
            <a href="{{ route('users.index') }}"
               class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Kelola Pengguna
            </a>

        {{-- ── Ketua SPPG ─────────────────────────────────────────────── --}}
        @elseif($user->isKetuaSppg())
            <div class="nav-section">Menu Utama</div>
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>

            <div class="nav-section">Manajemen Data</div>
            <a href="{{ route('menu-harian.index') }}"
               class="nav-link {{ request()->routeIs('menu-harian.*') ? 'active' : '' }}">
                <i class="fas fa-utensils"></i> Menu Harian
            </a>
            <a href="{{ route('bahan-pangan.index') }}"
               class="nav-link {{ request()->routeIs('bahan-pangan.*') ? 'active' : '' }}">
                <i class="fas fa-carrot"></i> Bahan Pangan (TKPI)
            </a>
            <a href="{{ route('anggaran.index') }}"
               class="nav-link {{ request()->routeIs('anggaran.*') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i> Kelola Anggaran
            </a>

            <div class="nav-section">Monitoring</div>
            <a href="{{ route('biaya.dashboard') }}"
               class="nav-link {{ request()->routeIs('biaya.dashboard') || request()->routeIs('biaya.detail-menu') ? 'active' : '' }}">
                <i class="fas fa-coins"></i> Biaya Produksi
            </a>
            <a href="{{ route('biaya.harga.index') }}"
               class="nav-link {{ request()->routeIs('biaya.harga.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Harga Bahan
            </a>
            <a href="{{ route('budget-alert.index') }}"
               class="nav-link {{ request()->routeIs('budget-alert.*') ? 'active' : '' }}">
                <i class="fas fa-bell"></i> Budget Alert
                @if(isset($navAlertCount) && $navAlertCount > 0)
                <span class="badge ms-auto" style="background:rgba(255,100,100,.85); font-size:.65rem;">
                    {{ $navAlertCount }}
                </span>
                @endif
            </a>

            <div class="nav-section">Laporan</div>
            <a href="{{ route('laporan.index', ['jenis' => 'gizi']) }}"
               class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Laporan Gizi & Biaya
            </a>

            <div class="nav-section">Publik</div>
            <a href="{{ route('pesan-masuk.index') }}"
               class="nav-link {{ request()->routeIs('pesan-masuk.*') ? 'active' : '' }}">
                <i class="fas fa-envelope"></i> Pesan Masuk
                @if(isset($pesanMasukCount) && $pesanMasukCount > 0)
                <span class="badge ms-auto" style="background:rgba(0,113,228,.85); font-size:.65rem;">
                    {{ $pesanMasukCount }}
                </span>
                @endif
            </a>

        {{-- ── Ahli Gizi ─────────────────────────────────────────────── --}}
        @elseif($user->isAhliGizi())
            <div class="nav-section">Menu Utama</div>
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>

            <div class="nav-section">Input Menu</div>
            <a href="{{ route('menu-harian.index') }}"
               class="nav-link {{ request()->routeIs('menu-harian.*') ? 'active' : '' }}">
                <i class="fas fa-utensils"></i> Menu Harian
            </a>
            <a href="{{ route('bahan-pangan.index') }}"
               class="nav-link {{ request()->routeIs('bahan-pangan.*') ? 'active' : '' }}">
                <i class="fas fa-carrot"></i> Bahan Pangan (TKPI)
            </a>

            <div class="nav-section">Laporan</div>
            <a href="{{ route('laporan.index', ['jenis' => 'gizi']) }}"
               class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Laporan Gizi
            </a>

        {{-- ── Akuntan ───────────────────────────────────────────────── --}}
        @elseif($user->isAkuntan())
            <div class="nav-section">Menu Utama</div>
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>

            <div class="nav-section">Referensi</div>
            <a href="{{ route('bahan-pangan.index') }}"
               class="nav-link {{ request()->routeIs('bahan-pangan.*') ? 'active' : '' }}">
                <i class="fas fa-carrot"></i> Bahan Pangan (TKPI)
            </a>

            <div class="nav-section">Kelola Biaya</div>
            <a href="{{ route('biaya.harga.index') }}"
               class="nav-link {{ request()->routeIs('biaya.harga.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Harga Bahan
            </a>

            <div class="nav-section">Monitoring</div>
            <a href="{{ route('biaya.dashboard') }}"
               class="nav-link {{ request()->routeIs('biaya.dashboard') || request()->routeIs('biaya.detail-menu') ? 'active' : '' }}">
                <i class="fas fa-coins"></i> Biaya Produksi
            </a>
            <a href="{{ route('budget-alert.index') }}"
               class="nav-link {{ request()->routeIs('budget-alert.*') ? 'active' : '' }}">
                <i class="fas fa-bell"></i> Budget Alert
                @if(isset($navAlertCount) && $navAlertCount > 0)
                <span class="badge ms-auto" style="background:rgba(255,100,100,.85); font-size:.65rem;">
                    {{ $navAlertCount }}
                </span>
                @endif
            </a>

            <div class="nav-section">Laporan</div>
            <a href="{{ route('laporan.index', ['jenis' => 'biaya']) }}"
               class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Laporan Biaya
            </a>
        @endif

        <div style="height:.75rem;"></div>
    </nav>

    {{-- Logout --}}
    <div style="flex-shrink:0; padding:.75rem; border-top:1px solid rgba(255,255,255,.1);">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start"
                    style="color:rgba(255,255,255,.6);">
                <i class="fas fa-right-from-bracket"></i> Keluar
            </button>
        </form>
    </div>
</nav>
