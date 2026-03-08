<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<?php
$isEdit  = !empty($user);
$userId  = $isEdit ? (int) $user['id']          : 0;
$uname   = $isEdit ? $user['username']           : '';
$uemail  = $isEdit ? $user['email']              : '';
$urole   = $isEdit ? $user['role']               : 'cashier';
$ustatus = $isEdit ? $user['status']             : 'active';
?>

<div class="max-w-lg">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="/users" class="hover:text-blue-600 transition-colors">المستخدمون</a>
        <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
        <span class="text-slate-700 font-medium"><?= $isEdit ? 'تعديل المستخدم' : 'مستخدم جديد' ?></span>
    </nav>

    <!-- Alert -->
    <div id="alert-box" class="hidden rounded-xl p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite">
        <span id="alert-icon" class="me-2"></span>
        <span id="alert-message"></span>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        <form id="user-form" novalidate>
            <input type="hidden" id="user_id"    value="<?= $userId ?>">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Username -->
            <div class="mb-5">
                <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">
                    اسم المستخدم <span class="text-red-500">*</span>
                </label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') ?>"
                       required autocomplete="username"
                       class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                       placeholder="مثال: ahmed">
                <p id="err-username" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <!-- Email -->
            <div class="mb-5">
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                    البريد الإلكتروني <span class="text-red-500">*</span>
                </label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($uemail, ENT_QUOTES, 'UTF-8') ?>"
                       required autocomplete="email"
                       class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                       placeholder="user@example.com">
                <p id="err-email" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <?php if (!$isEdit): ?>
            <!-- Password (create only) -->
            <div class="mb-5">
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                    كلمة المرور <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password"
                           required autocomplete="new-password"
                           class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 pe-12 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                           placeholder="6 أحرف على الأقل">
                    <button type="button" id="toggle-pwd" tabindex="-1"
                            class="absolute inset-y-0 end-3 flex items-center text-gray-400 hover:text-blue-500 transition-colors"
                            aria-label="إظهار/إخفاء كلمة المرور">
                        <i class="fa-regular fa-eye" id="eye-icon"></i>
                    </button>
                </div>
                <p id="err-password" class="hidden mt-1 text-xs text-red-600"></p>
            </div>
            <?php endif; ?>

            <!-- Role -->
            <div class="mb-5">
                <label for="role" class="block text-sm font-semibold text-gray-700 mb-1">الدور</label>
                <select id="role" name="role"
                        class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                    <option value="cashier" <?= $urole === 'cashier' ? 'selected' : '' ?>>كاشير</option>
                    <option value="admin"   <?= $urole === 'admin'   ? 'selected' : '' ?>>مدير</option>
                </select>
            </div>

            <!-- Status -->
            <div class="mb-7">
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">الحالة</label>
                <select id="status" name="status"
                        class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                    <option value="active"   <?= $ustatus === 'active'   ? 'selected' : '' ?>>نشط</option>
                    <option value="inactive" <?= $ustatus === 'inactive' ? 'selected' : '' ?>>موقوف</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-3">
                <button type="submit" id="submit-btn"
                        class="flex-1 py-3 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[.98] text-white text-sm font-semibold transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                    <span id="btn-text"><?= $isEdit ? 'حفظ التعديلات' : 'إنشاء المستخدم' ?></span>
                </button>
                <a href="/users"
                   class="py-3 px-6 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold transition-colors">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>

<script>
(() => {
    'use strict';

    const IS_EDIT    = <?= $isEdit ? 'true' : 'false' ?>;
    const ENDPOINT   = IS_EDIT ? '/api/users/update' : '/api/users';
    const submitBtn  = document.getElementById('submit-btn');
    const btnText    = document.getElementById('btn-text');

    // ── Alert ──────────────────────────────────────────────────────────────
    function showAlert(msg, type = 'error') {
        const box  = document.getElementById('alert-box');
        const isOk = type === 'success';
        box.className = `rounded-xl p-4 mb-6 border text-sm font-medium ${
            isOk
            ? 'bg-green-50 border-green-200 text-green-700'
            : 'bg-red-50 border-red-200 text-red-700'
        }`;
        document.getElementById('alert-icon').textContent    = isOk ? '✓' : '✕';
        document.getElementById('alert-message').textContent = msg;
        box.classList.remove('hidden');
        if (isOk) setTimeout(() => box.classList.add('hidden'), 4000);
    }

    function fieldError(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.classList.toggle('hidden', !msg);
    }

    // ── Password toggle (create form only) ────────────────────────────────
    const toggleBtn = document.getElementById('toggle-pwd');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const inp  = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    // ── Form submit ────────────────────────────────────────────────────────
    document.getElementById('user-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear previous errors
        ['username', 'email', 'password'].forEach(f => fieldError(`err-${f}`, ''));

        const username = document.getElementById('username').value.trim();
        const email    = document.getElementById('email').value.trim();
        const role     = document.getElementById('role').value;
        const status   = document.getElementById('status').value;

        let hasError = false;

        if (username.length < 3) {
            fieldError('err-username', 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل.');
            hasError = true;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            fieldError('err-email', 'يرجى إدخال بريد إلكتروني صحيح.');
            hasError = true;
        }
        if (!IS_EDIT) {
            const pwd = document.getElementById('password')?.value.trim() ?? '';
            if (pwd.length < 6) {
                fieldError('err-password', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.');
                hasError = true;
            }
        }
        if (hasError) return;

        const payload = {
            csrf_token: document.getElementById('csrf_token').value,
            username, email, role, status,
        };
        if (IS_EDIT)  payload.id = Number(document.getElementById('user_id').value);
        if (!IS_EDIT) payload.password = document.getElementById('password').value.trim();

        submitBtn.disabled = true;
        btnText.textContent = 'جارٍ الحفظ…';

        try {
            const res  = await fetch(ENDPOINT, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => { window.location.href = data.redirect ?? '/users'; }, 900);
            } else {
                showAlert(data.error ?? 'حدث خطأ غير متوقع.');
                submitBtn.disabled  = false;
                btnText.textContent = IS_EDIT ? 'حفظ التعديلات' : 'إنشاء المستخدم';
            }
        } catch {
            showAlert('تعذّر الاتصال بالخادم. تحقق من الاتصال وأعِد المحاولة.');
            submitBtn.disabled  = false;
            btnText.textContent = IS_EDIT ? 'حفظ التعديلات' : 'إنشاء المستخدم';
        }
    });
})();
</script>
