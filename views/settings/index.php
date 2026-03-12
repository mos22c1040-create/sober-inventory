<?php
$s               = $settings ?? [];
$appName         = $s['app_name']         ?? 'نظام المخزون';
$currencySymbol  = $s['currency_symbol']  ?? 'د.ع';
$timezone        = $s['timezone']         ?? 'Asia/Baghdad';
$sessionLifetime = (int) ($s['session_lifetime'] ?? 3600);
require BASE_PATH . '/views/layouts/header.php';
require BASE_PATH . '/views/layouts/sidebar.php';
$bp = $basePathSafe ?? '';

// Common timezones (Middle East first, then by region)
$timezoneGroups = [
    'الشرق الأوسط وأفريقيا' => [
        'Asia/Baghdad'   => 'بغداد (UTC+3)',
        'Asia/Riyadh'    => 'الرياض (UTC+3)',
        'Asia/Kuwait'    => 'الكويت (UTC+3)',
        'Asia/Qatar'     => 'قطر (UTC+3)',
        'Asia/Bahrain'   => 'البحرين (UTC+3)',
        'Asia/Aden'      => 'عدن (UTC+3)',
        'Asia/Dubai'     => 'دبي (UTC+4)',
        'Asia/Muscat'    => 'مسقط (UTC+4)',
        'Asia/Tehran'    => 'طهران (UTC+3:30)',
        'Asia/Beirut'    => 'بيروت (UTC+2/3)',
        'Asia/Damascus'  => 'دمشق (UTC+2/3)',
        'Asia/Amman'     => 'عمّان (UTC+2/3)',
        'Asia/Jerusalem' => 'القدس (UTC+2/3)',
        'Africa/Cairo'   => 'القاهرة (UTC+2/3)',
        'Africa/Tripoli' => 'طرابلس (UTC+2)',
        'Africa/Tunis'   => 'تونس (UTC+1)',
        'Africa/Algiers' => 'الجزائر (UTC+1)',
        'Africa/Casablanca' => 'الدار البيضاء (UTC+0/1)',
        'Africa/Khartoum'   => 'الخرطوم (UTC+3)',
    ],
    'أوروبا' => [
        'UTC'             => 'UTC (UTC+0)',
        'Europe/London'   => 'لندن (UTC+0/1)',
        'Europe/Paris'    => 'باريس (UTC+1/2)',
        'Europe/Berlin'   => 'برلين (UTC+1/2)',
        'Europe/Istanbul' => 'إسطنبول (UTC+3)',
    ],
    'آسيا' => [
        'Asia/Kolkata'    => 'الهند (UTC+5:30)',
        'Asia/Karachi'    => 'كراتشي (UTC+5)',
        'Asia/Singapore'  => 'سنغافورة (UTC+8)',
        'Asia/Tokyo'      => 'طوكيو (UTC+9)',
    ],
    'أمريكا' => [
        'America/New_York'    => 'نيويورك (UTC-5/-4)',
        'America/Chicago'     => 'شيكاغو (UTC-6/-5)',
        'America/Los_Angeles' => 'لوس أنجلوس (UTC-8/-7)',
        'America/Sao_Paulo'   => 'ساو باولو (UTC-3)',
    ],
];
?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">إعدادات النظام</span>
</nav>

