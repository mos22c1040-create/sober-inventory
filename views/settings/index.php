<?php
$s = $settings ?? [];
$appName = $s['app_name'] ?? 'نظام المخزون';
$currencySymbol = $s['currency_symbol'] ?? 'د.ع';
require BASE_PATH . '/views/layouts/header.php';
require BASE_PATH . '/views/layouts/sidebar.php';
?>
<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">إعدادات النظام</span>
</nav>

<div class="max-w-xl">
    <header class="page-header">
        <h1 class="page-title">إعدادات النظام</h1>
        <p class="page-subtitle">اسم المتجر ورمز العملة</p>
    </header>

    <div id="alert-box" class="hidden rounded-lg p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite"></div>

    <div class="app-card-flat p-6">
        <form id="settings-form" novalidate>
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-5">
                <label for="app_name" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">اسم المتجر / التطبيق</label>
                <input type="text" id="app_name" name="app_name" value="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
                       class="app-input w-full rounded-lg px-4 py-2.5 text-sm border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>
            <div class="mb-6">
                <label for="currency_symbol" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">رمز العملة</label>
                <input type="text" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>"
                       class="app-input w-full rounded-lg px-4 py-2.5 text-sm border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));" placeholder="د.ع أو $">
            </div>
            <button type="submit" id="submit-btn" class="min-h-[44px] px-6 py-2.5 rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
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
            var ok = !!data.success;
            alertBox.className = 'rounded-lg p-4 mb-6 border text-sm font-medium ' + (ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700');
            alertBox.textContent = ok ? (data.message || 'تم الحفظ بنجاح') : (data.error || 'حدث خطأ');
            alertBox.classList.remove('hidden');
            submitBtn.disabled = false;
        }).catch(function() {
            alertBox.className = 'rounded-lg p-4 mb-6 border text-sm font-medium bg-red-50 border-red-200 text-red-700';
            alertBox.textContent = 'تعذّر الاتصال بالخادم.';
            alertBox.classList.remove('hidden');
            submitBtn.disabled = false;
        });
    });
})();
</script>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
