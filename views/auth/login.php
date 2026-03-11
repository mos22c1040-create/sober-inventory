<?php
declare(strict_types=1);
$csrfToken = $csrfToken ?? '';
$basePath  = $basePath ?? '/';
$expired   = $expired ?? false;
$bp = htmlspecialchars(rtrim($basePath, '/'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= $bp ?>/favicon.svg">
    <title>تسجيل الدخول — نظام المخزون</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue-950: #0c1a3a;
            --blue-900: #0f2252;
            --blue-800: #1e3a8a;
            --blue-700: #1d4ed8;
            --blue-600: #2563eb;
            --blue-500: #3b82f6;
            --blue-400: #60a5fa;
            --blue-100: #dbeafe;
            --slate-50:  #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        html, body {
            height: 100%;
            font-family: 'Tajawal', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── Layout ─────────────────────────────────────── */
        .page {
            display: flex;
            min-height: 100vh;
            background: var(--slate-50);
        }

        /* ─── Branding Panel (visually right in RTL) ─────── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 46%;
            min-width: 380px;
            background: var(--blue-950);
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .brand-panel { display: flex; }
        }

        /* subtle mesh grid */
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,.06) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* glow orbs */
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,235,.35) 0%, transparent 65%);
            top: -180px;
            right: -180px;
            pointer-events: none;
        }

        .brand-glow-bottom {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.2) 0%, transparent 65%);
            bottom: -120px;
            left: -100px;
            pointer-events: none;
        }

        .brand-content { position: relative; z-index: 1; }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
        }
        .brand-logo-icon {
            width: 2.75rem;
            height: 2.75rem;
            background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 4px rgba(59,130,246,.15), 0 4px 12px rgba(37,99,235,.4);
        }
        .brand-logo-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.02em;
        }

        .brand-headline {
            margin-top: 4rem;
        }
        .brand-headline h2 {
            font-size: 2.25rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.25;
            letter-spacing: -.03em;
        }
        .brand-headline h2 span {
            background: linear-gradient(90deg, var(--blue-400), #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-headline p {
            margin-top: .75rem;
            font-size: .95rem;
            color: var(--slate-400);
            line-height: 1.7;
            max-width: 26rem;
        }

        .brand-features {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: .85rem;
        }
        .feature-icon {
            width: 2.2rem;
            height: 2.2rem;
            border-radius: .6rem;
            background: rgba(59,130,246,.12);
            border: 1px solid rgba(59,130,246,.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--blue-400);
            font-size: .8rem;
        }
        .feature-item span {
            font-size: .875rem;
            color: var(--slate-300);
            font-weight: 500;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
        }
        .brand-divider {
            height: 1px;
            background: rgba(255,255,255,.07);
            margin-bottom: 1.25rem;
        }
        .brand-footer p {
            font-size: .75rem;
            color: var(--slate-500);
        }

        /* ─── Form Panel ─────────────────────────────────── */
        .form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #fff;
            position: relative;
        }

        /* subtle top accent line */
        .form-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--blue-600), var(--blue-400));
        }

        .form-inner {
            width: 100%;
            max-width: 400px;
        }

        /* Mobile-only logo */
        .mobile-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2.25rem;
        }
        .mobile-logo-icon {
            width: 3.25rem;
            height: 3.25rem;
            background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(37,99,235,.35);
            margin-bottom: .75rem;
        }
        .mobile-logo h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--slate-900);
        }
        @media (min-width: 1024px) {
            .mobile-logo { display: none; }
        }

        /* Form heading */
        .form-heading {
            margin-bottom: 2rem;
        }
        .form-heading h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -.03em;
        }
        .form-heading p {
            margin-top: .35rem;
            font-size: .875rem;
            color: var(--slate-500);
        }

        /* Alert */
        .alert {
            display: none;
            align-items: flex-start;
            gap: .6rem;
            padding: .875rem 1rem;
            border-radius: .75rem;
            font-size: .875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            border: 1px solid;
            line-height: 1.5;
        }
        .alert.show { display: flex; }
        .alert-error  { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
        .alert-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .alert-icon { flex-shrink: 0; margin-top: 1px; }

        /* Field group */
        .field { margin-bottom: 1.25rem; }
        .field label {
            display: block;
            font-size: .8125rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: .45rem;
        }

        .input-group {
            position: relative;
        }
        .input-group .field-icon {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            right: .875rem;
            color: var(--slate-400);
            font-size: .875rem;
            pointer-events: none;
            transition: color .15s;
        }
        .input-group:focus-within .field-icon {
            color: var(--blue-500);
        }
        .input-group input {
            width: 100%;
            padding: .75rem 2.5rem .75rem .875rem;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            color: var(--slate-900);
            background: var(--slate-50);
            border: 1.5px solid var(--slate-200);
            border-radius: .625rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .input-group input::placeholder { color: var(--slate-400); font-size: .875rem; }
        .input-group input:focus {
            border-color: var(--blue-500);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }
        .input-group input.invalid {
            border-color: #f87171;
            box-shadow: 0 0 0 3px rgba(248,113,113,.12);
        }

        .toggle-pass {
            position: absolute;
            top: 50%; transform: translateY(-50%);
            left: .875rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--slate-400);
            font-size: .875rem;
            padding: .25rem;
            transition: color .15s;
        }
        .toggle-pass:hover { color: var(--slate-600); }

        .field-error {
            display: none;
            margin-top: .35rem;
            font-size: .75rem;
            color: #dc2626;
            font-weight: 500;
        }
        .field-error.show { display: block; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .875rem 1.5rem;
            background: var(--blue-600);
            color: #fff;
            font-family: 'Tajawal', system-ui, sans-serif;
            font-size: .9375rem;
            font-weight: 700;
            border: none;
            border-radius: .75rem;
            cursor: pointer;
            margin-top: 1.75rem;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 4px 12px rgba(37,99,235,.25);
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--blue-700);
            box-shadow: 0 2px 4px rgba(0,0,0,.1), 0 8px 20px rgba(37,99,235,.35);
        }
        .btn-submit:active:not(:disabled) {
            transform: scale(0.99);
        }
        .btn-submit:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin-icon {
            width: 1rem; height: 1rem;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            flex-shrink: 0;
        }

        /* Slide-up animation */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: slideUp .4s cubic-bezier(.22,1,.36,1) both;
        }

        /* Dots separator */
        .separator {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: 1.75rem 0 0;
        }
        .separator span {
            display: block;
            height: 1px;
            flex: 1;
            background: var(--slate-100);
        }
        .separator p {
            font-size: .75rem;
            color: var(--slate-400);
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="page">

    <!-- ── Branding Panel ──────────────────────────────────── -->
    <div class="brand-panel">
        <div class="brand-glow-bottom"></div>

        <div class="brand-content">
            <div class="brand-logo">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-boxes-stacked" style="color:#fff;font-size:1.1rem;"></i>
                </div>
                <span class="brand-logo-text">Sober POS</span>
            </div>

            <div class="brand-headline">
                <h2>إدارة المخزون<br><span>بذكاء واحترافية</span></h2>
                <p>منصة متكاملة لإدارة المبيعات والمخزون والتقارير — كل شيء في مكان واحد.</p>
            </div>

            <div class="brand-features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <span>تقارير فورية ولوحة إحصائيات تفاعلية</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-barcode"></i></div>
                    <span>نظام باركود متقدم لإدارة المنتجات</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <span>حماية متعددة المستويات للبيانات</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-users"></i></div>
                    <span>إدارة الصلاحيات والمستخدمين بمرونة</span>
                </div>
            </div>
        </div>

        <div class="brand-footer">
            <div class="brand-divider"></div>
            <p>© <?= date('Y') ?> Sober POS — نظام نقاط البيع والمخزون</p>
        </div>
    </div>

    <!-- ── Form Panel ──────────────────────────────────────── -->
    <div class="form-panel">
        <div class="form-inner animate-in">

            <!-- Mobile logo -->
            <div class="mobile-logo">
                <div class="mobile-logo-icon">
                    <i class="fa-solid fa-boxes-stacked" style="color:#fff;font-size:1.3rem;"></i>
                </div>
                <h1>Sober POS</h1>
            </div>

            <!-- Heading -->
            <div class="form-heading">
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
                               style="padding-left:2.5rem;">
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
