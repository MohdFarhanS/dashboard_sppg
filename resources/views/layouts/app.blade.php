<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard SPPG')</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary:       #0f4c81;
            --primary-light: #0071e4;
            --primary-pale:  #daeeff;
            --sidebar-width: 260px;
            --navbar-height: 60px;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f0f4f9; }

        /* ── SIDEBAR ── */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #072a4e 0%, #0f4c81 60%, #0071e4 100%);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .3s ease;
            display: flex;
            flex-direction: column;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        #sidebar .sidebar-brand h5 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: .95rem;
            line-height: 1.3;
        }
        #sidebar .sidebar-brand small {
            color: rgba(255,255,255,.55);
            font-size: .72rem;
        }
        #sidebar .nav-section {
            padding: .75rem 1rem .25rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: rgba(255,255,255,.4);
            text-transform: uppercase;
        }
        #sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .55rem 1.25rem;
            border-radius: 8px;
            margin: .1rem .75rem;
            font-size: .85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .65rem;
            transition: all .2s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255,255,255,.15);
            color: #fff;
        }
        #sidebar .nav-link.active {
            background: rgba(255,255,255,.2);
            font-weight: 600;
        }
        #sidebar .nav-link i { width: 18px; text-align: center; }

        /* ── MAIN CONTENT ── */
        #main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        #topnav {
            height: var(--navbar-height);
            background: #fff;
            border-bottom: 1px solid #dde8f0;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
            gap: 1rem;
        }
        #topnav .page-title {
            font-weight: 700;
            font-size: 1rem;
            color: #0d2545;
        }
        #topnav .topnav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .avatar {
            width: 36px; height: 36px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: .85rem;
        }

        /* ── PAGE CONTENT ── */
        .page-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* ── CARDS ── */
        .stat-card {
            border: none;
            border-radius: 14px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            background: #fff;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-card .stat-label {
            font-size: .75rem;
            color: #6b8ba4;
            font-weight: 500;
            margin-bottom: .15rem;
        }
        .stat-card .stat-value {
            font-size: 1.45rem;
            font-weight: 700;
            color: #0d2545;
            line-height: 1;
        }
        .stat-card .stat-sub {
            font-size: .72rem;
            color: #adb5bd;
            margin-top: .2rem;
        }

        /* ── PROGRESS GIZI ── */
        .progress { height: 10px; border-radius: 6px; }
        .card-mbg {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .card-mbg .card-header {
            background: transparent;
            border-bottom: 1px solid #e4eef8;
            font-weight: 700;
            font-size: .9rem;
            color: #0d2545;
            padding: 1rem 1.25rem .75rem;
        }

        /* ── BUDGET BADGE ── */
        .badge-budget-aman    { background: #daeeff; color: #0f4c81; }
        .badge-budget-warning { background: #fff8e1; color: #f57c00; }
        .badge-budget-over    { background: #fce4e4; color: #c62828; }

        /* ── SKELETON LOADING ── */
        .skeleton {
            display: inline-block;
            background: linear-gradient(90deg, #e9ecef 25%, #f4f6f8 37%, #e9ecef 63%);
            background-size: 400% 100%;
            animation: skeleton-shimmer 1.4s ease infinite;
            border-radius: 4px;
        }
        @keyframes skeleton-shimmer {
            0%   { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-wrapper { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- SIDEBAR --}}
@include('partials.sidebar')

{{-- OVERLAY mobile --}}
<div id="sidebarOverlay" onclick="closeSidebar()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:999;"></div>

{{-- MAIN WRAPPER --}}
<div id="main-wrapper">

    {{-- NAVBAR --}}
    @include('partials.navbar')

    {{-- PAGE CONTENT --}}
    <div class="page-content">
        @yield('content')
    </div>

    {{-- FOOTER --}}
    <footer class="text-center py-3" style="font-size:.75rem; color:#adb5bd;">
        &copy; {{ date('Y') }} Dashboard SPPG — Monitoring System
    </footer>
</div>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').style.display = 'none';
    }
    function toggleSidebar() {
        const s = document.getElementById('sidebar');
        const o = document.getElementById('sidebarOverlay');
        const open = s.classList.toggle('show');
        o.style.display = open ? 'block' : 'none';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" integrity="sha384-JUh163oCRItcbPme8pYnROHQMC6fNKTBWtRG3I3I0erJkzNgL7uxKlNwcrcFKeqF" crossorigin="anonymous"></script>

@stack('scripts')
</body>
</html>