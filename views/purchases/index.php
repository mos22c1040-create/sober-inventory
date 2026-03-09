<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">المشتريات</span>
</nav>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">المشتريات</h1>
        <p class="text-sm text-slate-500 mt-1">إدخال مخزون وإعادة التعبئة</p>
    </div>
<a href="/purchases/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium shadow-md btn-primary focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 cursor-pointer">
            <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> مشتريات جديدة
        </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الرقم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">المورد</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">الإجمالي</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($purchases)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">لا توجد مشتريات بعد. <a href="/purchases/create" class="text-blue-600 hover:underline">أضف مشتريات</a>.</td></tr>
                <?php else: ?>
                <?php foreach ($purchases as $pu): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-slate-800">#<?= (int)$pu['id'] ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($pu['supplier'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-800"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$pu['total'], 0) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars(date('Y/m/j H:i', strtotime($pu['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
