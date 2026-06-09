<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPPG — Program Makan Bergizi Gratis</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary:       #0f4c81;
            --primary-light: #0071e4;
            --primary-pale:  #daeeff;
            --accent-green:  #00b894;
            --accent-orange: #f57c00;
            --dark:          #0d2545;
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        html { scroll-behavior: smooth; }
        body { background: #fff; overflow-x: hidden; }

        /* ── NAVBAR ── */
        #landingNav {
            background: transparent;
            transition: background .3s, box-shadow .3s;
            padding: .75rem 0;
        }
        #landingNav.scrolled {
            background: rgba(10,36,70,.97);
            box-shadow: 0 4px 20px rgba(0,0,0,.3);
        }
        #landingNav .navbar-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }
        #landingNav .navbar-brand:hover { color: #fff; }
        #landingNav .nav-link {
            color: rgba(255,255,255,.85) !important;
            font-weight: 500;
            font-size: .88rem;
            transition: color .2s;
        }
        #landingNav .nav-link:hover { color: #fff !important; }
        .btn-masuk {
            background: #fff;
            color: var(--primary) !important;
            font-weight: 700;
            font-size: .85rem;
            border-radius: 8px;
            padding: .45rem 1.2rem;
            transition: all .2s;
            border: 2px solid #fff;
        }
        .btn-masuk:hover {
            background: transparent;
            color: #fff !important;
        }
        .navbar-toggler {
            border: 1px solid rgba(255,255,255,.4);
            color: #fff;
        }
        .navbar-toggler-icon {
            filter: invert(1);
        }

        /* ── HERO ── */
        #hero {
            background: linear-gradient(135deg, #061e3c 0%, #0f4c81 45%, #0071e4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        #hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        #hero::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0,180,148,.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        #hero .container { position: relative; z-index: 1; }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            font-size: .8rem;
            font-weight: 600;
            padding: .35rem 1rem;
            border-radius: 50px;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.4rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 1.25rem;
        }
        .hero-title span { color: #7dd3fc; }
        .hero-desc {
            color: rgba(255,255,255,.75);
            font-size: 1.05rem;
            line-height: 1.75;
            margin-bottom: 2rem;
            max-width: 520px;
        }
        .btn-hero-primary {
            background: #fff;
            color: var(--primary);
            font-weight: 700;
            padding: .75rem 2rem;
            border-radius: 10px;
            border: none;
            font-size: .95rem;
            transition: all .25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .btn-hero-primary:hover {
            background: var(--primary-pale);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
        }
        .btn-hero-outline {
            background: transparent;
            color: #fff;
            font-weight: 600;
            padding: .73rem 1.75rem;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,.4);
            font-size: .95rem;
            transition: all .25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .btn-hero-outline:hover {
            border-color: #fff;
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        /* Hero dashboard mockup */
        .hero-mockup {
            position: relative;
            z-index: 1;
        }
        .mockup-frame {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 16px;
            padding: 1rem;
            backdrop-filter: blur(10px);
        }
        .mockup-bar {
            background: rgba(255,255,255,.12);
            border-radius: 8px;
            padding: .75rem 1rem;
            margin-bottom: .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .mockup-dot { width: 8px; height: 8px; border-radius: 50%; }
        .mockup-stat-row { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: .75rem; }
        .mockup-stat {
            background: rgba(255,255,255,.1);
            border-radius: 10px;
            padding: .75rem;
        }
        .mockup-stat-label { font-size: .65rem; color: rgba(255,255,255,.5); font-weight: 600; text-transform: uppercase; }
        .mockup-stat-val { font-size: 1.1rem; font-weight: 700; color: #fff; margin-top: .2rem; }
        .mockup-chart {
            background: rgba(255,255,255,.08);
            border-radius: 10px;
            padding: .75rem;
        }
        .mockup-bars { display: flex; align-items: flex-end; gap: .35rem; height: 55px; margin-top: .5rem; }
        .mockup-bar-item {
            flex: 1;
            border-radius: 4px 4px 0 0;
            opacity: .7;
        }
        .mockup-menu-list { background: rgba(255,255,255,.08); border-radius: 10px; padding: .65rem .75rem; }
        .mockup-menu-card {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .45rem 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .mockup-menu-card:last-child { border-bottom: none; }
        .mockup-menu-photo {
            width: 40px; height: 40px;
            border-radius: 7px;
            overflow: hidden;
            flex-shrink: 0;
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
        }
        .mockup-menu-photo img { width: 100%; height: 100%; object-fit: cover; }
        .mockup-menu-photo-placeholder { color: rgba(255,255,255,.3); font-size: .8rem; }
        .mockup-menu-info { flex: 1; min-width: 0; }
        .mockup-menu-day  { font-size: .58rem; color: rgba(255,255,255,.35); font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }
        .mockup-menu-name { font-size: .72rem; color: #fff; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .05rem; }
        .mockup-menu-date { font-size: .6rem; color: rgba(255,255,255,.4); margin-top: .08rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mockup-menu-badge { font-size: .58rem; color: rgba(100,220,150,.8); white-space: nowrap; flex-shrink: 0; }

        /* ── STATS STRIP ── */
        #stats {
            background: var(--dark);
            padding: 2.5rem 0;
        }
        .stat-item { text-align: center; }
        .stat-num {
            font-size: 2rem;
            font-weight: 800;
            color: #7dd3fc;
            line-height: 1;
        }
        .stat-lbl {
            font-size: .8rem;
            color: rgba(255,255,255,.55);
            margin-top: .3rem;
            font-weight: 500;
        }

        /* ── TENTANG ── */
        #tentang { padding: 5rem 0; background: #f8fafd; }
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: var(--primary-pale);
            color: var(--primary);
            font-size: .78rem;
            font-weight: 700;
            padding: .3rem .85rem;
            border-radius: 50px;
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            color: var(--dark);
            line-height: 1.25;
            margin-bottom: 1rem;
        }
        .section-desc {
            color: #6b8ba4;
            font-size: .95rem;
            line-height: 1.8;
        }
        .info-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.4rem 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            box-shadow: 0 2px 16px rgba(0,0,0,.05);
            height: 100%;
            transition: transform .2s, box-shadow .2s;
        }
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0,0,0,.09);
        }
        .info-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .info-card h6 {
            font-size: .88rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: .3rem;
        }
        .info-card p {
            font-size: .8rem;
            color: #6b8ba4;
            margin: 0;
            line-height: 1.6;
        }

        /* ── FITUR ── */
        #fitur { padding: 5rem 0; background: #fff; }
        .fitur-card {
            background: #fff;
            border: 1px solid #e8f0f8;
            border-radius: 16px;
            padding: 1.75rem;
            height: 100%;
            transition: all .25s;
            position: relative;
            overflow: hidden;
        }
        .fitur-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--grad, linear-gradient(90deg, var(--primary), var(--primary-light)));
            opacity: 0;
            transition: opacity .25s;
        }
        .fitur-card:hover {
            border-color: var(--primary-pale);
            box-shadow: 0 8px 30px rgba(15,76,129,.1);
            transform: translateY(-4px);
        }
        .fitur-card:hover::before { opacity: 1; }
        .fitur-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.1rem;
        }
        .fitur-card h5 {
            font-size: .95rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: .5rem;
        }
        .fitur-card p {
            font-size: .82rem;
            color: #6b8ba4;
            line-height: 1.7;
            margin: 0;
        }

        /* ── DASHBOARD PREVIEW ── */
        #preview { padding: 5rem 0; background: linear-gradient(135deg, #f0f7ff 0%, #e8f4ff 100%); }
        .preview-screen {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15,76,129,.15);
            overflow: hidden;
            border: 1px solid #dde8f0;
        }
        .preview-topbar {
            background: #0f4c81;
            padding: .65rem 1.25rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .preview-dot { width: 10px; height: 10px; border-radius: 50%; }
        .preview-body { display: flex; min-height: 280px; }
        .preview-sidebar {
            width: 180px;
            background: linear-gradient(180deg, #072a4e 0%, #0f4c81 100%);
            padding: 1rem .75rem;
            flex-shrink: 0;
        }
        .preview-menu-item {
            background: rgba(255,255,255,.12);
            border-radius: 6px;
            padding: .4rem .65rem;
            margin-bottom: .4rem;
            font-size: .65rem;
            color: rgba(255,255,255,.8);
            font-weight: 500;
        }
        .preview-menu-item.active {
            background: rgba(255,255,255,.22);
            color: #fff;
        }
        .preview-content {
            flex: 1;
            background: #f0f4f9;
            padding: 1rem;
        }
        .preview-stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin-bottom: .75rem; }
        .preview-stat-box {
            background: #fff;
            border-radius: 8px;
            padding: .65rem .75rem;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .preview-stat-box .lbl { font-size: .58rem; color: #6b8ba4; font-weight: 600; }
        .preview-stat-box .val { font-size: .9rem; font-weight: 700; color: #0d2545; }
        .preview-chart-box {
            background: #fff;
            border-radius: 8px;
            padding: .75rem;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .preview-chart-title { font-size: .65rem; font-weight: 700; color: #0d2545; margin-bottom: .5rem; }
        .preview-chart-bars { display: flex; align-items: flex-end; gap: .3rem; height: 50px; }
        .preview-bar { flex: 1; border-radius: 3px 3px 0 0; opacity: .75; }

        /* ── KONTAK ── */
        #kontak { padding: 5rem 0; background: #fff; }
        .kontak-card {
            background: #fff;
            border: 1px solid #e8f0f8;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 40px rgba(15,76,129,.08);
        }
        .form-label { font-size: .85rem; font-weight: 600; color: var(--dark); }
        .form-control, .form-select {
            border-color: #dde8f0;
            border-radius: 8px;
            font-size: .88rem;
            padding: .65rem .9rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(0,113,228,.12);
        }
        .btn-kirim {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            font-weight: 700;
            padding: .75rem 2rem;
            border-radius: 10px;
            border: none;
            font-size: .92rem;
            transition: all .25s;
        }
        .btn-kirim:hover {
            opacity: .9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,113,228,.35);
            color: #fff;
        }
        .kontak-info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .kontak-icon {
            width: 44px; height: 44px;
            background: var(--primary-pale);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .kontak-info-item h6 { font-size: .82rem; font-weight: 700; color: var(--dark); margin-bottom: .15rem; }
        .kontak-info-item p { font-size: .82rem; color: #6b8ba4; margin: 0; line-height: 1.6; }

        /* ── FOOTER ── */
        footer {
            background: linear-gradient(180deg, #072a4e 0%, #041729 100%);
            color: rgba(255,255,255,.7);
            padding: 3.5rem 0 1.5rem;
        }
        footer h6 {
            color: #fff;
            font-size: .88rem;
            font-weight: 700;
            margin-bottom: 1.1rem;
            position: relative;
            padding-bottom: .6rem;
        }
        footer h6::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 28px; height: 2px;
            background: var(--primary-light);
            border-radius: 2px;
        }
        footer p, footer a {
            font-size: .82rem;
            line-height: 1.8;
            color: rgba(255,255,255,.55);
        }
        footer a { text-decoration: none; transition: color .2s; display: block; }
        footer a:hover { color: #7dd3fc; }
        .footer-divider {
            border-color: rgba(255,255,255,.08);
            margin: 2rem 0 1.25rem;
        }
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            border-radius: 8px;
            background: rgba(255,255,255,.08);
            color: rgba(255,255,255,.6);
            font-size: .9rem;
            transition: all .2s;
            text-decoration: none;
        }
        .social-btn:hover {
            background: var(--primary-light);
            color: #fff;
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════ NAVBAR ═══════════════════════════════════ --}}
<nav class="navbar navbar-expand-lg fixed-top" id="landingNav">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="{{ asset('images/logo_bgn.png') }}" alt="Logo BGN" height="36">
            <span>Dashboard SPPG</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#preview">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn-masuk" href="{{ route('login') }}">
                        <i class="fas fa-sign-in-alt me-1"></i> Masuk
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════ HERO ═══════════════════════════════════ --}}
<section id="hero">
    <div class="container py-5">
        <div class="row align-items-center g-5 py-4">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="hero-badge">
                    <i class="fas fa-leaf" style="color:#7dd3fc;"></i>
                    Program Makan Bergizi Gratis
                </div>
                <h1 class="hero-title">
                    Pantau Gizi &amp; Biaya<br>
                    <span>Makan Bergizi Gratis</span><br>
                    Secara Real-Time
                </h1>
                <p class="hero-desc">
                    Sistem digital SPPG untuk monitoring kandungan gizi dan biaya produksi
                    menu harian program Makan Bergizi Gratis. Transparan, akurat, dan mudah dipantau.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#tentang" class="btn-hero-outline">
                        <i class="fas fa-info-circle"></i> Pelajari Program
                    </a>
                    <a href="{{ route('login') }}" class="btn-hero-primary">
                        <i class="fas fa-sign-in-alt"></i> Masuk Dashboard
                    </a>
                </div>
            </div>

            {{-- Dashboard Mockup --}}
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-mockup">
                    <div class="mockup-frame">
                        <div class="mockup-bar">
                            <div class="mockup-dot" style="background:#ff5f57;"></div>
                            <div class="mockup-dot" style="background:#febc2e;"></div>
                            <div class="mockup-dot" style="background:#28c840;"></div>
                            <span style="font-size:.65rem; color:rgba(255,255,255,.4); margin-left:.5rem;">Dashboard SPPG — Monitoring Gizi & Biaya</span>
                        </div>
                        <div class="mockup-stat-row">
                            <div class="mockup-stat">
                                <div class="mockup-stat-label">Rata-rata Kalori</div>
                                <div class="mockup-stat-val">
                                    {{ $avgEnergi > 0 ? number_format($avgEnergi, 0) . ' kkal' : '— kkal' }}
                                </div>
                            </div>
                            <div class="mockup-stat">
                                <div class="mockup-stat-label">Menu Hari Ini</div>
                                <div class="mockup-stat-val">
                                    {{ $todayMenus->count() }} <span style="font-size:.65rem;opacity:.6;font-weight:400">menu</span>
                                </div>
                            </div>
                            <div class="mockup-stat">
                                <div class="mockup-stat-label">Rata-rata % AKG</div>
                                <div class="mockup-stat-val" style="{{ $avgPctAkg > 0 ? ($avgPctAkg >= 70 && $avgPctAkg <= 130 ? 'color:#28c840;' : 'color:#febc2e;') : '' }}">
                                    {{ $avgPctAkg > 0 ? $avgPctAkg . '%' : '—' }}
                                </div>
                            </div>
                            <div class="mockup-stat">
                                <div class="mockup-stat-label">Total Porsi Bulan Ini</div>
                                <div class="mockup-stat-val">
                                    {{ $totalPorsi > 0 ? number_format($totalPorsi, 0, ',', '.') : '—' }}
                                    @if($totalPorsi > 0)<span style="font-size:.65rem;opacity:.6;font-weight:400"> porsi</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="mockup-menu-list">
                            <div style="font-size:.62rem; color:rgba(255,255,255,.45); font-weight:600; margin-bottom:.5rem; letter-spacing:.04em;">
                                MENU HARI INI &mdash; {{ now()->translatedFormat('d M Y') }}
                            </div>
                            @forelse($todayMenus as $menu)
                            <div class="mockup-menu-card">
                                <div class="mockup-menu-photo">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($menu->foto_menu) }}"
                                         alt="{{ $menu->nama_menu }}">
                                </div>
                                <div class="mockup-menu-info">
                                    <div class="mockup-menu-name">{{ $menu->nama_menu ?: '(tanpa nama)' }}</div>
                                    <div class="mockup-menu-date">
                                        <i class="fas fa-users fa-xs" style="opacity:.55;margin-right:2px;"></i>
                                        {{ $menu->catatan_anggaran ?: '—' }}
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div style="text-align:center; color:rgba(255,255,255,.3); font-size:.75rem; padding:.75rem 0;">
                                <i class="fas fa-utensils" style="display:block; margin-bottom:.35rem; opacity:.4;"></i>
                                Belum ada menu hari ini
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Scroll indicator --}}
    <div class="text-center pb-4 position-absolute bottom-0 start-50 translate-middle-x">
        <a href="#stats" style="color:rgba(255,255,255,.4); font-size:.75rem; text-decoration:none;">
            <i class="fas fa-chevron-down" style="animation: bounce 1.5s infinite;"></i>
        </a>
    </div>
