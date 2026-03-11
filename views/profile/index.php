<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$username = $user['username'] ?? '';
$email    = $user['email'] ?? '';
?>

<div class="max-w-lg">
    <nav class="flex items-center gap-2 text-sm mb-6" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
        <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
        <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
        <span class="font-medium" style="color: rgb(var(--foreground));">حسابي</span>
    </nav>

    <div id="alert-box" class="hidden rounded-lg p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite">
        <span id="alert-icon" class="me-2"></span>
        <span id="alert-message"></span>
    </div>

    <!-- بيانات الحساب -->
    <div class="app-card-flat p-6 md:p-8 mb-6">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: rgb(var(--foreground));">
            <i class="fa-solid fa-user" style="color: rgb(var(--primary));" aria-hidden="true"></i>
            بيانات الحساب
        </h2>
        <p class="text-sm mb-1" style="color: rgb(var(--foreground));"><strong>اسم المستخدم:</strong> <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-sm" style="color: rgb(var(--foreground));"><strong>البريد:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <!-- تغيير كلمة المرور -->
    <div class="app-card-flat p-6 md:p-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: rgb(var(--foreground));">
            <i class="fa-solid fa-key" style="color: rgb(var(--color-warning));" aria-hidden="true"></i>
            تغيير كلمة المرور
        </h2>

        <form id="password-form" novalidate>
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-5">
                <label for="current_password" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">كلمة المرور الحالية <span style="color: rgb(var(--color-danger));">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                       placeholder="أدخل كلمة المرور الحالية">
                <p id="err-current" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="mb-5">
                <label for="new_password" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">كلمة المرور الجديدة <span style="color: rgb(var(--color-danger));">*</span></label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="6"
                       class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                       placeholder="6 أحرف على الأقل">
                <p id="err-new" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">تأكيد كلمة المرور <span style="color: rgb(var(--color-danger));">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                       class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                       placeholder="أعد إدخال كلمة المرور الجديدة">
                <p id="err-confirm" class="hidden mt-1 text-xs text-red-600"></p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" id="submit-btn" class="flex-1 min-h-[44px] rounded-lg font-semibold py-2.5 btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                    <i class="fa-solid fa-check me-2" aria-hidden="true"></i> حفظ كلمة المرور
                </button>
                <a href="/dashboard" class="flex-1 min-h-[44px] flex items-center justify-center rounded-lg border font-semibold py-2.5 focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">
                    <i class="fa-solid fa-arrow-right me-2" aria-hidden="true"></i> العودة للوحة التحكم
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
        alertBox.className = 'rounded-lg p-4 mb-6 border text-sm font-medium ' + (isError ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800');
        alertIcon.className = 'me-2 ' + (isError ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check');
        alertMsg.textContent = message;
    }
    function hideAlert() { alertBox.classList.add('hidden'); }

    ['current_password', 'new_password', 'confirm_password'].forEach(function(id) {
        var key = id === 'current_password' ? 'current' : id === 'new_password' ? 'new' : 'confirm';
        var el = document.getElementById('err-' + key);
        if (el) document.getElementById(id).addEventListener('input', function() { el.classList.add('hidden'); hideAlert(); });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert();
        var current = document.getElementById('current_password').value;
        var newPwd  = document.getElementById('new_password').value;
        var confirmPwd = document.getElementById('confirm_password').value;
        var errCurrent = document.getElementById('err-current');
        var errNew = document.getElementById('err-new');
        var errConfirm = document.getElementById('err-confirm');
        errCurrent.classList.add('hidden'); errNew.classList.add('hidden'); errConfirm.classList.add('hidden');
        if (!current.trim()) { errCurrent.textContent = 'أدخل كلمة المرور الحالية'; errCurrent.classList.remove('hidden'); return; }
        if (newPwd.length < 6) { errNew.textContent = 'كلمة المرور الجديدة 6 أحرف على الأقل'; errNew.classList.remove('hidden'); return; }
        if (newPwd !== confirmPwd) { errConfirm.textContent = 'كلمة المرور وتأكيدها غير متطابقين'; errConfirm.classList.remove('hidden'); return; }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> جارٍ الحفظ...';
        fetch('/api/profile/password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ current_password: current, new_password: newPwd, confirm_password: confirmPwd, csrf_token: document.getElementById('csrf_token').value })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { showAlert(data.message || 'تم تغيير كلمة المرور بنجاح.', false); form.reset(); }
            else showAlert(data.error || 'حدث خطأ.', true);
        }).catch(function() { showAlert('حدث خطأ في الاتصال.', true); }).finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check me-2"></i> حفظ كلمة المرور';
        });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
