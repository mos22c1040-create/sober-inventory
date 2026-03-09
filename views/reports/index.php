<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">التقارير</span>
</nav>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">التقارير</h1>
        <p class="text-sm text-slate-500 mt-1">المبيعات وأكثر المنتجات مبيعاً (آخر 30 يوماً)</p>
    </div>
    <div class="flex gap-2">
        <a href="/reports/export/sales" class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-medium focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 cursor-pointer">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> تصدير المبيعات CSV
        </a>
        <a href="/reports/export/products" class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 bg-slate-600 text-white rounded-xl hover:bg-slate-700 text-sm font-medium focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 cursor-pointer">
            <i class="fa-solid fa-file-export" aria-hidden="true"></i> تصدير المنتجات CSV
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h4 class="font-bold text-slate-800 mb-4">المبيعات (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <?php if (empty($salesByDay)): ?>
            <p class="text-gray-500">لا توجد بيانات مبيعات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200">
                    <tr><th class="text-right py-2 text-gray-500">التاريخ</th><th class="text-left py-2 text-gray-500">الفواتير</th><th class="text-left py-2 text-gray-500">الإجمالي (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th></tr>
                </thead>
                <tbody>
                <?php foreach ($salesByDay as $row): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-2 text-slate-800"><?= htmlspecialchars($row['day'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2 text-right text-slate-800"><?= (int)$row['count'] ?></td>
                    <td class="py-2 text-right font-medium text-slate-800"><?= number_format((float)$row['total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h4 class="font-bold text-slate-800 mb-4">أكثر المنتجات مبيعاً (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <?php if (empty($topProducts)): ?>
            <p class="text-gray-500">لا توجد بيانات مبيعات منتجات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200">
                    <tr><th class="text-right py-2 text-gray-500">المنتج</th><th class="text-left py-2 text-gray-500">الكمية المباعة</th><th class="text-left py-2 text-gray-500">الإيراد (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th></tr>
                </thead>
                <tbody>
                <?php foreach ($topProducts as $row): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-2 text-slate-800"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2 text-right text-slate-800"><?= (int)$row['qty_sold'] ?></td>
                    <td class="py-2 text-right font-medium text-slate-800"><?= number_format((float)$row['revenue'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