</section>

{{-- ═══════════════════════════════════ STATS ═══════════════════════════════════ --}}
<section id="stats">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">845+</div>
                    <div class="stat-lbl">Bahan Pangan TKPI</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">12</div>
                    <div class="stat-lbl">Kelompok Sasaran AKG</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">22+</div>
                    <div class="stat-lbl">Nutrisi Terpantau</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num">100%</div>
                    <div class="stat-lbl">Data Transparan</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════ TENTANG ═══════════════════════════════════ --}}
<section id="tentang">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="section-tag">
                    <i class="fas fa-building"></i> Tentang Kami
                </div>
                <h2 class="section-title">
                    Satuan Pelayanan<br>Pemenuhan Gizi (SPPG)
                </h2>
                <p class="section-desc">
                    SPPG adalah unit pelaksana program Makan Bergizi Gratis yang bertanggung jawab
                    dalam pengelolaan, pengolahan, dan distribusi makanan bergizi kepada penerima
                    manfaat program.
                </p>
                <p class="section-desc mt-2">
                    Dashboard SPPG hadir sebagai platform digital untuk memastikan setiap menu
                    yang disajikan memenuhi standar gizi nasional (AKG) dan sesuai dengan
                    anggaran yang ditetapkan pemerintah.
                </p>
                {{-- Ganti informasi di bawah ini dengan data SPPG yang sebenarnya --}}
                <div class="mt-4 p-3" style="background:var(--primary-pale); border-radius:12px; border-left:3px solid var(--primary);">
                    <div style="font-size:.78rem; font-weight:700; color:var(--primary); margin-bottom:.3rem;">
                        <i class="fas fa-map-marker-alt me-1"></i> Lokasi SPPG
                    </div>
                    <div style="font-size:.82rem; color:#4a6f8a;">
                        SPPG Kota Pekanbaru Binawidya Simpangbaru<br>
                        Jl. Bangau Sakti No. 140<br>
                        Kecamatan Binawidya, Kota Pekanbaru, Provinsi Riau<br>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-card">
                            <div class="info-icon" style="background:#daeeff; color:#0f4c81;">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div>
                                <h6>Menu Bergizi Harian</h6>
                                <p>Penyusunan menu harian yang terstandarisasi dan sesuai kebutuhan gizi tiap kelompok sasaran.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <div class="info-icon" style="background:#d1fae5; color:#065f46;">
                                <i class="fas fa-heart-pulse"></i>
                            </div>
                            <div>
                                <h6>Standar Gizi Nasional</h6>
                                <p>Setiap menu mengacu pada Angka Kecukupan Gizi (AKG) yang ditetapkan oleh Kemenkes RI.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <div class="info-icon" style="background:#fef3c7; color:#92400e;">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div>
                                <h6>Transparansi Anggaran</h6>
                                <p>Biaya produksi dipantau secara real-time dan dibandingkan dengan anggaran per porsi yang berlaku.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-card">
                            <div class="info-icon" style="background:#ede9fe; color:#5b21b6;">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div>
                                <h6>Laporan Berkala</h6>
                                <p>Laporan gizi dan biaya dapat diekspor dalam format Excel dan PDF untuk keperluan evaluasi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════ FITUR ═══════════════════════════════════ --}}
