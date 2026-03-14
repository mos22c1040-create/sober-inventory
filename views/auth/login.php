<?php
declare(strict_types=1);
$csrfToken = $csrfToken ?? '';
$basePath  = $basePath ?? '';
$expired   = $expired ?? false;
$bp = htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $bp ?>/favicon.svg">
    <title>تسجيل الدخول — Sober POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:      #2563eb;
            --primary-dark: #1d4ed8;
            --primary-glow: rgba(37,99,235,.45);
            --accent:       #6366f1;
            --accent-glow:  rgba(99,102,241,.3);
            --bg-deep:      #050c1a;
            --bg-panel:     #080f20;
            --bg-card:      rgba(10,20,50,.85);
            --border-glow:  rgba(37,99,235,.25);
            --text-primary: #f0f6ff;
            --text-muted:   #64748b;
            --text-soft:    #94a3b8;
            --success:      #10b981;
            --danger:       #ef4444;
            --warning-bg:   rgba(245,158,11,.1);
            --warning-border: rgba(245,158,11,.3);
            --warning-text: #fbbf24;
        }

        html, body {
            height: 100%;
            font-family: 'Tajawal', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--bg-deep);
            color: var(--text-primary);
        }

        /* ─── Animated Background ─────────────────────── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .bg-canvas::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(37,99,235,.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(99,102,241,.12) 0%, transparent 55%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(16,185,129,.06) 0%, transparent 60%);
        }

        /* Grid mesh */
        .bg-canvas::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: drift 12s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(37,99,235,.25) 0%, transparent 70%);
            top: -10%; right: -10%;
            animation-duration: 15s;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(99,102,241,.2) 0%, transparent 70%);
            bottom: 5%; left: -5%;
            animation-duration: 11s;
            animation-delay: -5s;
        }
        .orb-3 {
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(16,185,129,.12) 0%, transparent 70%);
            top: 50%; left: 30%;
            animation-duration: 18s;
            animation-delay: -8s;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -20px) scale(1.05); }
            50% { transform: translate(-15px, 30px) scale(0.95); }
            75% { transform: translate(20px, 10px) scale(1.02); }
        }

        /* ─── Page Layout ─────────────────────────────── */
        .page {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
        }

        /* ─── Brand Panel ─────────────────────────────── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 48%;
            min-width: 400px;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
            border-inline-start: 1px solid var(--border-glow);
            background: linear-gradient(160deg, rgba(8,15,32,.95) 0%, rgba(5,12,26,.98) 100%);
        }

        @media (min-width: 1024px) {
            .brand-panel { display: flex; }
        }

        /* ─ Logo ─ */
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            position: relative;
            z-index: 1;
        }
        .brand-logo-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 1px rgba(99,102,241,.3), 0 0 20px var(--primary-glow), 0 4px 16px rgba(0,0,0,.4);
            position: relative;
        }
        .brand-logo-icon::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(37,99,235,.5), rgba(99,102,241,.5));
            z-index: -1;
            filter: blur(8px);
        }
        .brand-logo-text {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -.02em;
        }
        .brand-logo-sub {
            font-size: .7rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-top: .1rem;
        }

        /* ─ Headline ─ */
        .brand-main { position: relative; z-index: 1; }
        .brand-headline {
            margin-top: 4.5rem;
        }
        .brand-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        .brand-eyebrow::before, .brand-eyebrow::after {
            content: '';
            display: block;
            width: 20px;
            height: 1px;
            background: var(--accent);
            opacity: .6;
        }
        .brand-headline h2 {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1.2;
            letter-spacing: -.04em;
        }
        .brand-headline h2 .gradient-text {
            background: linear-gradient(90deg, #60a5fa 0%, #a5b4fc 50%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-headline p {
            margin-top: 1rem;
            font-size: .95rem;
            color: var(--text-soft);
            line-height: 1.75;
            max-width: 28rem;
        }

        /* ─ Feature Pills ─ */
        .brand-features {
            margin-top: 2.75rem;
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }
        .feature-pill {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .65rem 1rem;
            border-radius: .85rem;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.05);
            transition: background .2s, border-color .2s;
        }
        .feature-pill:hover {
            background: rgba(37,99,235,.08);
            border-color: rgba(37,99,235,.2);
        }
        .feature-pill-icon {
            width: 2rem;
            height: 2rem;
            border-radius: .6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .75rem;
        }
        .fp-blue  { background: rgba(37,99,235,.15);  color: #60a5fa; }
        .fp-green { background: rgba(16,185,129,.12); color: #34d399; }
        .fp-violet{ background: rgba(139,92,246,.12); color: #a78bfa; }
        .fp-orange{ background: rgba(249,115,22,.12); color: #fb923c; }

        .feature-pill-text strong {
            display: block;
            font-size: .825rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
        }
        .feature-pill-text span {
            font-size: .72rem;
            color: var(--text-muted);
        }

        /* Animated dots */
        .feature-live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
            margin-inline-start: auto;
            flex-shrink: 0;
            box-shadow: 0 0 6px var(--success);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(.7); }
        }

        /* ─ Stats ─ */
        .brand-stats {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .stat-box {
            text-align: center;
            padding: .875rem .5rem;
            border-radius: .85rem;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.05);
        }
        .stat-box-num {
            font-size: 1.4rem;
            font-weight: 900;
            background: linear-gradient(90deg, #60a5fa, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: .3rem;
        }
        .stat-box-label {
            font-size: .7rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* ─ Footer ─ */
        .brand-footer {
            position: relative;
            z-index: 1;
        }
        .brand-footer-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.06) 30%, rgba(255,255,255,.06) 70%, transparent);
            margin-bottom: 1.25rem;
        }
        .brand-footer p {
            font-size: .72rem;
            color: var(--text-muted);
        }

        /* ─── Form Panel ──────────────────────────────── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            background: rgba(5,12,26,.6);
            backdrop-filter: blur(20px);
        }

        /* Shimmering top border */
        .form-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--primary) 30%, var(--accent) 60%, transparent 100%);
            animation: shimmer-border 4s ease-in-out infinite;
        }
        @keyframes shimmer-border {
            0% { opacity: .6; }
            50% { opacity: 1; }
            100% { opacity: .6; }
        }

        .form-inner {
            width: 100%;
            max-width: 420px;
        }

        /* Mobile logo */
        .mobile-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .mobile-logo-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 24px var(--primary-glow);
            margin-bottom: .85rem;
        }
        .mobile-logo h1 {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--text-primary);
        }
        .mobile-logo p {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: .2rem;
        }
        @media (min-width: 1024px) {
            .mobile-logo { display: none; }
        }

        /* Heading */
        .form-heading { margin-bottom: 2rem; }
        .form-heading-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            background: rgba(99,102,241,.1);
            border: 1px solid rgba(99,102,241,.2);
            padding: .3rem .7rem;
            border-radius: 2rem;
            margin-bottom: .85rem;
        }
        .form-heading h2 {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--text-primary);
            letter-spacing: -.04em;
            line-height: 1.2;
        }
        .form-heading p {
            margin-top: .4rem;
            font-size: .875rem;
            color: var(--text-soft);
        }

        /* Alerts */
        .alert {
            display: none;
            align-items: flex-start;
            gap: .65rem;
            padding: .9rem 1rem;
            border-radius: .875rem;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            border: 1px solid;
            line-height: 1.5;
        }
        .alert.show { display: flex; }
        .alert-error {
            background: rgba(239,68,68,.08);
            border-color: rgba(239,68,68,.25);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16,185,129,.08);
            border-color: rgba(16,185,129,.25);
            color: #6ee7b7;
        }
        .alert-warning {
            background: var(--warning-bg);
            border-color: var(--warning-border);
            color: var(--warning-text);
        }
        .alert-icon { flex-shrink: 0; margin-top: 1px; }

        /* Fields */
        .field { margin-bottom: 1.25rem; }
        .field label {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: var(--text-soft);
            margin-bottom: .5rem;
            letter-spacing: .02em;
        }
        .input-group { position: relative; }
        .input-group .field-icon {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            right: 1rem;
            color: var(--text-muted);
            font-size: .85rem;
            pointer-events: none;
            transition: color .2s;
        }
        .input-group:focus-within .field-icon { color: var(--primary); }
        .input-group input {
            width: 100%;
            padding: .8rem 2.75rem .8rem 1rem;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            color: var(--text-primary);
            background: rgba(255,255,255,.04);
            border: 1.5px solid rgba(255,255,255,.08);
            border-radius: .75rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .input-group input::placeholder { color: var(--text-muted); font-size: .875rem; }
        .input-group input:focus {
            border-color: var(--primary);
            background: rgba(37,99,235,.06);
            box-shadow: 0 0 0 3px rgba(37,99,235,.15), 0 0 16px rgba(37,99,235,.08);
        }
        .input-group input.invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }

        /* Password toggle */
        .toggle-pass {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            left: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: .85rem;
            padding: .25rem;
            transition: color .2s;
        }
        .toggle-pass:hover { color: var(--text-soft); }

        .field-error {
            display: none;
            margin-top: .4rem;
            font-size: .75rem;
            color: #f87171;
            font-weight: 600;
        }
        .field-error.show { display: block; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            padding: .9rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: #fff;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            font-weight: 700;
            border: none;
            border-radius: .875rem;
            cursor: pointer;
            margin-top: 1.75rem;
            position: relative;
            overflow: hidden;
            transition: transform .15s, box-shadow .2s, opacity .2s;
            box-shadow: 0 0 0 1px rgba(99,102,241,.3), 0 4px 16px var(--primary-glow), 0 8px 32px rgba(0,0,0,.3);
        }
        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.14) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .2s;
        }
        .btn-submit:hover:not(:disabled)::before { opacity: 1; }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 0 0 1px rgba(99,102,241,.4), 0 8px 20px var(--primary-glow), 0 12px 40px rgba(0,0,0,.3);
        }
        .btn-submit:active:not(:disabled) {
            transform: translateY(0) scale(0.99);
        }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; }

        /* Shimmer effect on button */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
            transform: skewX(-20deg);
            animation: btn-shimmer 3.5s ease-in-out infinite;
        }
        @keyframes btn-shimmer {
            0% { left: -100%; }
            40%, 100% { left: 150%; }
        }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin-icon {
            width: 1rem; height: 1rem;
            border: 2px solid rgba(255,255,255,.25);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            flex-shrink: 0;
        }

        /* Animate in */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: slideUp .5s cubic-bezier(.22,1,.36,1) both;
        }

        /* Separator */
        .separator {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: 1.75rem 0 0;
        }
        .separator span {
            display: block;
            height: 1px;
            flex: 1;
            background: rgba(255,255,255,.06);
        }
        .separator p {
            font-size: .72rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }
    </style>
