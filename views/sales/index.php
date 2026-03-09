<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">المبيعات</span>
</nav>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">المبيعات وأوامر الصرف</h1>
        <p class="text-sm text-slate-500 mt-1">تتبع الفواتير وحركة خروج المخزون</p>
    </div>
    <a href="/sales/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-medium shadow-md btn-primary focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 cursor-pointer">
        <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> فاتورة جديدة
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">رقم الفاتورة</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">العميل</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الدفع</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">الإجمالي</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($sales)): ?>
                <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">لا توجد مبيعات بعد. <a href="/sales/create" class="text-emerald-600 hover:underline">أضف فاتورة جديدة</a>.</td></tr>
                <?php else: ?>
                <?php foreach ($sales as $sale): ?>
                <tr class="table-row-hover transition-colors duration-200">
                    <td class="px-6 py-4 text-sm font-medium text-slate-800">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-emerald-500"></i>
                            <?= htmlspecialchars($sale['invoice_number'] ?? '#' . $sale['id'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($sale['customer_name'] ?? 'عميل نقدي', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4">
                        <?php if (($sale['payment_method'] ?? 'cash') === 'card'): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                            <i class="fa-solid fa-credit-card"></i> بطاقة
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <i class="fa-solid fa-money-bill"></i> نقدي
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-bold text-slate-800"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$sale['total'], 0) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars(date('Y/m/j H:i', strtotime($sale['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