<section id="fitur">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag mx-auto">
                <i class="fas fa-star"></i> Fitur Unggulan
            </div>
            <h2 class="section-title">Semua yang Anda Butuhkan<br>dalam Satu Dashboard</h2>
            <p class="section-desc mx-auto" style="max-width:540px;">
                Dirancang khusus untuk pengelola SPPG, ahli gizi, dan akuntan agar proses
                monitoring berjalan efisien dan akurat.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#daeeff; color:#0f4c81; --grad:linear-gradient(90deg,#0f4c81,#0071e4);">
                        <i class="fas fa-gauge-high"></i>
                    </div>
                    <h5>Dashboard Terpadu</h5>
                    <p>Ringkasan seluruh data gizi dan biaya dalam satu tampilan, dilengkapi grafik tren bulanan dan indikator performa.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#d1fae5; color:#065f46; --grad:linear-gradient(90deg,#065f46,#00b894);">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <h5>Monitoring Gizi</h5>
                    <p>Evaluasi otomatis kandungan energi, protein, lemak, karbohidrat, dan 18+ mikronutrien dibanding target AKG per kelompok.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#fef3c7; color:#92400e; --grad:linear-gradient(90deg,#f57c00,#ffd54f);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h5>Monitoring Biaya</h5>
                    <p>Pantau biaya produksi per porsi secara real-time, bandingkan dengan anggaran aktif, dan terima peringatan otomatis.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#ede9fe; color:#5b21b6; --grad:linear-gradient(90deg,#5b21b6,#7c3aed);">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h5>Simulasi Menu</h5>
                    <p>Buat dan uji coba komposisi menu sebelum disimpan. Lihat prediksi nilai gizi dan biaya secara langsung tanpa menyimpan ke database.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#fee2e2; color:#991b1b; --grad:linear-gradient(90deg,#991b1b,#ef4444);">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h5>Budget Alert</h5>
                    <p>Notifikasi otomatis ketika biaya produksi mendekati (≥85%) atau melebihi (>100%) batas anggaran yang ditetapkan.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="fitur-card">
                    <div class="fitur-icon" style="background:#e0f2fe; color:#0369a1; --grad:linear-gradient(90deg,#0369a1,#0ea5e9);">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h5>Ekspor Laporan</h5>
                    <p>Unduh laporan gizi dan biaya dalam format Excel (.xlsx) dan PDF untuk keperluan pelaporan dan dokumentasi.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════ PREVIEW ═══════════════════════════════════ --}}