</head>
<body>

<!-- ── Animated Background ─────────────────────────── -->
<div class="bg-canvas">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<div class="page">

    <!-- ── Brand Panel ────────────────────────────────── -->
    <div class="brand-panel">
        <div class="brand-logo">
            <div class="brand-logo-icon">
                <i class="fa-solid fa-boxes-stacked" style="color:#fff;font-size:1.2rem;"></i>
            </div>
            <div>
                <span class="brand-logo-text">Sober POS</span>
                <div class="brand-logo-sub">نظام المخزون والمبيعات</div>
            </div>
        </div>

        <div class="brand-main">
            <div class="brand-headline">
                <div class="brand-eyebrow">
                    <span></span>
                    نظام نقاط البيع
                    <span></span>
                </div>
                <h2>إدارة المخزون<br><span class="gradient-text">بذكاء واحترافية</span></h2>
                <p>منصة متكاملة لإدارة المبيعات والمخزون والتقارير — كل ما تحتاجه في مكان واحد بسرعة استثنائية.</p>
            </div>

            <div class="brand-features">
                <div class="feature-pill">
                    <div class="feature-pill-icon fp-blue">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="feature-pill-text">
                        <strong>تقارير فورية</strong>
                        <span>لوحة إحصائيات تفاعلية بالوقت الفعلي</span>
                    </div>
                    <div class="feature-live-dot"></div>
                </div>
                <div class="feature-pill">
                    <div class="feature-pill-icon fp-green">
                        <i class="fa-solid fa-barcode"></i>
                    </div>
                    <div class="feature-pill-text">
                        <strong>نقطة البيع المتكاملة</strong>
                        <span>نظام باركود متقدم لإدارة المنتجات</span>
                    </div>
                </div>
                <div class="feature-pill">
                    <div class="feature-pill-icon fp-violet">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="feature-pill-text">
                        <strong>حماية متعددة المستويات</strong>
                        <span>تشفير البيانات وإدارة الأدوار والصلاحيات</span>
                    </div>
                </div>
                <div class="feature-pill">
                    <div class="feature-pill-icon fp-orange">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div class="feature-pill-text">
                        <strong>تنبيهات ذكية</strong>
                        <span>إشعارات المخزون المنخفض والتقارير الدورية</span>
                    </div>
                </div>
            </div>

            <div class="brand-stats">
                <div class="stat-box">
                    <div class="stat-box-num">∞</div>
                    <div class="stat-box-label">منتج</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-num">24/7</div>
                    <div class="stat-box-label">متاح</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-num">100%</div>
                    <div class="stat-box-label">آمن</div>
                </div>
            </div>
        </div>

        <div class="brand-footer">
            <div class="brand-footer-divider"></div>
            <p>© <?= date('Y') ?> Sober POS — جميع الحقوق محفوظة</p>
        </div>
    </div>

    <!-- ── Form Panel ──────────────────────────────────── -->
    <div class="form-panel">
        <div class="form-inner animate-in">

            <!-- Mobile logo -->
            <div class="mobile-logo">
                <div class="mobile-logo-icon">
                    <i class="fa-solid fa-boxes-stacked" style="color:#fff;font-size:1.4rem;"></i>
                </div>
                <h1>Sober POS</h1>
                <p>نظام المخزون والمبيعات</p>
            </div>

            <!-- Heading -->
            <div class="form-heading">
                <div class="form-heading-badge">
                    <i class="fa-solid fa-circle-dot" style="font-size:.55rem;color:var(--success);"></i>
                    متصل وجاهز
                </div>
                <h2>مرحباً بعودتك 👋</h2>
                <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
            </div>

            <!-- Expired session alert -->
            <?php if ($expired): ?>
            <div id="expired-banner" class="alert alert-warning show" role="alert">
                <span class="alert-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <span>انتهت جلستك تلقائياً. يرجى تسجيل الدخول مرة أخرى.</span>
            </div>
            <?php endif; ?>

            <!-- Dynamic alert -->
            <div id="alert-box" class="alert" role="alert" aria-live="polite">
                <span class="alert-icon" id="alert-icon"></span>
                <span id="alert-message"></span>
            </div>

            <!-- Form -->
            <form id="login-form" novalidate>
                <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="base_path"  value="<?= htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8') ?>">

                <!-- Email -->
                <div class="field">
                    <label for="email">البريد الإلكتروني</label>
                    <div class="input-group">
                        <i class="fa-regular fa-envelope field-icon"></i>
                        <input type="email" id="email" name="email" required
                               autocomplete="email"
                               placeholder="admin@example.com">
                    </div>
                    <p class="field-error" id="email-error"></p>
                </div>

                <!-- Password -->
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password"
                               placeholder="••••••••"
                               style="padding-left:2.75rem;">
                        <button type="button" id="toggle-password" class="toggle-pass" tabindex="-1"
                                aria-label="إظهار / إخفاء كلمة المرور">
                            <i class="fa-regular fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                    <p class="field-error" id="password-error"></p>
                </div>

                <!-- Submit -->
                <button type="submit" id="submit-btn" class="btn-submit">
                    <span id="btn-text">تسجيل الدخول</span>
                    <i class="fa-solid fa-arrow-left-long" id="btn-icon" style="font-size:.8rem;"></i>
                </button>
            </form>

            <div class="separator">
                <span></span>
                <p>نظام المخزون والمبيعات</p>
                <span></span>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    var form       = document.getElementById('login-form');
    var emailInput = document.getElementById('email');
    var passInput  = document.getElementById('password');
    var submitBtn  = document.getElementById('submit-btn');
    var btnText    = document.getElementById('btn-text');
    var alertBox   = document.getElementById('alert-box');
    var alertMsg   = document.getElementById('alert-message');
    var alertIcon  = document.getElementById('alert-icon');
    var toggleBtn  = document.getElementById('toggle-password');
    var eyeIcon    = document.getElementById('eye-icon');
    var emailErr   = document.getElementById('email-error');
    var passErr    = document.getElementById('password-error');
    var basePath   = (document.getElementById('base_path') || {}).value || '';

    /* ── Alert ─────────────────────────────── */
    function showAlert(message, type) {
        var expired = document.getElementById('expired-banner');
        if (expired) expired.style.display = 'none';

        alertBox.className = 'alert show alert-' + (type || 'error');
        alertIcon.innerHTML = type === 'success'
            ? '<i class="fa-solid fa-circle-check"></i>'
            : '<i class="fa-solid fa-circle-exclamation"></i>';
        alertMsg.textContent = message;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        alertBox.className = 'alert';
        alertBox.classList.remove('show');
    }

    /* ── Field errors ──────────────────────── */
    function showFieldError(el, input, msg) {
        el.textContent = msg;
        el.classList.add('show');
        input.classList.add('invalid');
    }
    function clearFieldError(el, input) {
        el.textContent = '';
        el.classList.remove('show');
        if (input) input.classList.remove('invalid');
    }

    /* ── Loading state ─────────────────────── */
    function setLoading(active) {
        submitBtn.disabled = active;
        var iconEl = document.getElementById('btn-icon');
        if (active) {
            btnText.textContent = 'جارٍ التحقق…';
            if (iconEl) {
                var sp = document.createElement('span');
                sp.className = 'spin-icon';
                sp.id = 'btn-icon';
                iconEl.parentNode.replaceChild(sp, iconEl);
            }
        } else {
            btnText.textContent = 'تسجيل الدخول';
            var cur = document.getElementById('btn-icon');
            if (cur) {
                var ic = document.createElement('i');
                ic.className = 'fa-solid fa-arrow-left-long';
                ic.id = 'btn-icon';
                ic.style.fontSize = '.8rem';
                cur.parentNode.replaceChild(ic, cur);
            }
        }
    }

    /* ── Validation ────────────────────────── */
    function validate(email, password) {
        var ok = true;
        clearFieldError(emailErr, emailInput);
        clearFieldError(passErr, passInput);
        if (!email) {
            showFieldError(emailErr, emailInput, 'البريد الإلكتروني مطلوب.');
            ok = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFieldError(emailErr, emailInput, 'صيغة البريد الإلكتروني غير صحيحة.');
            ok = false;
        }
        if (!password) {
            showFieldError(passErr, passInput, 'كلمة المرور مطلوبة.');
            ok = false;
        } else if (password.length < 4) {
            showFieldError(passErr, passInput, 'كلمة المرور قصيرة جداً.');
            ok = false;
        }
        return ok;
    }

    /* ── Toggle password visibility ─────────── */
    toggleBtn.addEventListener('click', function () {
        var hide = passInput.type === 'password';
        passInput.type = hide ? 'text' : 'password';
        eyeIcon.className = hide ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });

    emailInput.addEventListener('input', function () { clearFieldError(emailErr, emailInput); });
    passInput.addEventListener('input',  function () { clearFieldError(passErr, passInput); });

    /* ── Submit ────────────────────────────── */
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideAlert();

        var email    = emailInput.value.trim();
        var password = passInput.value;
        var csrf     = document.getElementById('csrf_token').value;

        if (!validate(email, password)) return;

        setLoading(true);

        var ctrl    = new AbortController();
        var timeout = setTimeout(function () { ctrl.abort(); }, 20000);
        var apiUrl  = basePath + '/api/login';

        try {
            var response = await fetch(apiUrl, {
                method:  'POST',
                signal:  ctrl.signal,
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ email: email, password: password, csrf_token: csrf }),
            });
            clearTimeout(timeout);

            var data = {};
            try { data = await response.json(); } catch (_) {}

            if (response.ok && data.success) {
                showAlert(data.message || 'تم تسجيل الدخول بنجاح.', 'success');
                setTimeout(function () {
                    window.location.href = data.redirect || basePath + '/dashboard';
                }, 800);
            } else {
                var msg = data.error
                    || (response.status === 401 ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                      : response.status >= 500  ? 'خطأ في الخادم. تحقق من الإعدادات وحاول مرة أخرى.'
                      : 'حدث خطأ غير متوقع. حاول مرة أخرى.');
                showAlert(msg, 'error');
                setLoading(false);
                if (response.status === 403) {
                    submitBtn.disabled = true;
                    submitBtn.title    = 'أعد تحميل الصفحة (F5) ثم حاول مجدداً.';
                }
            }
        } catch (err) {
            clearTimeout(timeout);
            showAlert(
                err.name === 'AbortError'
                    ? 'انتهت المهلة. تحقق من الاتصال وحاول مرة أخرى.'
                    : 'تعذر الاتصال بالخادم. تحقق من الشبكة وحاول مرة أخرى.',
                'error'
            );
            setLoading(false);
        }
    });
}());
</script>

</body>
</html>
