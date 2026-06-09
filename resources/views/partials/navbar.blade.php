<div id="topnav">
    {{-- Toggle mobile --}}
    <button onclick="toggleSidebar()" class="btn btn-sm d-md-none"
            style="color:#0f4c81; background:var(--primary-pale); border:none; border-radius:8px; padding:.4rem .65rem;">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Page title (diisi tiap halaman) --}}
    <span class="page-title">@yield('page-title', 'Dashboard')</span>

    {{-- Tanggal --}}
    <span class="d-none d-md-flex align-items-center"
          style="font-size:.78rem; color:#6b8ba4; background:var(--primary-pale);
                 padding:.3rem .85rem; border-radius:20px; gap:.4rem; margin-left:.5rem;">
        <i class="fas fa-calendar-day" style="color:var(--primary);"></i>
        <span id="tanggal-hari-ini"></span>
    </span>

    <div class="topnav-right">
        {{-- Notifikasi Bell --}}
        @php
            $canReceiveBudgetAlerts = auth()->check() && auth()->user()->hasAnyRole(['ketua_sppg', 'akuntan']);
            $budgetAlertCount = $canReceiveBudgetAlerts ? ($navAlertCount ?? 0) : 0;
            $budgetAlerts = $canReceiveBudgetAlerts ? ($navAlerts ?? []) : [];
            $totalNavBadge = $budgetAlertCount + ($pesanMasukCount ?? 0);
        @endphp
        <div class="dropdown">
            <button class="btn btn-sm position-relative dropdown-toggle"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    style="background:var(--primary-pale); border:none; border-radius:8px;
                        color:var(--primary); width:36px; height:36px; padding:0;">
                <i class="fas fa-bell"></i>
                @if($totalNavBadge > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size:.55rem; pointer-events:none;">
                    {{ $totalNavBadge }}
                </span>
                @endif
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0"
                style="border:none; border-radius:12px; min-width:310px; overflow:hidden;">

                {{-- Header --}}
                <li class="px-3 py-2 d-flex justify-content-between align-items-center"
                    style="background:var(--primary-pale); border-bottom:1px solid #dee2e6;">
                    <span style="font-size:.82rem; font-weight:700; color:var(--primary)">
                        <i class="fas fa-bell me-1"></i> Notifikasi
                    </span>
                    @if($totalNavBadge > 0)
                    <span class="badge bg-danger" style="font-size:.65rem;">
                        {{ $totalNavBadge }} baru
                    </span>
                    @endif
                </li>

                {{-- Pesan masuk (khusus ketua_sppg) --}}
                @if(isset($pesanMasukCount) && $pesanMasukCount > 0)
                <li>
                    <a href="{{ route('pesan-masuk.index') }}"
                       class="dropdown-item py-2 px-3"
                       style="border-bottom:1px solid #f5f5f5; white-space:normal;">
                        <div class="d-flex gap-2 align-items-start">
                            <span style="font-size:1rem; flex-shrink:0;">💬</span>
                            <div>
                                <div style="font-size:.8rem; font-weight:600; color:#0d2545;">
                                    {{ $pesanMasukCount }} pesan baru dari masyarakat
                                </div>
                                <div style="font-size:.7rem; color:#adb5bd;">Klik untuk melihat</div>
                            </div>
                        </div>
                    </a>
                </li>
                @endif

                {{-- Budget alerts --}}
                @if(count($budgetAlerts) > 0)
                    @foreach($budgetAlerts as $alert)
                    <li>
                        <a href="{{ route('budget-alert.index') }}"
                           class="dropdown-item py-2 px-3"
                           style="border-bottom:1px solid #f5f5f5; white-space:normal;">
                            <div class="d-flex gap-2 align-items-start">
                                <span style="font-size:1rem; flex-shrink:0;">
                                    {{ $alert['type'] === 'danger' ? '🚨' : '⚠️' }}
                                </span>
                                <div>
                                    <div style="font-size:.8rem; font-weight:500; color:#1a2e1d;">
                                        {{ $alert['msg'] }}
                                    </div>
                                    <div style="font-size:.7rem; color:#adb5bd;">
                                        {{ $alert['time'] }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endforeach
                @elseif(!isset($pesanMasukCount) || $pesanMasukCount === 0)
                    <li class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle text-primary d-block mb-1" style="font-size:1.3rem;"></i>
                        <span style="font-size:.8rem;">Tidak ada notifikasi baru</span>
                    </li>
                @endif

                {{-- Footer --}}
                @if($canReceiveBudgetAlerts)
                <li style="border-top:1px solid #f0f0f0;">
                    <a href="{{ route('budget-alert.index') }}"
                       class="dropdown-item text-center py-2"
                       style="font-size:.8rem; font-weight:600; color:var(--primary);">
                        Lihat Budget Alert →
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>

<script>
    // Tampilkan tanggal hari ini dalam Bahasa Indonesia
    const el = document.getElementById('tanggal-hari-ini');
    if (el) {
        el.textContent = new Date().toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    }
</script>