<section id="preview">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="section-tag">
                    <i class="fas fa-desktop"></i> Tampilan Sistem
                </div>
                <h2 class="section-title">Antarmuka yang<br>Bersih &amp; Intuitif</h2>
                <p class="section-desc">
                    Dashboard SPPG dirancang dengan tampilan yang sederhana namun informatif,
                    memudahkan ketua SPPG, ahli gizi, dan akuntan dalam mengambil keputusan
                    berbasis data.
                </p>
                <ul class="list-unstyled mt-3" style="font-size:.85rem; color:#4a6f8a;">
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:var(--accent-green);"></i> Akses berbasis peran (role-based access)</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:var(--accent-green);"></i> Data tersimpan aman di server lokal</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:var(--accent-green);"></i> Dapat diakses dari perangkat apapun</li>
                    <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:var(--accent-green);"></i> Integrasi data TKPI 845+ bahan pangan</li>
                </ul>
                <a href="{{ route('login') }}" class="btn-hero-primary mt-2" style="display:inline-flex;">
                    <i class="fas fa-sign-in-alt"></i> Coba Sekarang
                </a>
            </div>
            <div class="col-lg-7">
                <div class="preview-screen">
                    <div class="preview-topbar">
                        <div class="preview-dot" style="background:#ff5f57;"></div>
                        <div class="preview-dot" style="background:#febc2e;"></div>
                        <div class="preview-dot" style="background:#28c840;"></div>
                        <span style="font-size:.6rem; color:rgba(255,255,255,.5); margin-left:.75rem; font-family:monospace;">
                            localhost — Dashboard SPPG
                        </span>
                    </div>
                    <div class="preview-body">
                        <div class="preview-sidebar">
                            <div style="font-size:.58rem; color:rgba(255,255,255,.35); font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin-bottom:.65rem;">Menu Utama</div>
                            <div class="preview-menu-item active"><i class="fas fa-gauge-high me-1"></i> Dashboard</div>
                            <div style="font-size:.58rem; color:rgba(255,255,255,.35); font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin:.75rem 0 .5rem;">Manajemen</div>
                            <div class="preview-menu-item"><i class="fas fa-utensils me-1"></i> Menu Harian</div>
                            <div class="preview-menu-item"><i class="fas fa-carrot me-1"></i> Bahan Pangan</div>
                            <div class="preview-menu-item"><i class="fas fa-wallet me-1"></i> Anggaran</div>
                            <div style="font-size:.58rem; color:rgba(255,255,255,.35); font-weight:700; text-transform:uppercase; letter-spacing:.07em; margin:.75rem 0 .5rem;">Monitoring</div>
                            <div class="preview-menu-item"><i class="fas fa-coins me-1"></i> Biaya Produksi</div>
                            <div class="preview-menu-item"><i class="fas fa-bell me-1"></i> Budget Alert</div>
                        </div>
                        <div class="preview-content">
                            <div style="font-size:.7rem; font-weight:700; color:#0d2545; margin-bottom:.75rem;">
                                Dashboard — Mei 2026
                            </div>
                            <div class="preview-stat-grid">
                                <div class="preview-stat-box">
                                    <div class="lbl">Kalori Rata-rata</div>
                                    <div class="val" style="color:#0f4c81;">547 kkal</div>
                                </div>
                                <div class="preview-stat-box">
                                    <div class="lbl">Biaya/Porsi</div>
                                    <div class="val" style="color:#f57c00;">Rp 14.250</div>
                                </div>
                                <div class="preview-stat-box">
                                    <div class="lbl">Status Budget</div>
                                    <div class="val" style="color:#00b894;">Aman</div>
                                </div>
                            </div>
                            <div class="preview-chart-box">
                                <div class="preview-chart-title">Tren Kalori Harian (kkal)</div>
                                <div class="preview-chart-bars">
                                    <div class="preview-bar" style="height:55%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:70%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:48%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:85%; background:#0071e4;"></div>
                                    <div class="preview-bar" style="height:62%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:75%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:58%; background:#0f4c81;"></div>
                                    <div class="preview-bar" style="height:90%; background:#0071e4;"></div>
                                    <div class="preview-bar" style="height:67%; background:#0f4c81;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════ KONTAK ═══════════════════════════════════ --}}
