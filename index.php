<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestiPoint - Solution professionnelle de gestion de pointage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Manrope', sans-serif;
            background: #f8faff;
            color: #0f172a;
        }

        /* NAV */
        .navbar {
            background: #ffffff;
            box-shadow: 0 1px 0 rgba(0,0,0,0.08);
            padding: 0.85rem 0;
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }
        .navbar .inner {
            max-width: 1280px; margin: 0 auto; padding: 0 3rem;
            display: flex; justify-content: space-between; align-items: center;
            gap: 2rem;
        }
        .logo {
            font-size: 1.45rem; font-weight: 800;
            color: #111827; text-decoration: none;
            display: flex; align-items: center; gap: 10px;
            margin-right: 3rem; white-space: nowrap;
        }
        .logo-icon {
            width: 36px; height: 36px; background: #1a56db;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .logo-icon svg { width: 20px; height: 20px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; flex: 1; justify-content: flex-start; }
        .nav-links a {
            text-decoration: none; color: #374151; font-weight: 500;
            font-size: 0.9rem; transition: color 0.2s; white-space: nowrap;
        }
        .nav-links a:hover { color: #1a56db; }
        .nav-links a.active { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 2px; }
        .nav-cta { display: flex; gap: 0.8rem; align-items: center; flex-shrink: 0; }
        .btn-secondary-nav {
            color: #374151; text-decoration: none; font-weight: 600;
            font-size: 0.9rem; padding: 0.5rem 1rem; white-space: nowrap;
            transition: color 0.2s;
        }
        .btn-secondary-nav:hover { color: #1a56db; }
        .btn-primary-nav {
            background: #1a56db; color: white; text-decoration: none;
            font-weight: 600; font-size: 0.9rem; padding: 0.6rem 1.4rem;
            border-radius: 8px; transition: all 0.2s; white-space: nowrap;
        }
        .btn-primary-nav:hover { background: #1447c0; color: white; }

        /* HERO */
        .hero {
            min-height: 100vh;
            padding-top: 80px;
            background: url('pc2.jpeg') center center / cover no-repeat;
            display: flex; align-items: center;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                to right,
                rgba(5, 12, 35, 0.65) 0%,
                rgba(5, 12, 35, 0.45) 40%,
                rgba(5, 12, 35, 0.20) 70%,
                rgba(5, 12, 35, 0.05) 100%
            );
            pointer-events: none;
            z-index: 0;
        }
        .hero::after { display: none; }

        .hero-inner {
            max-width: 1280px; margin: 0 auto; padding: 0 2rem;
            display: grid; grid-template-columns: 1fr 1.2fr;
            gap: 3rem; align-items: center; width: 100%;
            position: relative; z-index: 1;
        }
        .hero-left { padding: 3rem 0; }
        .hero h1 {
            font-size: 3rem; font-weight: 800; line-height: 1.15;
            color: #ffffff; margin-bottom: 1.2rem; letter-spacing: -0.03em;
        }
        .hero h1 .highlight { color: #60a5fa; }
        .hero-sub {
            font-size: 1rem; color: rgba(255,255,255,0.72); line-height: 1.7;
            margin-bottom: 2rem; max-width: 480px;
        }
        .hero-actions { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .btn-hero-primary {
            background: #1a56db; color: white; text-decoration: none;
            font-weight: 700; font-size: 0.95rem;
            padding: 0.85rem 1.8rem; border-radius: 10px;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s; box-shadow: 0 4px 20px rgba(26,86,219,0.50);
        }
        .btn-hero-primary:hover { background: #1447c0; color: white; transform: translateY(-1px); }
        .btn-hero-outline {
            background: rgba(255,255,255,0.12); color: #ffffff; text-decoration: none;
            font-weight: 600; font-size: 0.95rem;
            padding: 0.85rem 1.8rem; border-radius: 10px;
            border: 1.5px solid rgba(255,255,255,0.28);
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s; backdrop-filter: blur(6px);
        }
        .btn-hero-outline:hover { background: rgba(255,255,255,0.20); color: white; }
        .play-icon {
            width: 28px; height: 28px; background: #ffffff;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .play-icon i { font-size: 0.6rem; color: #1a56db; margin-left: 2px; }
        .hero-badges { display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; }
        .hero-badge-item {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.85);
        }
        .badge-check {
            width: 20px; height: 20px; background: #22c55e;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .badge-check i { font-size: 0.55rem; color: white; }

        /* DASHBOARD MOCKUP */
        .hero-right { position: relative; }
        .dashboard-wrapper {
            position: relative;
            filter: drop-shadow(0 25px 50px rgba(0,0,0,0.35));
        }
        .mobile-mock {
            position: absolute;
            left: -60px; bottom: -20px;
            width: 190px;
            z-index: 10;
        }
        .phone-outer {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border-radius: 36px;
            padding: 9px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.50), inset 0 1px 0 rgba(255,255,255,0.08);
            position: relative;
        }
        .phone-outer::before {
            content: '';
            position: absolute;
            left: -3px; top: 65px;
            width: 3px; height: 26px;
            background: #1e293b; border-radius: 2px 0 0 2px;
            box-shadow: 0 34px 0 #1e293b;
        }
        .phone-outer::after {
            content: '';
            position: absolute;
            right: -3px; top: 85px;
            width: 3px; height: 38px;
            background: #1e293b; border-radius: 0 2px 2px 0;
        }
        .phone-screen { background: white; border-radius: 28px; overflow: hidden; }
        .phone-notch { display: flex; justify-content: center; padding: 7px 0 3px; background: white; }
        .phone-notch-pill {
            width: 55px; height: 16px; background: #f1f5f9;
            border-radius: 20px; display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .notch-speaker { width: 20px; height: 3px; background: #cbd5e1; border-radius: 4px; }
        .notch-cam { width: 6px; height: 6px; background: #cbd5e1; border-radius: 50%; }
        .mobile-header { background: #f8faff; padding: 9px 13px 7px; font-size: 0.63rem; color: #64748b; border-bottom: 1px solid #e5e7eb; }
        .mobile-header .greeting { font-size: 0.7rem; color: #111827; font-weight: 700; margin-top: 2px; }
        .mobile-time { text-align: center; padding: 11px 12px 3px; font-size: 1.5rem; font-weight: 800; color: #111827; letter-spacing: -1px; }
        .mobile-date { text-align: center; font-size: 0.56rem; color: #94a3b8; padding-bottom: 8px; }
        .mobile-btn { background: #22c55e; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin: 0 auto 3px; box-shadow: 0 4px 14px rgba(34,197,94,0.45); }
        .mobile-btn i { color: white; font-size: 1rem; }
        .mobile-btn-label { text-align: center; font-size: 0.56rem; color: #22c55e; padding-bottom: 9px; font-weight: 700; }
        .mobile-history { padding: 9px 13px; border-top: 1px solid #e5e7eb; }
        .mobile-history-title { font-size: 0.56rem; color: #94a3b8; margin-bottom: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
        .history-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.6rem; margin-bottom: 6px; }
        .history-row .label { color: #94a3b8; }
        .history-row .val { color: #111827; font-weight: 700; }
        .history-row .val.out { color: #f59e0b; }
        .phone-home-bar { display: flex; justify-content: center; padding: 7px 0 5px; background: white; }
        .home-bar { width: 46px; height: 4px; background: #e5e7eb; border-radius: 4px; }

        .desktop-mock { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); overflow: hidden; border: 1px solid #e5e7eb; margin-left: 60px; }
        .desktop-topbar { background: white; border-bottom: 1px solid #f3f4f6; padding: 10px 18px; display: flex; justify-content: space-between; align-items: center; }
        .desktop-title { font-size: 0.9rem; font-weight: 700; color: #111827; }
        .user-pill { display: flex; align-items: center; gap: 7px; font-size: 0.75rem; color: #374151; }
        .user-avatar { width: 28px; height: 28px; background: #1a56db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.65rem; font-weight: 700; }
        .desktop-body { display: grid; grid-template-columns: 160px 1fr; }
        .sidebar { background: #0f2b4d; padding: 14px 0; min-height: 320px; }
        .sidebar-item { display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 0.72rem; color: #94a3b8; cursor: pointer; transition: all 0.15s; }
        .sidebar-item i { width: 14px; font-size: 0.72rem; }
        .sidebar-item.active { background: rgba(26,86,219,0.2); color: white; }
        .main-area { padding: 14px; background: white; }
        .kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; margin-bottom: 12px; }
        .kpi { background: white; border-radius: 8px; padding: 10px; border: 1px solid #e5e7eb; }
        .kpi-label { font-size: 0.6rem; color: #9ca3af; margin-bottom: 4px; }
        .kpi-val { font-size: 1rem; font-weight: 800; color: #111827; }
        .kpi-val span { font-size: 0.65rem; color: #9ca3af; font-weight: 500; }
        .kpi-val.blue { color: #1a56db; }
        .kpi-sub { font-size: 0.58rem; color: #9ca3af; margin-top: 2px; }
        .chart-area { background: white; border-radius: 8px; padding: 10px; border: 1px solid #e5e7eb; margin-bottom: 8px; }
        .chart-title { font-size: 0.68rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .chart-svg { width: 100%; height: 70px; }
        .activity-list { background: white; border-radius: 8px; padding: 10px; border: 1px solid #e5e7eb; }
        .activity-title { font-size: 0.68rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .activity-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f9fafb; font-size: 0.6rem; color: #6b7280; }
        .activity-row:last-child { border-bottom: none; }
        .activity-row .name { font-weight: 600; color: #374151; }
        .activity-dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; margin-right: 5px; display: inline-block; }

        /* WHY */
        .why-section { padding: 80px 0 60px; background: white; }
        .section-header { text-align: center; margin-bottom: 3rem; }
        .section-header h2 { font-size: 1.9rem; font-weight: 800; color: #111827; margin-bottom: 0.6rem; letter-spacing: -0.02em; }
        .section-header h2 .blue { color: #1a56db; }
        .section-header p { color: #6b7280; font-size: 0.95rem; }
        .inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
        .why-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5rem; }
        .why-card { background: #f8faff; border-radius: 16px; padding: 1.6rem; border: 1px solid #e8edf8; transition: all 0.25s; }
        .why-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.07); }
        .why-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 1.4rem; }
        .why-icon.blue { background: #eff6ff; color: #1a56db; }
        .why-icon.green { background: #f0fdf4; color: #22c55e; }
        .why-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        .why-icon.orange { background: #fff7ed; color: #f59e0b; }
        .why-card h3 { font-size: 0.95rem; font-weight: 700; color: #111827; margin-bottom: 0.4rem; }
        .why-card p { font-size: 0.83rem; color: #6b7280; line-height: 1.6; }

        /* STATS */
        .stats-section { padding: 60px 0; background: white; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; }
        .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; text-align: center; }
        .stat-block { padding: 1.5rem; border-right: 1px solid #f3f4f6; }
        .stat-block:last-child { border-right: none; }
        .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem; font-size: 1.1rem; }
        .stat-icon.blue { background: #eff6ff; color: #1a56db; }
        .stat-icon.green { background: #f0fdf4; color: #22c55e; }
        .stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        .stat-icon.orange { background: #fff7ed; color: #f59e0b; }
        .stat-number { font-size: 2rem; font-weight: 800; color: #111827; letter-spacing: -0.02em; margin-bottom: 0.2rem; }
        .stat-label { font-size: 0.8rem; color: #6b7280; font-weight: 500; }

        /* FEATURES */
        .features-section { padding: 80px 0; background: #f8faff; }
        .features-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.2rem; }
        .feat-item { background: white; border-radius: 14px; padding: 1.3rem; display: flex; align-items: center; gap: 1rem; border: 1px solid #e8edf8; transition: all 0.2s; }
        .feat-item:hover { transform: translateX(4px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .feat-icon { width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #1a56db; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .feat-text h4 { font-size: 0.88rem; font-weight: 700; color: #111827; margin-bottom: 0.2rem; }
        .feat-text p { font-size: 0.78rem; color: #6b7280; }

        /* CTA */
        .cta-section { padding: 80px 0; background: white; text-align: center; }
        .cta-section h2 { font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 0.8rem; letter-spacing: -0.02em; }
        .cta-section p { font-size: 0.95rem; color: #6b7280; margin-bottom: 2rem; }
        .btn-cta { background: #1a56db; color: white; text-decoration: none; font-weight: 700; padding: 0.9rem 2.2rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(26,86,219,0.3); transition: all 0.2s; }
        .btn-cta:hover { background: #1447c0; color: white; transform: translateY(-2px); }

        /* FOOTER */
        .footer { background: #111827; color: #9ca3af; padding: 30px 0; text-align: center; font-size: 0.85rem; }
        .footer a { color: #9ca3af; text-decoration: none; }
        .footer-links { display: flex; gap: 1.5rem; justify-content: center; margin-top: 0.8rem; }

        @media (max-width: 1024px) {
            .hero-inner { grid-template-columns: 1fr; }
            .hero-right { display: none; }
            .why-grid, .stats-row, .features-grid { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 640px) {
            .hero h1 { font-size: 2rem; }
            .why-grid, .stats-row, .features-grid { grid-template-columns: 1fr; }
            .stat-block { border-right: none; border-bottom: 1px solid #f3f4f6; }
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav class="navbar">
    <div class="inner">
        <a href="index.php" class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            GestiPoint
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">Accueil</a>
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#tarifs">Tarifs</a>
            <a href="#apropos">À propos</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="nav-cta">
            <a href="login.php" class="btn-secondary-nav">Se connecter</a>
            <a href="login.php" class="btn-primary-nav">Demander une démo</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="accueil">
    <div class="hero-inner">
        <div class="hero-left">
            <h1>La solution simple<br>et efficace pour gérer<br>le <span class="highlight">pointage</span> de votre équipe</h1>
            <p class="hero-sub">GestiPoint vous aide à suivre les présences, les heures travaillées et à optimiser la gestion du temps en toute simplicité.</p>
            <div class="hero-actions">
                <a href="login.php" class="btn-hero-primary">
                    Commencer maintenant <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#demo" class="btn-hero-outline">
                    <div class="play-icon"><i class="fas fa-play"></i></div>
                    Voir la démo
                </a>
            </div>
            <div class="hero-badges">
                <div class="hero-badge-item">
                    <div class="badge-check"><i class="fas fa-check"></i></div>
                    Simple à utiliser
                </div>
                <div class="hero-badge-item">
                    <div class="badge-check"><i class="fas fa-check"></i></div>
                    Sécurisé
                </div>
                <div class="hero-badge-item">
                    <div class="badge-check"><i class="fas fa-check"></i></div>
                    Accessible partout
                </div>
            </div>
        </div>

        <!-- Dashboard mockup -->
        <div class="hero-right">
            <div class="dashboard-wrapper">
                <div class="mobile-mock">
                    <div class="phone-outer">
                        <div class="phone-screen">
                            <div class="phone-notch">
                                <div class="phone-notch-pill">
                                    <div class="notch-speaker"></div>
                                    <div class="notch-cam"></div>
                                </div>
                            </div>
                            <div class="mobile-header">
                                Bonjour,<div class="greeting">M. User 👋</div>
                            </div>
                            <div class="mobile-time">08:30:45</div>
                            <div class="mobile-date">Vendredi 24 mai 2024</div>
                            <div class="mobile-btn"><i class="fas fa-fingerprint"></i></div>
                            <div class="mobile-btn-label">Pointer</div>
                            <div class="mobile-history">
                                <div class="mobile-history-title">Historique récent</div>
                                <div class="history-row"><span class="label">Entrée</span><span class="val">08:02</span></div>
                                <div class="history-row"><span class="label">Aujourd'hui</span><span class="val">Aujourd'hui</span></div>
                                <div class="history-row"><span class="label">Sortie</span><span class="val out">17:45</span></div>
                            </div>
                            <div class="phone-home-bar"><div class="home-bar"></div></div>
                        </div>
                    </div>
                </div>

                <div class="desktop-mock">
                    <div class="desktop-topbar">
                        <div class="desktop-title">Tableau de bord</div>
                        <div class="user-pill">
                            <i class="fas fa-bell" style="color:#9ca3af;font-size:0.8rem;"></i>
                            <div class="user-avatar">MU</div>
                            <div>
                                <div style="font-size:0.7rem;font-weight:700;">M. User</div>
                                <div style="font-size:0.6rem;color:#9ca3af;">Administrateur</div>
                            </div>
                        </div>
                    </div>
                    <div class="desktop-body">
                        <div class="sidebar">
                            <div class="sidebar-item active"><i class="fas fa-th-large"></i> Tableau de bord</div>
                            <div class="sidebar-item"><i class="fas fa-fingerprint"></i> Pointage</div>
                            <div class="sidebar-item"><i class="fas fa-users"></i> Employés</div>
                            <div class="sidebar-item"><i class="fas fa-chart-bar"></i> Rapports</div>
                            <div class="sidebar-item"><i class="fas fa-envelope"></i> Demandes</div>
                            <div class="sidebar-item"><i class="fas fa-cog"></i> Paramètres</div>
                            <div class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Déconnexion</div>
                        </div>
                        <div class="main-area">
                            <div class="kpi-row">
                                <div class="kpi"><div class="kpi-label">Présents</div><div class="kpi-val">42 <span>/ 58</span></div><div class="kpi-sub">Aujourd'hui</div></div>
                                <div class="kpi"><div class="kpi-label">Retards</div><div class="kpi-val">5</div><div class="kpi-sub">Aujourd'hui</div></div>
                                <div class="kpi"><div class="kpi-label">Heures travaillées</div><div class="kpi-val blue">256h <span>30m</span></div><div class="kpi-sub">Cette semaine</div></div>
                                <div class="kpi"><div class="kpi-label">Demandes</div><div class="kpi-val">8</div><div class="kpi-sub">En attente</div></div>
                            </div>
                            <div class="chart-area">
                                <div class="chart-title">Présences cette semaine</div>
                                <svg class="chart-svg" viewBox="0 0 400 70" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#1a56db" stop-opacity="0.15"/>
                                            <stop offset="100%" stop-color="#1a56db" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    <line x1="0" y1="17" x2="400" y2="17" stroke="#f3f4f6" stroke-width="1"/>
                                    <line x1="0" y1="35" x2="400" y2="35" stroke="#f3f4f6" stroke-width="1"/>
                                    <line x1="0" y1="52" x2="400" y2="52" stroke="#f3f4f6" stroke-width="1"/>
                                    <line x1="0" y1="70" x2="400" y2="70" stroke="#f3f4f6" stroke-width="1"/>
                                    <path d="M0,50 C30,45 60,20 100,25 S160,40 200,30 S280,15 320,20 S370,35 400,28 L400,70 L0,70 Z" fill="url(#chartGrad)"/>
                                    <path d="M0,50 C30,45 60,20 100,25 S160,40 200,30 S280,15 320,20 S370,35 400,28" fill="none" stroke="#1a56db" stroke-width="2.5" stroke-linecap="round"/>
                                    <circle cx="100" cy="25" r="3.5" fill="#1a56db"/>
                                    <circle cx="200" cy="30" r="3.5" fill="#1a56db"/>
                                    <circle cx="300" cy="18" r="3.5" fill="#1a56db"/>
                                    <circle cx="400" cy="28" r="3.5" fill="#1a56db"/>
                                </svg>
                                <div style="display:flex;justify-content:space-between;font-size:0.55rem;color:#9ca3af;margin-top:4px;">
                                    <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
                                </div>
                            </div>
                            <div class="activity-list">
                                <div class="activity-title">Activités récentes</div>
                                <div class="activity-row"><span><span class="activity-dot"></span><span class="name">Marie K.</span> a pointé à 08:02</span><span>il y a 10 min</span></div>
                                <div class="activity-row"><span><span class="activity-dot" style="background:#f59e0b"></span><span class="name">Jean D.</span> a fait une demande de congé</span><span>il y a 1h</span></div>
                                <div class="activity-row"><span><span class="activity-dot"></span><span class="name">Paul T.</span> a pointé à 17:45</span><span>il y a 2h</span></div>
                                <div class="activity-row"><span><span class="activity-dot"></span><span class="name">Sophie L.</span> a pointé à 08:10</span><span>il y a 3h</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHY -->
<section class="why-section">
    <div class="inner">
        <div class="section-header">
            <h2>Pourquoi choisir <span class="blue">GestiPoint</span> ?</h2>
            <p>Tout ce qu'il vous faut pour une gestion de pointage sans stress.</p>
        </div>
        <div class="why-grid">
            <div class="why-card">
                <div class="why-icon blue"><i class="fas fa-clock"></i></div>
                <h3>Pointage simplifié</h3>
                <p>Vos employés pointent en un clic depuis n'importe où.</p>
            </div>
            <div class="why-card">
                <div class="why-icon green"><i class="fas fa-chart-bar"></i></div>
                <h3>Rapports en temps réel</h3>
                <p>Suivez les présences, retards et absences en temps réel.</p>
            </div>
            <div class="why-card">
                <div class="why-icon purple"><i class="fas fa-shield-alt"></i></div>
                <h3>Sécurité garantie</h3>
                <p>Vos données sont protégées avec les plus hauts standards.</p>
            </div>
            <div class="why-card">
                <div class="why-icon orange"><i class="fas fa-mobile-alt"></i></div>
                <h3>Accessible partout</h3>
                <p>Disponible sur tous vos appareils : web, mobile, tablette.</p>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="inner">
        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-icon blue"><i class="fas fa-building"></i></div>
                <div class="stat-number" data-target="500">0</div>
                <div class="stat-label">Entreprises nous font confiance</div>
            </div>
            <div class="stat-block">
                <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                <div class="stat-number" data-target="10000">0</div>
                <div class="stat-label">Utilisateurs actifs</div>
            </div>
            <div class="stat-block">
                <div class="stat-icon purple"><i class="fas fa-history"></i></div>
                <div class="stat-number" data-target="1000000">0</div>
                <div class="stat-label">Pointages enregistrés</div>
            </div>
            <div class="stat-block">
                <div class="stat-icon orange"><i class="fas fa-smile"></i></div>
                <div class="stat-number" data-target="98">0</div>
                <div class="stat-label">Taux de satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="features-section" id="fonctionnalites">
    <div class="inner">
        <div class="section-header">
            <h2>Fonctionnalités avancées</h2>
            <p>L'essentiel pour une gestion sans friction</p>
        </div>
        <div class="features-grid">
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-fingerprint"></i></div>
                <div class="feat-text"><h4>Pointage multi-modes</h4><p>Email, QR code ou reconnaissance faciale</p></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="feat-text"><h4>Tableaux de bord temps réel</h4><p>Visualisez l'activité en un clin d'œil</p></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-file-excel"></i></div>
                <div class="feat-text"><h4>Export Excel / PDF</h4><p>Rapports automatiques pour la paie</p></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="feat-text"><h4>Sécurité renforcée</h4><p>Géolocalisation, IP, anti-fraude</p></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-bell"></i></div>
                <div class="feat-text"><h4>Alertes & notifications</h4><p>Restez informé en temps réel</p></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="fas fa-mobile-alt"></i></div>
                <div class="feat-text"><h4>Mobile-first</h4><p>Accessible partout, tout le temps</p></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="inner">
        <h2>Prêt à transformer la gestion<br>de vos équipes ?</h2>
        <p>Rejoignez les centaines d'entreprises qui nous font confiance</p>
        <a href="login.php" class="btn-cta">Commencer maintenant <i class="fas fa-arrow-right"></i></a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer" id="contact">
    <div class="inner">
        <p>&copy; 2026 GestiPoint – Gestion de pointage professionnelle</p>
        <div class="footer-links">
            <a href="mailto:contact@gestipoint.ma"><i class="fas fa-envelope"></i> contact@gestipoint.ma</a>
            <span>|</span>
            <a href="tel:+212522123456"><i class="fas fa-phone"></i> +212 5 22 123 456</a>
            <span>|</span>
            <span><i class="fas fa-map-marker-alt"></i> Casablanca, Maroc</span>
        </div>
    </div>
</footer>

<script>
function formatNumber(n, target) {
    if (target >= 1000000) return '+' + Math.ceil(n / 1000000) + 'M';
    if (target >= 10000)   return '+' + Math.ceil(n / 1000) + ' 000';
    if (target >= 500)     return '+' + Math.ceil(n);
    return Math.ceil(n) + '%';
}
const counters = document.querySelectorAll('.stat-number');
let animated = false;
function animateCounters() {
    if (animated) return;
    animated = true;
    counters.forEach(el => {
        const target = parseInt(el.dataset.target);
        let current = 0;
        const increment = target / 60;
        const timer = setInterval(() => {
            current = Math.min(current + increment, target);
            el.textContent = formatNumber(current, target);
            if (current >= target) {
                el.textContent = formatNumber(target, target);
                clearInterval(timer);
            }
        }, 25);
    });
}
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) animateCounters(); });
});
const statsSection = document.querySelector('.stats-section');
if (statsSection) observer.observe(statsSection);

document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
    });
});
</script>
</body>
</html>