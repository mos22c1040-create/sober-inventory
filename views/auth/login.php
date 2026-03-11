<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <title>تسجيل الدخول — نظام المخزون</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Tajawal', 'system-ui', 'sans-serif'] } } } };</script>
    <link rel="stylesheet" href="/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center p-4 antialiased" style="font-family: 'Tajawal', sans-serif; background: rgb(var(--background)); color: rgb(var(--foreground));">

    <div class="w-full max-w-md login-card bg-white overflow-hidden animate-slide-up">

        <!-- Header -->
        <div class="login-bg px-6 py-10 text-center text-white relative" style="background: rgb(var(--primary));">
            <div class="relative z-10 inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 backdrop-blur border border-white/20 mb-5 shadow-lg">
                <i class="fa-solid fa-box-open text-3xl text-white"></i>
            </div>
            <h1 class="relative z-10 text-2xl font-bold tracking-tight text-white drop-shadow-sm">نظام المخزون</h1>
            <p class="relative z-10 mt-1.5 text-blue-100/90 text-sm font-medium">سجّل الدخول للوصول إلى لوحة التحكم</p>
        </div>

        <!-- Form -->
        <div class="p-8 pt-7">

            <div id="alert-box"
                 class="hidden rounded-xl p-4 mb-5 border text-sm font-medium flex items-start gap-2"
                 role="alert"
                 aria-live="polite">
                <span id="alert-icon" class="shrink-0"></span>
                <span id="alert-message"></span>
            </div>

            <form id="login-form" novalidate>
                <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-5">
                    <label for="email" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           placeholder="admin@example.com"
                           class="app-input w-full rounded-lg border px-4 py-3 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                    <p id="email-error" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">كلمة المرور</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="app-input w-full rounded-lg border px-4 py-3 pe-12 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                        <button type="button" id="toggle-password" tabindex="-1"
                                class="absolute inset-y-0 end-3 flex items-center transition-colors duration-200 cursor-pointer" style="color: rgb(var(--muted-foreground));"
                                aria-label="إظهار / إخفاء كلمة المرور">
                            <i class="fa-regular fa-eye text-base" id="eye-icon"></i>
                        </button>
                    </div>
                    <p id="password-error" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
                </div>

                <button type="submit" id="submit-btn"
                        class="btn-primary w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-lg text-sm font-bold focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                    <span id="btn-text">تسجيل الدخول</span>
                    <i class="fa-solid fa-arrow-left text-xs" id="btn-icon"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- ── Fetch API Login Script ─────────────────────────────────── -->
    <script>
    (() => {
        'use strict';

        // ── DOM references ──────────────────────────────────────────
        const form        = document.getElementById('login-form');
        const emailInput  = document.getElementById('email');
        const passInput   = document.getElementById('password');
        const submitBtn   = document.getElementById('submit-btn');
        const btnText     = document.getElementById('btn-text');
        const btnIcon     = document.getElementById('btn-icon');
        const alertBox    = document.getElementById('alert-box');
        const alertMsg    = document.getElementById('alert-message');
        const alertIcon   = document.getElementById('alert-icon');
        const toggleBtn   = document.getElementById('toggle-password');
        const eyeIcon     = document.getElementById('eye-icon');
        const emailErr    = document.getElementById('email-error');
        const passErr     = document.getElementById('password-error');

        // ── Alert helper ────────────────────────────────────────────
        /**
         * Show a styled alert banner above the form.
         * @param {string}  message
         * @param {'error'|'success'} type
         */
        function showAlert(message, type = 'error') {
            alertBox.className = [
                'rounded-xl p-4 mb-6 border text-sm font-medium flex items-start gap-2',
                type === 'success'
                    ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                    : 'bg-red-50 border-red-300 text-red-800',
            ].join(' ');
            alertIcon.textContent = type === 'success' ? '✓' : '✕';
            alertMsg.textContent  = message;
            alertBox.classList.remove('hidden');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideAlert() {
            alertBox.classList.add('hidden');
        }

        // ── Inline field error ──────────────────────────────────────
        function showFieldError(el, msg) {
            el.textContent = msg;
            el.classList.remove('hidden');
        }
        function clearFieldError(el) {
            el.textContent = '';
            el.classList.add('hidden');
        }

        // ── Loading state ───────────────────────────────────────────
        function setLoading(active) {
            submitBtn.disabled = active;
            const iconEl = document.getElementById('btn-icon');
            if (active) {
                btnText.textContent = 'جارٍ التحقق…';
                if (iconEl && iconEl.parentNode) {
                    iconEl.outerHTML = '<span class="spinner" id="btn-icon" aria-hidden="true"></span>';
                }
            } else {
                btnText.textContent = 'تسجيل الدخول';
                if (iconEl && iconEl.parentNode) {
                    iconEl.outerHTML = '<i class="fa-solid fa-arrow-left text-xs" id="btn-icon"></i>';
                } else {
                    document.getElementById('btn-icon')?.remove();
                    submitBtn.insertAdjacentHTML('beforeend', '<i class="fa-solid fa-arrow-left text-xs" id="btn-icon"></i>');
                }
            }
        }

        // ── Client-side validation ──────────────────────────────────
        function validate(email, password) {
            let valid = true;
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

        // ── Password visibility toggle ──────────────────────────────
        toggleBtn.addEventListener('click', () => {
            const isHidden = passInput.type === 'password';
            passInput.type      = isHidden ? 'text' : 'password';
            eyeIcon.className   = isHidden
                ? 'fa-regular fa-eye-slash text-base'
                : 'fa-regular fa-eye text-base';
        });

        // Clear field errors on typing
        emailInput.addEventListener('input', () => clearFieldError(emailErr));
        passInput.addEventListener('input',  () => clearFieldError(passErr));

        // ── Form submit ─────────────────────────────────────────────
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();

            const email    = emailInput.value.trim();
            const password = passInput.value;
            const csrf     = document.getElementById('csrf_token').value;

            // Client-side guard
            if (!validate(email, password)) return;

            setLoading(true);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 20000);

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        email:      email,
                        password:   password,
                        csrf_token: csrf,
                    }),
                });
                clearTimeout(timeoutId);

                // Parse JSON regardless of status code
                let data = {};
                try { data = await response.json(); } catch (_) { /* no-op */ }

                if (response.ok && data.success) {
                    clearTimeout(timeoutId);
                    showAlert(data.message ?? 'تم تسجيل الدخول بنجاح.', 'success');
                    // Brief pause so the user sees the success banner, then redirect
                    setTimeout(() => {
                        window.location.href = data.redirect ?? '/dashboard';
                    }, 900);
                } else {
                    // ── Error path ─────────────────────────────────
                    const msg = data.error
                        ?? (response.status === 401
                            ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                            : response.status >= 500
                                ? 'خطأ في الخادم أو قاعدة البيانات. تحقق من إعدادات السيرفر وقاعدة البيانات (Supabase).'
                                : 'حدث خطأ. حاول مرة أخرى.');
                    showAlert(msg, 'error');
                    setLoading(false);

                    // If 403, the CSRF might be stale — don't allow re-submit until reload
                    if (response.status === 403) {
                        submitBtn.disabled = true;
                        submitBtn.title    = 'أعِد تحميل الصفحة (F5) ثم حاول مجدداً.';
                    }
                }
            } catch (networkErr) {
                clearTimeout(timeoutId);
                const isTimeout = networkErr && networkErr.name === 'AbortError';
                showAlert(
                    isTimeout
                        ? 'انتهت المهلة. تحقق من الاتصال بالسيرفر وحاول مرة أخرى.'
                        : 'تعذّر الاتصال بالخادم. تحقق من الشبكة وحاول مرة أخرى.',
                    'error'
                );
                setLoading(false);
            }
        });

    })();
    </script>

</body>
</html>
