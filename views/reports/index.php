<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<h3 class="text-lg font-bold text-slate-800 mb-6">تقارير المبيعات والمنتجات</h3>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h4 class="font-bold text-slate-800 mb-4">المبيعات (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <?php if (empty($salesByDay)): ?>
            <p class="text-gray-500">لا توجد بيانات مبيعات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200">
                    <tr><th class="text-right py-2 text-gray-500">التاريخ</th><th class="text-left py-2 text-gray-500">الفواتير</th><th class="text-left py-2 text-gray-500">الإجمالي (د.ع)</th></tr>
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
                    <tr><th class="text-right py-2 text-gray-500">المنتج</th><th class="text-left py-2 text-gray-500">الكمية المباعة</th><th class="text-left py-2 text-gray-500">الإيراد (د.ع)</th></tr>
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
