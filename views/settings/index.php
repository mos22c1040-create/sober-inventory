<?php
$s = $settings ?? [];
$appName = $s['app_name'] ?? 'نظام المخزون';
$currencySymbol = $s['currency_symbol'] ?? 'د.ع';
require BASE_PATH . '/views/layouts/header.php';
require BASE_PATH . '/views/layouts/sidebar.php';
?>
<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">إعدادات النظام</span>
</nav>
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">إعدادات النظام</h1>
    <p class="text-sm text-slate-500 mb-6">اسم المتجر ورمز العملة</p>

    <div id="alert-box" class="hidden rounded-xl p-4 mb-6 border text-sm font-medium" role="alert"></div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form id="settings-form">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-5">
                <label for="app_name" class="block text-sm font-semibold text-gray-700 mb-1">اسم المتجر / التطبيق</label>
                <input type="text" id="app_name" name="app_name" value="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="currency_symbol" class="block text-sm font-semibold text-gray-700 mb-1">رمز العملة</label>
                <input type="text" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                       placeholder="د.ع أو $">
            </div>
            <button type="submit" id="submit-btn" class="min-h-[44px] px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold btn-primary focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-colors cursor-pointer">
                حفظ الإعدادات
            </button>
        </form>
    </div>
</div>
<script>
(function() {
    var form = document.getElementById('settings-form');
    var alertBox = document.getElementById('alert-box');
    var submitBtn = document.getElementById('submit-btn');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        fetch('/api/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: document.getElementById('csrf_token').value,
                app_name: document.getElementById('app_name').value.trim(),
                currency_symbol: document.getElementById('currency_symbol').value.trim()
            })
        }).then(function(r) { return r.json(); }).then(function(data) {
            alertBox.classList.remove('hidden');
            alertBox.className = 'rounded-xl p-4 mb-6 border text-sm font-medium ' + (data.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700');
            alertBox.textContent = data.success ? data.message : (data.error || 'حدث خطأ');
            submitBtn.disabled = false;
        }).catch(function() {
            alertBox.classList.remove('hidden');
            alertBox.className = 'rounded-xl p-4 mb-6 border text-sm font-medium bg-red-50 border-red-200 text-red-700';
            alertBox.textContent = 'تعذّر الاتصال بالخادم.';
            submitBtn.disabled = false;
        });
    });
})();
</script>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
