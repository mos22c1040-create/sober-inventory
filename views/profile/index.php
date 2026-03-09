<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<?php
$username = $user['username'] ?? '';
$email    = $user['email'] ?? '';
?>

<div class="max-w-lg">
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
        <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
        <span class="text-slate-700 font-medium">حسابي</span>
    </nav>

    <div id="alert-box" class="hidden rounded-xl p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite">
        <span id="alert-icon" class="me-2"></span>
        <span id="alert-message"></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 mb-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-user text-blue-500"></i>
            بيانات الحساب
        </h2>
        <p class="text-sm text-slate-600 mb-1"><strong>اسم المستخدم:</strong> <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-sm text-slate-600"><strong>البريد:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-key text-amber-500"></i>
            تغيير كلمة المرور
        </h2>

        <form id="password-form" novalidate>
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-5">
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1">كلمة المرور الحالية <span class="text-red-500">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                       placeholder="أدخل كلمة المرور الحالية">
                <p id="err-current" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="mb-5">
                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-1">كلمة المرور الجديدة <span class="text-red-500">*</span></label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="6"
                       class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                       placeholder="6 أحرف على الأقل">
                <p id="err-new" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                       class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"
                       placeholder="أعد إدخال كلمة المرور الجديدة">
                <p id="err-confirm" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" id="submit-btn" class="w-full sm:flex-1 touch-target min-h-[44px] rounded-xl bg-blue-600 text-white font-semibold py-2.5 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors btn-primary cursor-pointer">
                <i class="fa-solid fa-check me-2"></i>
                حفظ كلمة المرور
            </button>
            <a href="/dashboard" class="w-full sm:flex-1 min-h-[44px] flex items-center justify-center rounded-xl border-2 border-slate-200 text-slate-600 font-semibold py-2.5 hover:bg-slate-50 hover:border-slate-300 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-colors cursor-pointer">
                <i class="fa-solid fa-arrow-right me-2"></i>
                العودة للوحة التحكم
            </a>
        </div>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('password-form');
    var alertBox = document.getElementById('alert-box');
    var alertIcon = document.getElementById('alert-icon');
    var alertMsg = document.getElementById('alert-message');
    var submitBtn = document.getElementById('submit-btn');

    function showAlert(message, isError) {
        alertBox.classList.remove('hidden');
        alertBox.className = 'rounded-xl p-4 mb-6 border text-sm font-medium ' + (isError ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800');
        alertIcon.className = 'me-2 ' + (isError ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check');
        alertMsg.textContent = message;
    }

    function hideAlert() {
        alertBox.classList.add('hidden');
    }

    ['current_password', 'new_password', 'confirm_password'].forEach(function(id) {
        var el = document.getElementById('err-' + (id === 'current_password' ? 'current' : id === 'new_password' ? 'new' : 'confirm'));
        if (!el) return;
        document.getElementById(id).addEventListener('input', function() {
            el.classList.add('hidden');
            hideAlert();
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert();
        var current = document.getElementById('current_password').value;
        var newPwd = document.getElementById('new_password').value;
        var confirmPwd = document.getElementById('confirm_password').value;
        var csrf = document.getElementById('csrf_token').value;

        var errCurrent = document.getElementById('err-current');
        var errNew = document.getElementById('err-new');
        var errConfirm = document.getElementById('err-confirm');
        errCurrent.classList.add('hidden');
        errNew.classList.add('hidden');
        errConfirm.classList.add('hidden');

        if (!current.trim()) {
            errCurrent.textContent = 'أدخل كلمة المرور الحالية';
            errCurrent.classList.remove('hidden');
            return;
        }
        if (newPwd.length < 6) {
            errNew.textContent = 'كلمة المرور الجديدة 6 أحرف على الأقل';
            errNew.classList.remove('hidden');
            return;
        }
        if (newPwd !== confirmPwd) {
            errConfirm.textContent = 'كلمة المرور وتأكيدها غير متطابقين';
            errConfirm.classList.remove('hidden');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> جاري الحفظ...';

        fetch('/api/profile/password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: current,
                new_password: newPwd,
                confirm_password: confirmPwd,
                csrf_token: csrf
            })
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, status: r.status, data: data }; }); })
        .then(function(res) {
            if (res.ok && res.data.success) {
                showAlert(res.data.message || 'تم تغيير كلمة المرور بنجاح.', false);
                form.reset();
            } else {
                showAlert(res.data.error || 'حدث خطأ.', true);
            }
        })
        .catch(function() {
            showAlert('حدث خطأ في الاتصال.', true);
        })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check me-2"></i> حفظ كلمة المرور';
        });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