<section id="kontak">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag mx-auto">
                <i class="fas fa-envelope"></i> Hubungi Kami
            </div>
            <h2 class="section-title">Punya Pertanyaan<br>atau Masukan?</h2>
            <p class="section-desc mx-auto" style="max-width:500px;">
                Kirimkan pesan Anda dan tim SPPG akan segera merespons.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-5">
                <div class="kontak-info-item">
                    <div class="kontak-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h6>Alamat</h6>
                        {{-- Perbarui alamat SPPG di bawah ini --}}
                        <p>Jl. Bangau Sakti No. 140, Kecamatan Binawidya<br>Kota Pekanbaru, Provinsi Riau</p>
                    </div>
                </div>
                <div class="kontak-info-item">
                    <div class="kontak-icon"><i class="fas fa-phone"></i></div>
                    <div>
                        <h6>Telepon / WhatsApp</h6>
                        {{-- Perbarui nomor SPPG di bawah ini --}}
                        <p>+62 812-1234-4567</p>
                    </div>
                </div>
                <div class="kontak-info-item">
                    <div class="kontak-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h6>Email</h6>
                        {{-- Perbarui email SPPG di bawah ini --}}
                        <p>sppgbinawidya@gmail.com</p>
                    </div>
                </div>
                <div class="kontak-info-item">
                    <div class="kontak-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <h6>Jam Operasional</h6>
                        <p>Senin – Jumat: 07.00 – 16.00 WIB<br>Sabtu: 07.00 – 12.00 WIB</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-7">
                <div class="kontak-card">
                    {{-- Alert pesan berhasil dikirim --}}
                    @if(session('pesan_terkirim'))
                    <div class="alert mb-4" style="background:#d1fae5; border:1px solid #6ee7b7; border-radius:10px; color:#065f46;">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Pesan berhasil dikirim!</strong> Terima kasih, kami akan segera menghubungi Anda.
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert mb-4" style="background:#fee2e2; border:1px solid #fca5a5; border-radius:10px; color:#991b1b;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('landing.kirim-pesan') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama') }}"
                                   placeholder="Masukkan nama Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor HP / WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                                   value="{{ old('no_hp') }}"
                                   placeholder="Contoh: 08123456789" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Isi Pesan <span class="text-danger">*</span></label>
                            <textarea name="pesan" rows="5"
                                      class="form-control @error('pesan') is-invalid @enderror"
                                      placeholder="Tulis pertanyaan atau masukan Anda di sini..."
                                      required maxlength="1000">{{ old('pesan') }}</textarea>
                            <div class="text-end mt-1" style="font-size:.72rem; color:#adb5bd;">
                                Maksimal 1000 karakter
                            </div>
                        </div>
                        <button type="submit" class="btn-kirim w-100">
                            <i class="fas fa-paper-plane me-2"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════ FOOTER ═══════════════════════════════════ --}}
