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
            --primary:      #4f46e5;
            --primary-dark: #4338ca;
            --primary-glow: rgba(79,70,229,.35);
            --accent:       #6366f1;
            --bg-body:      #f9fafb;
            --bg-card:      #ffffff;
            --border:       #e5e7eb;
            --border-focus: #4f46e5;
            --text-primary: #111827;
            --text-muted:   #6b7280;
            --text-soft:    #9ca3af;
            --success:      #10b981;
            --danger:       #ef4444;
            --warning-bg:   rgba(245,158,11,.08);
            --warning-border: rgba(245,158,11,.25);
            --warning-text: #92400e;
        }

        html, body {
            height: 100%;
            font-family: 'Tajawal', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--bg-body);
            color: var(--text-primary);
        }

        /* ─── Background Pattern ─────────────── */
        .bg-pattern {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background:
                radial-gradient(ellipse 70% 55% at 20% 5%,  rgba(79,70,229,.07)  0%, transparent 60%),
                radial-gradient(ellipse 55% 45% at 82% 90%, rgba(99,102,241,.05) 0%, transparent 55%);
        }
        .bg-pattern::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(79,70,229,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(79,70,229,.025) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* ─── Page Layout ──────────────────────── */
        .page {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
        }

        /* ─── Brand Panel ──────────────────────── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 46%;
            min-width: 400px;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
            background: rgb(10, 14, 26);
            border-inline-start: 1px solid rgba(79,70,229,.12);
        }
        @media (min-width: 1024px) { .brand-panel { display: flex; } }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79,70,229,.16) 0%, transparent 70%);
            pointer-events: none;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16,185,129,.10) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ─ Logo ─ */
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: .875rem;
            position: relative;
            z-index: 1;
        }
        .brand-logo-icon {
            width: 2.75rem;
            height: 2.75rem;
            background: var(--primary);
            border-radius: .875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px var(--primary-glow);
        }
        .brand-logo-text {
            font-size: 1.1rem;
            font-weight: 800;
            color: #f9fafb;
            letter-spacing: -.02em;
        }
        .brand-logo-sub {
            font-size: .7rem;
            color: #4b5563;
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-top: .15rem;
        }

        /* ─ Headline ─ */
        .brand-main { position: relative; z-index: 1; }
        .brand-headline { margin-top: 4rem; }
        .brand-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #818cf8;
            background: rgba(99,102,241,.12);
            border: 1px solid rgba(99,102,241,.2);
            padding: .3rem .75rem;
            border-radius: 9999px;
            margin-bottom: 1.25rem;
        }
        .brand-headline h2 {
            font-size: 2.25rem;
            font-weight: 900;
            color: #f9fafb;
            line-height: 1.22;
            letter-spacing: -.04em;
        }
        .brand-headline h2 .highlight {
            background: linear-gradient(90deg, #818cf8, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-headline p {
            margin-top: 1rem;
            font-size: .9rem;
            color: #6b7280;
            line-height: 1.8;
            max-width: 28rem;
        }

        /* ─ Feature List ─ */
        .brand-features {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: .625rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: .875rem;
            padding: .75rem 1rem;
            border-radius: .875rem;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            transition: background .2s, border-color .2s;
        }
        .feature-item:hover {
            background: rgba(79,70,229,.06);
            border-color: rgba(79,70,229,.15);
        }
        .feature-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .8rem;
        }
        .fi-blue   { background: rgba(79,70,229,.14);  color: #818cf8; }
        .fi-green  { background: rgba(16,185,129,.12); color: #34d399; }
        .fi-violet { background: rgba(139,92,246,.12); color: #a78bfa; }
        .fi-orange { background: rgba(249,115,22,.12); color: #fb923c; }

        .feature-text strong {
            display: block;
            font-size: .825rem;
            font-weight: 700;
            color: #e5e7eb;
        }
        .feature-text span {
            font-size: .72rem;
            color: #4b5563;
        }
        .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--success);
            margin-inline-start: auto;
            flex-shrink: 0;
            box-shadow: 0 0 6px var(--success);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(.7); }
        }

        /* ─ Stats ─ */
        .brand-stats {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .875rem;
        }
        .stat-box {
            text-align: center;
            padding: 1rem .5rem;
            border-radius: .875rem;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
        }
        .stat-box-num {
            font-size: 1.35rem;
            font-weight: 900;
            background: linear-gradient(90deg, #818cf8, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: .3rem;
        }
        .stat-box-label {
            font-size: .7rem;
            color: #4b5563;
            font-weight: 600;
        }

        /* ─ Footer ─ */
        .brand-footer { position: relative; z-index: 1; }
        .brand-footer p { font-size: .72rem; color: #374151; }

        /* ─── Form Panel ───────────────────────── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            background: #ffffff;
        }
        @media (min-width: 1024px) {
            .form-panel {
                background: #f9fafb;
            }
        }

        .form-inner {
            width: 100%;
            max-width: 420px;
        }

        /* Mobile Logo */
        .mobile-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .mobile-logo-icon {
            width: 3.25rem;
            height: 3.25rem;
            background: var(--primary);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 18px var(--primary-glow);
            margin-bottom: .875rem;
        }
        .mobile-logo h1 {
            font-size: 1.25rem;
            font-weight: 900;
            color: var(--text-primary);
            letter-spacing: -.03em;
        }
        .mobile-logo p {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: .2rem;
        }
        @media (min-width: 1024px) { .mobile-logo { display: none; } }

        /* Form Card */
        .form-card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            box-shadow:
                0 1px 3px rgba(0,0,0,.06),
                0 4px 16px rgba(0,0,0,.06),
                0 12px 40px rgba(0,0,0,.04);
            padding: 2.25rem 2rem;
        }

        /* Heading */
        .form-heading { margin-bottom: 1.875rem; }
        .form-heading-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            background: rgba(99,102,241,.08);
            border: 1px solid rgba(99,102,241,.18);
            padding: .3rem .75rem;
            border-radius: 9999px;
            margin-bottom: .875rem;
        }
        .form-heading h2 {
            font-size: 1.625rem;
            font-weight: 900;
            color: var(--text-primary);
            letter-spacing: -.04em;
            line-height: 1.2;
        }
        .form-heading p {
            margin-top: .4rem;
            font-size: .875rem;
            color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            display: none;
            align-items: flex-start;
            gap: .65rem;
            padding: .875rem 1rem;
            border-radius: .875rem;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            border: 1px solid;
            line-height: 1.55;
        }
        .alert.show { display: flex; }
        .alert-error {
            background: rgba(239,68,68,.05);
            border-color: rgba(239,68,68,.2);
            color: #b91c1c;
        }
        .alert-success {
            background: rgba(16,185,129,.05);
            border-color: rgba(16,185,129,.2);
            color: #065f46;
        }
        .alert-warning {
            background: var(--warning-bg);
            border-color: var(--warning-border);
            color: var(--warning-text);
        }
        .alert-icon { flex-shrink: 0; margin-top: 2px; }

        /* Fields */
        .field { margin-bottom: 1.125rem; }
        .field label {
            display: block;
            font-size: .8125rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: .5rem;
            letter-spacing: .01em;
        }
        .input-wrap { position: relative; }
        .input-wrap .field-icon {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            right: .9375rem;
            color: var(--text-soft);
            font-size: .8rem;
            pointer-events: none;
            transition: color .2s;
        }
        .input-wrap:focus-within .field-icon { color: var(--primary); }

        .input-wrap input {
            width: 100%;
            padding: .75rem 2.75rem .75rem .9375rem;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            color: var(--text-primary);
            background: #f9fafb;
            border: 1.5px solid var(--border);
            border-radius: .875rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .input-wrap input::placeholder { color: var(--text-soft); font-size: .875rem; }
        .input-wrap input:hover:not(:focus) { border-color: #d1d5db; background: #ffffff; }
        .input-wrap input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(79,70,229,.10);
        }
        .input-wrap input.invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 4px rgba(239,68,68,.08);
        }

        /* Password toggle */
        .toggle-pass {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            left: .9375rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-soft);
            font-size: .8rem;
            padding: .25rem;
            transition: color .2s;
        }
        .toggle-pass:hover { color: var(--text-muted); }

        .field-error {
            display: none;
            margin-top: .375rem;
            font-size: .75rem;
            color: #dc2626;
            font-weight: 600;
        }
        .field-error.show { display: block; }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            padding: .8125rem 1.5rem;
            background: var(--primary);
            color: #fff;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            font-weight: 700;
            border: none;
            border-radius: .875rem;
            cursor: pointer;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: background .15s, box-shadow .2s, transform .1s;
            box-shadow: 0 4px 16px rgba(79,70,229,.32), 0 1px 3px rgba(0,0,0,.1);
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            box-shadow: 0 6px 22px rgba(79,70,229,.40), 0 2px 6px rgba(0,0,0,.1);
            transform: translateY(-1px);
        }
        .btn-submit:active:not(:disabled) { transform: translateY(0) scale(0.99); }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin-icon {
            width: 1rem; height: 1rem;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .65s linear infinite;
            flex-shrink: 0;
        }

        /* Slide up */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: slideUp .45s cubic-bezier(.22,1,.36,1) both; }

        /* Footer */
        .form-footer-note {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .75rem;
            color: var(--text-soft);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    </style>
</head>
<body>

<div class="bg-pattern"></div>

<div class="page">

    <!-- ── Brand Panel ────────────────────────────── -->
    <div class="brand-panel">
        <div class="brand-logo">
            <div class="brand-logo-icon">
                <i class="fa-solid fa-boxes-stacked" style="color:#fff; font-size:1.1rem;"></i>
            </div>
            <div>
                <span class="brand-logo-text">Sober POS</span>
                <div class="brand-logo-sub">نظام المخزون والمبيعات</div>
            </div>
        </div>

        <div class="brand-main">
            <div class="brand-headline">
                <span class="brand-eyebrow">
                    <i class="fa-solid fa-bolt" style="font-size:.6rem;"></i>
                    نظام نقاط البيع
                </span>
                <h2>إدارة المخزون<br><span class="highlight">بذكاء واحترافية</span></h2>
                <p>منصة متكاملة لإدارة المبيعات والمخزون والتقارير — كل ما تحتاجه في مكان واحد.</p>
            </div>

            <div class="brand-features">
                <div class="feature-item">
                    <div class="feature-icon fi-blue"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="feature-text">
                        <strong>تقارير فورية</strong>
                        <span>إحصائيات تفاعلية بالوقت الفعلي</span>
                    </div>
                    <div class="live-dot"></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon fi-green"><i class="fa-solid fa-barcode"></i></div>
                    <div class="feature-text">
                        <strong>نقطة البيع المتكاملة</strong>
                        <span>نظام باركود متقدم لإدارة المنتجات</span>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon fi-violet"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="feature-text">
                        <strong>حماية متعددة المستويات</strong>
                        <span>تشفير البيانات وإدارة الأدوار</span>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon fi-orange"><i class="fa-solid fa-bell"></i></div>
                    <div class="feature-text">
                        <strong>تنبيهات ذكية</strong>
                        <span>إشعارات المخزون المنخفض تلقائياً</span>
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
            <p>© <?= date('Y') ?> Sober POS — جميع الحقوق محفوظة</p>
        </div>
    </div>

    <!-- ── Form Panel ─────────────────────────────── -->
    <div class="form-panel">
        <div class="form-inner animate-in">

            <!-- Mobile Logo -->
            <div class="mobile-logo">
                <div class="mobile-logo-icon">
                    <i class="fa-solid fa-boxes-stacked" style="color:#fff; font-size:1.3rem;"></i>
                </div>
                <h1>Sober POS</h1>
                <p>نظام المخزون والمبيعات</p>
            </div>

            <div class="form-card">
                <!-- Heading -->
                <div class="form-heading">
                    <span class="form-heading-eyebrow">
                        <i class="fa-solid fa-circle" style="font-size:.45rem; color: var(--success);"></i>
                        متصل وجاهز
                    </span>
                    <h2>أهلاً بعودتك</h2>
                    <p>أدخل بياناتك للوصول إلى لوحة التحكم</p>
                </div>

                <!-- Expired Session Alert -->
                <?php if ($expired): ?>
                <div id="expired-banner" class="alert alert-warning show" role="alert">
                    <span class="alert-icon"><i class="fa-regular fa-clock"></i></span>
                    <span>انتهت جلستك تلقائياً. يرجى تسجيل الدخول مرة أخرى.</span>
                </div>
                <?php endif; ?>

                <!-- Dynamic Alert -->
                <div id="alert-box" class="alert" role="alert" aria-live="polite">
                    <span class="alert-icon" id="alert-icon"></span>
                    <span id="alert-message"></span>
                </div>

                <!-- Login Form -->
                <form id="login-form" novalidate>
                    <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="base_path"  value="<?= htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Email Field -->
                    <div class="field">
                        <label for="email">البريد الإلكتروني</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input type="email" id="email" name="email" required
                                   autocomplete="email"
                                   placeholder="admin@example.com">
                        </div>
                        <p class="field-error" id="email-error"></p>
                    </div>

                    <!-- Password Field -->
                    <div class="field">
                        <label for="password">كلمة المرور</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="password" name="password" required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   style="padding-left: 2.75rem;">
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
                        <i class="fa-solid fa-arrow-left text-sm" id="btn-icon"></i>
                    </button>
                </form>
            </div>

            <div class="form-footer-note">
                Sober POS &mdash; نظام المخزون والمبيعات © <?= date('Y') ?>
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
    }

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
                ic.className = 'fa-solid fa-arrow-left text-sm';
                ic.id = 'btn-icon';
                cur.parentNode.replaceChild(ic, cur);
            }
        }
    }

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

    toggleBtn.addEventListener('click', function () {
        var hide = passInput.type === 'password';
        passInput.type = hide ? 'text' : 'password';
        eyeIcon.className = hide ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });

    emailInput.addEventListener('input', function () { clearFieldError(emailErr, emailInput); });
    passInput.addEventListener('input',  function () { clearFieldError(passErr,  passInput); });

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
                }, 700);
            } else {
                var msg = data.error
                    || (response.status === 401 ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                      : response.status >= 500  ? 'خطأ في الخادم. تحقق من الإعدادات وحاول مرة أخرى.'
                      : 'حدث خطأ غير متوقع. حاول مرة أخرى.');
                showAlert(msg, 'error');
                setLoading(false);
                if (response.status === 403) {
                    submitBtn.disabled = true;
                    submitBtn.title = 'أعد تحميل الصفحة (F5) ثم حاول مجدداً.';
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