<div class="max-w-xl">
    <header class="page-header">
        <h1 class="page-title">إعدادات النظام</h1>
        <p class="page-subtitle">اسم المتجر، العملة، المنطقة الزمنية، وإعدادات الجلسة</p>
    </header>

    <div id="alert-box" class="hidden rounded-lg p-4 mb-6 border text-sm font-medium" role="alert" aria-live="polite"></div>

    <div class="app-card-flat p-6 space-y-5">
        <form id="settings-form" novalidate>
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <!-- اسم المتجر -->
            <div>
                <label for="app_name" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">
                    <i class="fa-solid fa-store me-1.5 text-xs opacity-60"></i>اسم المتجر / التطبيق
                </label>
                <input type="text" id="app_name" name="app_name"
                       value="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
                       class="app-input w-full rounded-lg px-4 py-2.5 text-sm border"
                       style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>

            <!-- رمز العملة -->
            <div>
                <label for="currency_symbol" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">
                    <i class="fa-solid fa-coins me-1.5 text-xs opacity-60"></i>رمز العملة
                </label>
                <input type="text" id="currency_symbol" name="currency_symbol"
                       value="<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>"
                       class="app-input w-full rounded-lg px-4 py-2.5 text-sm border"
                       style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                       placeholder="د.ع أو $">
            </div>

            <!-- المنطقة الزمنية -->
            <div>
                <label for="timezone" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">
                    <i class="fa-solid fa-clock me-1.5 text-xs opacity-60"></i>المنطقة الزمنية
                </label>
                <select id="timezone" name="timezone"
                        class="app-input w-full rounded-lg px-4 py-2.5 text-sm border"
                        style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                    <?php foreach ($timezoneGroups as $groupLabel => $zones): ?>
                    <optgroup label="<?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($zones as $tz => $label): ?>
                        <option value="<?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $timezone === $tz ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs mt-1" style="color: rgb(var(--muted-foreground));">تؤثر على التواريخ والأوقات المعروضة في جميع أنحاء النظام</p>
            </div>

            <!-- مدة الجلسة -->
            <div>
                <label for="session_lifetime" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">
                    <i class="fa-solid fa-hourglass-half me-1.5 text-xs opacity-60"></i>مدة انتهاء الجلسة (بالثواني)
                </label>
                <div class="flex items-center gap-3">
                    <input type="number" id="session_lifetime" name="session_lifetime"
                           value="<?= $sessionLifetime ?>" min="300" max="86400" step="300"
                           class="app-input w-40 rounded-lg px-4 py-2.5 text-sm border"
                           style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ([900 => '15 دقيقة', 1800 => '30 دقيقة', 3600 => 'ساعة', 7200 => 'ساعتان', 28800 => '8 ساعات'] as $secs => $lbl): ?>
                        <button type="button" class="preset-session px-2.5 py-1 rounded-lg text-xs border transition-colors"
                                data-value="<?= $secs ?>"
                                style="border-color: rgb(var(--border)); color: rgb(var(--muted-foreground)); background: rgb(var(--muted));">
                            <?= $lbl ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-xs mt-1" style="color: rgb(var(--muted-foreground));">الحد الأدنى 5 دقائق — الحد الأقصى 24 ساعة (86400)</p>
            </div>

            <div class="pt-2">
                <button type="submit" id="submit-btn"
                        class="min-h-[44px] px-6 py-2.5 rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer"
                        style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                    <i class="fa-solid fa-floppy-disk me-2"></i>حفظ الإعدادات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var BASE      = (document.querySelector('meta[name="app-base"]') || {}).content || '';
    var form      = document.getElementById('settings-form');
    var alertBox  = document.getElementById('alert-box');
    var submitBtn = document.getElementById('submit-btn');

    // أزرار الاختصار لمدة الجلسة
    document.querySelectorAll('.preset-session').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('session_lifetime').value = btn.dataset.value;
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>جاري الحفظ...';

        fetch(BASE + '/api/settings', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                csrf_token      : document.getElementById('csrf_token').value,
                app_name        : document.getElementById('app_name').value.trim(),
                currency_symbol : document.getElementById('currency_symbol').value.trim(),
                timezone        : document.getElementById('timezone').value,
                session_lifetime: parseInt(document.getElementById('session_lifetime').value, 10) || 3600,
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var ok = !!data.success;
            alertBox.className = 'rounded-lg p-4 mb-6 border text-sm font-medium ' +
                (ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700');
            alertBox.textContent = ok ? (data.message || 'تم الحفظ بنجاح') : (data.error || 'حدث خطأ');
            alertBox.classList.remove('hidden');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        })
        .catch(function () {
            alertBox.className = 'rounded-lg p-4 mb-6 border text-sm font-medium bg-red-50 border-red-200 text-red-700';
            alertBox.textContent = 'تعذّر الاتصال بالخادم.';
            alertBox.classList.remove('hidden');
        })
        .finally(function () {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>حفظ الإعدادات';
        });
    });
})();
</script>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