<footer>
    <div class="container">
        <div class="row g-4">
            {{-- Kolom 1: Tentang --}}
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="{{ asset('images/logo_bgn.png') }}" alt="Logo" height="32">
                    <span style="font-size:.95rem; font-weight:700; color:#fff;">Dashboard SPPG</span>
                </div>
                <p>
                    Sistem monitoring gizi dan biaya produksi untuk Satuan Pelayanan Pemenuhan Gizi (SPPG)
                    program Makan Bergizi Gratis.
                </p>
                <div class="d-flex gap-2 mt-3">
                    {{-- Perbarui link sosial media di bawah ini --}}
                    <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="social-btn" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            {{-- Kolom 2: Navigasi --}}
            <div class="col-lg-2 col-md-6 col-6">
                <h6>Navigasi</h6>
                <a href="#tentang">Tentang SPPG</a>
                <a href="#fitur">Fitur Dashboard</a>
                <a href="#preview">Tampilan Sistem</a>
                <a href="#kontak">Hubungi Kami</a>
                <a href="{{ route('login') }}">Masuk Dashboard</a>
            </div>

            {{-- Kolom 3: Program --}}
            <div class="col-lg-3 col-md-6 col-6">
                <h6>Program MBG</h6>
                <a href="#">Tentang Program</a>
                <a href="#">Kelompok Sasaran</a>
                <a href="#">Standar Gizi AKG</a>
                <a href="#">Panduan SPPG</a>
            </div>

            {{-- Kolom 4: Kontak --}}
            <div class="col-lg-3 col-md-6">
                <h6>Kontak</h6>
                <p class="mb-1"><i class="fas fa-map-marker-alt me-2" style="color:var(--primary-light);"></i>
                    {{-- Perbarui alamat SPPG --}}
                    Jl. Bangau Sakti No. 140, Kota Pekanbaru
                </p>
                <p class="mb-1"><i class="fas fa-phone me-2" style="color:var(--primary-light);"></i>
                    +62 812-1234-4567
                </p>
                <p class="mb-1"><i class="fas fa-envelope me-2" style="color:var(--primary-light);"></i>
                    sppgbinawidya@gmail.com
                </p>
                <p><i class="fas fa-clock me-2" style="color:var(--primary-light);"></i>
                    Senin–Jumat, 07.00–16.00
                </p>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <p class="mb-0" style="font-size:.78rem; color:rgba(255,255,255,.35);">
                &copy; {{ date('Y') }} Dashboard SPPG — Monitoring System. Hak cipta dilindungi.
            </p>
            {{-- <p class="mb-0" style="font-size:.78rem; color:rgba(255,255,255,.25);">
                Dikembangkan untuk Program Makan Bergizi Gratis
            </p> --}}
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function () {
        const nav = document.getElementById('landingNav');
        if (window.scrollY > 60) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });

    // Auto scroll ke form kontak jika pesan terkirim
    @if(session('pesan_terkirim'))
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('kontak');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    });
    @endif
</script>

<style>
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(6px); }
    }
</style>
</body>
</html>
