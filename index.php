<?php
session_start();
require 'config/db.php';

// Live stats
$total_doctors  = $pdo->query("SELECT COUNT(*) FROM doctors WHERE available=1")->fetchColumn();
$total_depts    = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$today_tokens   = $pdo->query("SELECT COUNT(*) FROM tokens WHERE DATE(booked_at)=CURDATE()")->fetchColumn();
$today_done     = $pdo->query("SELECT COUNT(*) FROM tokens WHERE status='done' AND DATE(booked_at)=CURDATE()")->fetchColumn();
$today_waiting  = $pdo->query("SELECT COUNT(*) FROM tokens WHERE status='waiting' AND DATE(booked_at)=CURDATE()")->fetchColumn();

// Departments for display
$depts = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Doctors for display
$doctors = $pdo->query("
    SELECT d.name, d.available, dep.name AS dept_name
    FROM doctors d
    JOIN departments dep ON d.dept_id = dep.id
    ORDER BY d.available DESC, d.name ASC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital OPD Queue System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f8fb;
            color: #1a1a2e;
        }

        /* ── Navbar ── */
        .navbar {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            padding: 0 32px;
            height: 64px;
            box-shadow: 0 2px 16px rgba(13,110,253,0.2);
        }
        .navbar-brand {
            font-size: 18px;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .navbar-brand .brand-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex; align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .nav-btn {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .nav-btn-solid {
            background: white;
            color: #0d6efd;
        }
        .nav-btn-solid:hover { background: #f0f6ff; color: #0a58ca; }
        .nav-btn-outline {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.4);
        }
        .nav-btn-outline:hover { background: rgba(255,255,255,0.25); color: white; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0891b2 100%);
            padding: 80px 0 100px;
            color: white;
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }
        .hero::before {
            content: '';
            position: absolute;
            right: -100px; top: -100px;
            width: 400px; height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            left: -60px; bottom: -80px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .hero h1 {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.15;
            margin-bottom: 18px;
        }
        .hero p {
            font-size: 18px;
            opacity: 0.85;
            margin-bottom: 36px;
            line-height: 1.7;
        }
        .hero-btn-primary {
            background: white;
            color: #0d6efd;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }
        .hero-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            color: #0a58ca;
        }
        .hero-btn-ghost {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .hero-btn-ghost:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: translateY(-2px);
        }

        /* ── Hero background decoration (isolated additions) ── */
        .hero-ecg {
            position: absolute;
            top: 50%;
            left: 0;
            width: 200%;
            height: 120px;
            transform: translateY(-50%);
            opacity: 0.30;
            z-index: 0;
            animation: ecgDrift 8s linear infinite;
            will-change: transform;
        }
        @keyframes ecgDrift {
            from { transform: translate(0, -50%); }
            to   { transform: translate(-50%, -50%); }
        }

        .hero-float-icon {
            position: absolute;
            z-index: 0;
            color: rgba(255,255,255,0.30);
            animation: floatUpDown 4s ease-in-out infinite;
            will-change: transform;
            pointer-events: none;
        }
        @keyframes floatUpDown {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-28px) rotate(8deg); }
        }

        .hero-dot-grid {
            position: absolute;
            inset: 0;
            z-index: 0;
            background-image: radial-gradient(rgba(255,255,255,0.10) 1.5px, transparent 1.5px);
            background-size: 26px 26px;
            opacity: 0.6;
        }

        /* ── Live ticker ── */
        .live-ticker {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            padding: 8px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            font-size: 13px;
            font-weight: 500;
        }
        .live-dot {
            width: 8px; height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: livepulse 1.5s infinite;
        }
        @keyframes livepulse { 0%,100%{opacity:1} 50%{opacity:0.2} }

        /* ── Stats strip ── */
        .stats-strip {
            background: white;
            margin-top: -40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            padding: 32px;
            position: relative;
            z-index: 10;
        }
        .stat-item {
            text-align: center;
            padding: 0 20px;
        }
        .stat-item:not(:last-child) {
            border-right: 1px solid #e2e8f0;
        }
        .stat-num {
            font-size: 36px;
            font-weight: 800;
            color: #0d6efd;
            line-height: 1;
        }
        .stat-lbl {
            font-size: 13px;
            color: #6c757d;
            margin-top: 6px;
            font-weight: 500;
        }

        /* ── Section ── */
        .section { padding: 72px 0; }
        .section-tag {
            display: inline-block;
            background: #e8f0fe;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 14px;
        }
        .section h2 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 14px;
        }
        .section .lead {
            font-size: 16px;
            color: #6c757d;
            max-width: 540px;
        }

        /* ── How it works ── */
        .step-card {
            background: white;
            border-radius: 18px;
            padding: 28px 24px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            height: 100%;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(13,110,253,0.12);
            border-color: #0d6efd;
        }
        .step-card::before {
            content: attr(data-step);
            position: absolute;
            right: 16px; top: 12px;
            font-size: 56px;
            font-weight: 800;
            color: #f0f6ff;
            line-height: 1;
        }
        .step-icon {
            width: 52px; height: 52px;
            background: #e8f0fe;
            border-radius: 14px;
            display: flex; align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
            color: #0d6efd;
        }
        .step-card h5 {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .step-card p {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.6;
            margin: 0;
        }

        /* ── Departments ── */
        .dept-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 2px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .dept-card:hover {
            border-color: #0d6efd;
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(13,110,253,0.14);
            color: inherit;
        }
        .dept-icon {
            width: 60px; height: 60px;
            border-radius: 16px;
            background: #e8f0fe;
            color: #0d6efd;
            font-size: 26px;
            display: flex; align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }
        .dept-name {
            font-weight: 700;
            font-size: 15px;
        }

        /* ── Doctors ── */
        .doctor-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s;
        }
        .doctor-card:hover {
            box-shadow: 0 6px 24px rgba(13,110,253,0.12);
            transform: translateY(-2px);
        }
        .doctor-avatar {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0891b2);
            color: white;
            font-weight: 700;
            font-size: 20px;
            display: flex; align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .doctor-info h6 {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .doctor-info small {
            color: #6c757d;
            font-size: 12px;
        }
        .avail-badge {
            margin-left: auto;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .avail-yes { background: #d1f2e5; color: #0a3622; }
        .avail-no  { background: #e2e3e5; color: #41464b; }

        /* ── Live queue preview ── */
        .queue-preview {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            border: 1px solid #e2e8f0;
        }
        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .queue-header h5 {
            font-weight: 700;
            font-size: 16px;
            margin: 0;
        }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, #0d19fd, #0e080ad7);
            border-radius: 24px;
            padding: 60px;
            text-align: center;
            color: blue;
            position: relative;
            overflow: hidden;
        }
        .cta-section::after {
            content: '';
            position: absolute;
            right: -60px; bottom: -60px;
            width: 250px; height: 250px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .cta-section h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 14px;
        }
        .cta-section p {
            font-size: 16px;
            opacity: 0.85;
            margin-bottom: 32px;
        }

        /* ── Footer ── */
        .footer {
            background: #1a1a2e;
            color: rgba(255,255,255,0.7);
            padding: 40px 0 24px;
        }
        .footer h6 {
            color: white;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .footer a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13px;
            display: block;
            margin-bottom: 8px;
            transition: color 0.2s;
        }
        .footer a:hover { color: white; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 32px;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }

        /* ── Dept icon colors ── */
        .dept-cardiology  .dept-icon { background:#fde8e8; color:#e74c3c; }
        .dept-neurology   .dept-icon { background:#e8f0fe; color:#0d6efd; }
        .dept-orthopedics .dept-icon { background:#fef3cd; color:#f39c12; }
        .dept-general     .dept-icon { background:#d1f2e5; color:#00b894; }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="/opd-system/index.php">
        <div class="brand-icon">🏥</div>
        Hospital OPD System
    </a>
    <div class="d-flex align-items-center gap-2">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="color:rgba(255,255,255,0.8);font-size:13px;margin-right:6px">
                Hi, <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <?php if ($_SESSION['role'] === 'patient'): ?>
                <a href="pages/book_token.php"  class="nav-btn nav-btn-solid">Book Token</a>
                <a href="pages/my_tokens.php"   class="nav-btn nav-btn-outline">My Tokens</a>
                <a href="pages/view_queue.php"  class="nav-btn nav-btn-outline">Live Queue</a>
            <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                <a href="pages/doctor_dashboard.php" class="nav-btn nav-btn-solid">Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="pages/admin_dashboard.php"  class="nav-btn nav-btn-solid">Admin Panel</a>
                <a href="pages/admin_patients.php"   class="nav-btn nav-btn-outline">Patients</a>
                <a href="pages/admin_reports.php"    class="nav-btn nav-btn-outline">Reports</a>
            <?php endif; ?>
            <a href="pages/logout.php" class="nav-btn nav-btn-outline">Logout</a>
        <?php else: ?>
            <a href="pages/view_queue.php" class="nav-btn nav-btn-outline">Live Queue</a>
            <a href="pages/login.php"      class="nav-btn nav-btn-outline">Login</a>
            <a href="pages/register.php"   class="nav-btn nav-btn-solid">Register</a>
        <?php endif; ?>
    </div>
</nav>


<!-- ── HERO ── -->
<section class="hero">
    <div class="hero-dot-grid"></div>

    <svg class="hero-ecg" viewBox="0 0 800 120" preserveAspectRatio="none">
        <path d="M0 60 L120 60 L145 20 L170 100 L195 60 L800 60"
              fill="none" stroke="white" stroke-width="3"/>
        <path d="M800 60 L920 60 L945 20 L970 100 L995 60 L1600 60"
              fill="none" stroke="white" stroke-width="3"/>
    </svg>

    <i class="bi bi-heart-pulse hero-float-icon" style="top:15%; left:8%; font-size:70px; animation-delay:0s;"></i>
    <i class="bi bi-capsule hero-float-icon" style="top:65%; left:5%; font-size:44px; animation-delay:1.5s;"></i>
    <i class="bi bi-hospital hero-float-icon" style="top:20%; right:6%; font-size:60px; animation-delay:0.8s;"></i>
    <i class="bi bi-clipboard2-pulse hero-float-icon" style="top:70%; right:10%; font-size:50px; animation-delay:2.2s;"></i>

    <div class="container" style="position:relative;z-index:2">
        <div class="row align-items-center">
            <div class="col-lg-10 ">
                <div class="live-ticker">
                    <span class="live-dot"></span>
                    <?= $today_waiting ?> patients waiting right now
                </div>
                <h1>Skip the Queue.<br>Book Your OPD<br>Token Online.</h1>
                <p>No more standing in long hospital lines. Book your outpatient token online, track the live queue from your phone, and walk in right when it's your turn.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'patient'): ?>
                        <a href="pages/book_token.php"  class="hero-btn-primary">Book a Token</a>
                        <a href="pages/view_queue.php"  class="hero-btn-ghost">View Live Queue</a>
                    <?php else: ?>
                        <a href="pages/register.php"   class="hero-btn-primary">Get Started Free</a>
                        <a href="pages/view_queue.php" class="hero-btn-ghost">View Live Queue</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── STATS STRIP ── -->
<div class="container">
    <div class="stats-strip">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num"><?= $total_depts ?></div>
                    <div class="stat-lbl">Departments</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num"><?= $total_doctors ?></div>
                    <div class="stat-lbl">Doctors Available</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num"><?= $today_tokens ?></div>
                    <div class="stat-lbl">Tokens Today</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num"><?= $today_done ?></div>
                    <div class="stat-lbl">Consultations Done</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── HOW IT WORKS ── -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag">How it works</div>
            <h2>Book a token in 3 simple steps</h2>
            <p class="lead mx-auto">
                From registration to consultation — the whole process takes less than 2 minutes.
            </p>
        </div>
        <div class="row g-4">
            <?php
            $steps = [
                ['n'=>'1','icon'=>'bi-person-plus','title'=>'Create an Account',
                 'desc'=>'Register for free as a patient. Takes less than a minute — just your name, email and phone number.'],
                ['n'=>'2','icon'=>'bi-building-add','title'=>'Choose Department & Doctor',
                 'desc'=>'Select from our available departments and pick your preferred doctor. See their availability in real time.'],
                ['n'=>'3','icon'=>'bi-ticket-perforated','title'=>'Get Your Token',
                 'desc'=>'Receive your numbered token instantly. Track exactly how many patients are ahead of you in the live queue.'],
                ['n'=>'4','icon'=>'bi-check2-circle','title'=>'Walk in When Called',
                 'desc'=>'You get an SMS the moment your token is called. Walk in right on time — no waiting in the hospital.'],
            ];
            foreach ($steps as $s): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="step-card" data-step="<?= $s['n'] ?>">
                    <div class="step-icon">
                        <i class="bi <?= $s['icon'] ?>"></i>
                    </div>
                    <h5><?= $s['title'] ?></h5>
                    <p><?= $s['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── DEPARTMENTS ── -->
<section class="section" style="background:white;padding:72px 0">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col">
                <div class="section-tag">Departments</div>
                <h2 class="mb-0">Our OPD Departments</h2>
            </div>
            <div class="col-auto">
                <a href="pages/book_token.php" class="btn btn-primary">
                    Book Now →
                </a>
            </div>
        </div>
        <div class="row g-4">
            <?php
            $dept_icons = [
                'Cardiology'      => ['icon'=>'bi-heart-pulse','class'=>'dept-cardiology'],
                'Neurology'       => ['icon'=>'bi-lightning','class'=>'dept-neurology'],
                'Orthopedics'     => ['icon'=>'bi-bandaid','class'=>'dept-orthopedics'],
                'General Medicine'=> ['icon'=>'bi-clipboard2-pulse','class'=>'dept-general'],
            ];
            foreach ($depts as $dept):
                $di    = $dept_icons[$dept['name']] ?? ['icon'=>'bi-hospital','class'=>''];
                $dclass = $di['class'];
                $dicon  = $di['icon'];
            ?>
            <div class="col-6 col-md-3">
                <a href="pages/book_token.php" class="dept-card <?= $dclass ?>">
                    <div class="dept-icon">
                        <i class="bi <?= $dicon ?>"></i>
                    </div>
                    <div class="dept-name"><?= htmlspecialchars($dept['name']) ?></div>
                    <div class="text-muted mt-1" style="font-size:12px">
                        Book a token →
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── DOCTORS + LIVE QUEUE ── -->
<section class="section">
    <div class="container">
        <div class="row g-5">

            <!-- Doctors -->
            <div class="col-lg-6">
                <div class="section-tag">Our Doctors</div>
                <h2 class="mb-4">Available Today</h2>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($doctors as $doc): ?>
                    <div class="doctor-card">
                        <div class="doctor-avatar">
                            <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                        </div>
                        <div class="doctor-info">
                            <h6><?= htmlspecialchars($doc['name']) ?></h6>
                            <small><?= htmlspecialchars($doc['dept_name']) ?></small>
                        </div>
                        <span class="avail-badge <?= $doc['available'] ? 'avail-yes' : 'avail-no' ?>">
                            <?= $doc['available'] ? '● Available' : '● Unavailable' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Live Queue Preview -->
            <div class="col-lg-6">
                <div class="section-tag">Live Queue</div>
                <h2 class="mb-4">Today's Queue Status</h2>
                <div class="queue-preview">
                    <div class="queue-header">
                        <h5>Current Queue</h5>
                        <div class="live-ticker" style="font-size:12px;padding:5px 14px">
                            <span class="live-dot"></span> Live
                        </div>
                    </div>

                    <!-- Queue stats -->
                    <div class="row g-3 mb-4">
                        <?php
                        $qstats = [
                            ['label'=>'Waiting',  'val'=>$today_waiting,                           'color'=>'#f39c12','bg'=>'#fef3cd'],
                            ['label'=>'Done',     'val'=>$today_done,                              'color'=>'#00b894','bg'=>'#d1f2e5'],
                            ['label'=>'Total',    'val'=>$today_tokens,                            'color'=>'#0d6efd','bg'=>'#e8f0fe'],
                            ['label'=>'Remaining','val'=>max(0,$today_tokens - $today_done),       'color'=>'#e74c3c','bg'=>'#fde8e8'],
                        ];
                        foreach ($qstats as $qs): ?>
                        <div class="col-6">
                            <div style="background:<?= $qs['bg'] ?>;border-radius:12px;
                                        padding:16px;text-align:center">
                                <div style="font-size:28px;font-weight:800;color:<?= $qs['color'] ?>">
                                    <?= $qs['val'] ?>
                                </div>
                                <div style="font-size:12px;color:#6c757d;font-weight:500;margin-top:4px">
                                    <?= $qs['label'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <a href="pages/view_queue.php"
                       class="btn btn-primary w-100 py-3" style="border-radius:12px;font-weight:600">
                        <i class="bi bi-display"></i> View Full Live Queue
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="cta-section">
            <div style="position:relative;z-index:2">
                <div class="section-tag" style="background:rgba(255,255,255,0.2);color:white">
                    Get Started
                </div>
                <h2>Ready to skip the queue?</h2>
                <p>Join hundreds of patients who book their OPD tokens online and save hours every visit.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="pages/register.php"
                       class="hero-btn-primary">Create Free Account</a>
                    <a href="pages/login.php"
                       class="hero-btn-ghost">Already have account? Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h6>🏥 Hospital OPD System</h6>
                <p style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7">
                    A digital queue management system for hospital outpatient departments.
                    Built with PHP, MySQL and Bootstrap 5.
                </p>
            </div>
            <div class="col-md-2">
                <h6>Patient</h6>
                <a href="pages/register.php">Register</a>
                <a href="pages/login.php">Login</a>
                <a href="pages/book_token.php">Book Token</a>
                <a href="pages/view_queue.php">Live Queue</a>
            </div>
            <div class="col-md-2">
                <h6>Staff</h6>
                <a href="pages/login.php">Doctor Login</a>
                <a href="pages/login.php">Admin Login</a>
                <a href="pages/admin_reports.php">Reports</a>
            </div>
            <div class="col-md-4">
                <h6>Contact</h6>
                <p style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7">
                    📞 +91 9148393926<br>
                    📧 info@hospital.com<br>
                    🕐 OPD Hours: 9 AM – 2 PM
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            © <?= date('Y') ?> Hospital OPD Queue Management System.
            Built during internship at ApexPlanet Software Pvt. Ltd.
        </div>
    </div>
</footer>

</body>
</html>`