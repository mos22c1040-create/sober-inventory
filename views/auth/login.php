<?php
declare(strict_types=1);
$csrfToken = $csrfToken ?? '';
$basePath  = $basePath ?? '/';
$expired   = $expired ?? false;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/favicon.svg">
    <title>تسجيل الدخول — نظام المخزون</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Tajawal', 'system-ui', 'sans-serif'] } } } };</script>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-page {
            font-family: 'Tajawal', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 45%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(59, 130, 246, 0.25), transparent),
                              radial-gradient(ellipse 60% 40% at 100% 100%, rgba(37, 99, 235, 0.15), transparent);
            pointer-events: none;
        }
        .login-card-new {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2),
                        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
            padding: 2rem 2rem 2.25rem;
            text-align: center;
            position: relative;
        }
        .login-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.06' fill-rule='evenodd'%3E%3Cpath d='M0 40L40 0H20L0 20v20zM40 20V0H20L40 20z'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.8;
        }
        .login-icon-wrap {
            position: relative;
            z-index: 1;
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(8px);
        }
        .login-form-body {
            padding: 2rem 2rem 2.25rem;
        }
        .input-wrap {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-wrap:focus-within {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .btn-login {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            transition: transform 0.15s, box-shadow 0.2s;
        }
        .btn-login:hover:not(:disabled) {
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
        }
        .btn-login:active:not(:disabled) { transform: scale(0.99); }
        .spinner-login {
            width: 1.1rem;
            height: 1.1rem;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="login-page flex items-center justify-center p-4 antialiased">

    <div class="login-card-new w-full max-w-[420px] animate-slide-up relative z-10">

        <!-- Header -->
        <div class="login-header">
            <div class="login-icon-wrap">
                <i class="fa-solid fa-box-open text-2xl text-white"></i>
            </div>
            <h1 class="relative z-10 text-xl font-bold text-white tracking-tight">نظام المخزون</h1>
            <p class="relative z-10 mt-1 text-blue-100 text-sm font-medium">سجّل الدخول للوصول إلى لوحة التحكم</p>
        </div>

        <!-- Form -->
        <div class="login-form-body">

            <?php if ($expired): ?>
            <div id="expired-banner" class="rounded-xl p-3.5 mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium flex items-center gap-2" role="alert">
                <i class="fa-solid fa-clock-rotate-left shrink-0"></i>
                <span>انتهت جلستك. يرجى تسجيل الدخول مرة أخرى.</span>
            </div>
            <?php endif; ?>

            <div id="alert-box"
                 class="hidden rounded-xl p-4 mb-4 border text-sm font-medium flex items-start gap-2"
                 role="alert"
                 aria-live="polite">
                <span id="alert-icon" class="shrink-0"></span>
                <span id="alert-message"></span>
            </div>

            <form id="login-form" novalidate>
                <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="base_path" value="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-4">
                    <label for="email" class="block text-sm font-semibold mb-1.5 text-slate-700">البريد الإلكتروني</label>
                    <div class="input-wrap rounded-lg border border-slate-200 bg-slate-50/80 focus-within:border-blue-400 focus-within:bg-white">
                        <input type="email" id="email" name="email" required autocomplete="email"
                               placeholder="admin@example.com"
                               class="w-full rounded-lg px-4 py-3 text-sm bg-transparent border-0 focus:ring-0 placeholder:text-slate-400 text-slate-800">
                    </div>
                    <p id="email-error" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
                </div>

                <div class="mb-5">
                    <label for="password" class="block text-sm font-semibold mb-1.5 text-slate-700">كلمة المرور</label>
                    <div class="input-wrap relative rounded-lg border border-slate-200 bg-slate-50/80 focus-within:border-blue-400 focus-within:bg-white">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full rounded-lg px-4 py-3 pe-12 text-sm bg-transparent border-0 focus:ring-0 placeholder:text-slate-400 text-slate-800">
                        <button type="button" id="toggle-password" tabindex="-1"
                                class="absolute inset-y-0 end-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                                aria-label="إظهار / إخفاء كلمة المرور">
                            <i class="fa-regular fa-eye text-base" id="eye-icon"></i>
                        </button>
                    </div>
                    <p id="password-error" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
                </div>

                <button type="submit" id="submit-btn"
                        class="btn-login w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl text-white text-sm font-bold focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span id="btn-text">تسجيل الدخول</span>
                    <i class="fa-solid fa-arrow-left text-xs" id="btn-icon"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var form       = document.getElementById('login-form');
        var emailInput = document.getElementById('email');
        var passInput  = document.getElementById('password');
        var submitBtn  = document.getElementById('submit-btn');
        var btnText    = document.getElementById('btn-text');
        var btnIcon    = document.getElementById('btn-icon');
        var alertBox   = document.getElementById('alert-box');
        var alertMsg   = document.getElementById('alert-message');
        var alertIcon  = document.getElementById('alert-icon');
        var toggleBtn  = document.getElementById('toggle-password');
        var eyeIcon    = document.getElementById('eye-icon');
        var emailErr   = document.getElementById('email-error');
        var passErr    = document.getElementById('password-error');
        var basePath   = (document.getElementById('base_path') || {}).value || '';

        function showAlert(message, type) {
            type = type || 'error';
            var expiredBanner = document.getElementById('expired-banner');
            if (expiredBanner) expiredBanner.classList.add('hidden');
            alertBox.className = 'rounded-xl p-4 mb-4 border text-sm font-medium flex items-start gap-2 ' +
                (type === 'success' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-red-50 border-red-300 text-red-800');
            alertIcon.textContent = type === 'success' ? '\u2713' : '\u2715';
            alertMsg.textContent  = message;
            alertBox.classList.remove('hidden');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideAlert() {
            alertBox.classList.add('hidden');
        }

        function showFieldError(el, msg) {
            el.textContent = msg;
            el.classList.remove('hidden');
        }
        function clearFieldError(el) {
            el.textContent = '';
            el.classList.add('hidden');
        }

        function setLoading(active) {
            submitBtn.disabled = active;
            if (active) {
                btnText.textContent = 'جارٍ التحقق…';
                if (btnIcon && btnIcon.parentNode) {
                    var span = document.createElement('span');
                    span.className = 'spinner-login';
                    span.id = 'btn-icon';
                    btnIcon.parentNode.replaceChild(span, btnIcon);
                }
            } else {
                btnText.textContent = 'تسجيل الدخول';
                var iconEl = document.getElementById('btn-icon');
                if (iconEl) {
                    var i = document.createElement('i');
                    i.className = 'fa-solid fa-arrow-left text-xs';
                    i.id = 'btn-icon';
                    iconEl.parentNode.replaceChild(i, iconEl);
                }
            }
        }

        function validate(email, password) {
            var valid = true;
            clearFieldError(emailErr);
            clearFieldError(passErr);
            if (!email) {
                showFieldError(emailErr, 'البريد الإلكتروني مطلوب.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError(emailErr, 'صيغة البريد الإلكتروني غير صحيحة.');
                valid = false;
            }
            if (!password) {
                showFieldError(passErr, 'كلمة المرور مطلوبة.');
                valid = false;
            } else if (password.length < 4) {
                showFieldError(passErr, 'كلمة المرور قصيرة جداً.');
                valid = false;
            }
            return valid;
        }

        toggleBtn.addEventListener('click', function() {
            var isHidden = passInput.type === 'password';
            passInput.type = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden ? 'fa-regular fa-eye-slash text-base' : 'fa-regular fa-eye text-base';
        });

        emailInput.addEventListener('input', function() { clearFieldError(emailErr); });
        passInput.addEventListener('input', function() { clearFieldError(passErr); });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlert();

            var email    = emailInput.value.trim();
            var password = passInput.value;
            var csrf     = document.getElementById('csrf_token').value;

            if (!validate(email, password)) return;

            setLoading(true);

            var controller = new AbortController();
            var timeoutId  = setTimeout(function() { controller.abort(); }, 20000);
            var apiUrl     = basePath.replace(/\/$/, '') + '/api/login';

            try {
                var response = await fetch(apiUrl, {
                    method: 'POST',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        csrf_token: csrf,
                    }),
                });
                clearTimeout(timeoutId);

                var data = {};
                try { data = await response.json(); } catch (_) {}

                if (response.ok && data.success) {
                    showAlert(data.message || 'تم تسجيل الدخول بنجاح.', 'success');
                    setTimeout(function() {
                        window.location.href = data.redirect || basePath.replace(/\/$/, '') + '/dashboard';
                    }, 800);
                } else {
                    var msg = data.error ||
                        (response.status === 401 ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.' :
                         response.status >= 500 ? 'خطأ في الخادم أو قاعدة البيانات. تحقق من الإعدادات.' :
                         'حدث خطأ. حاول مرة أخرى.');
                    showAlert(msg, 'error');
                    setLoading(false);
                    if (response.status === 403) {
                        submitBtn.disabled = true;
                        submitBtn.title = 'أعد تحميل الصفحة (F5) ثم حاول مجدداً.';
                    }
                }
            } catch (networkErr) {
                clearTimeout(timeoutId);
                var isTimeout = networkErr && networkErr.name === 'AbortError';
                showAlert(
                    isTimeout ? 'انتهت المهلة. تحقق من الاتصال بالسيرفر وحاول مرة أخرى.' :
                               'تعذر الاتصال بالخادم. تحقق من الشبكة وحاول مرة أخرى.',
                    'error'
                );
                setLoading(false);
            }
        });
    })();
    </script>

</body>
</html>
