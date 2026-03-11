<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">التقارير</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">التقارير</h1>
        <p class="page-subtitle">المبيعات وأكثر المنتجات مبيعاً (آخر 30 يوماً)</p>
    </header>
    <div class="flex flex-wrap gap-2">
        <a href="/reports/export/sales" class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200" style="background: rgb(var(--color-success)); color: white;">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> تصدير المبيعات CSV
        </a>
        <a href="/reports/export/products" class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200 border" style="color: rgb(var(--foreground)); border-color: rgb(var(--border)); background: rgb(var(--muted));">
            <i class="fa-solid fa-file-export" aria-hidden="true"></i> تصدير المنتجات CSV
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <!-- جدول مبيعات اليوم -->
    <div class="app-card-flat p-6">
        <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">المبيعات (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <?php if (empty($salesByDay)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات مبيعات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid rgb(var(--border));">
                        <th class="text-right py-2 font-semibold" style="color: rgb(var(--muted-foreground));">التاريخ</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الفواتير</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الإجمالي (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($salesByDay as $row): ?>
                <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                    <td class="py-2.5" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['day'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2.5 text-right" style="color: rgb(var(--foreground));"><?= (int)$row['count'] ?></td>
                    <td class="py-2.5 text-right font-medium" style="color: rgb(var(--foreground));"><?= number_format((float)$row['total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- أكثر المنتجات مبيعاً -->
    <div class="app-card-flat p-6">
        <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">أكثر المنتجات مبيعاً (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <?php if (empty($topProducts)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات مبيعات منتجات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid rgb(var(--border));">
                        <th class="text-right py-2 font-semibold" style="color: rgb(var(--muted-foreground));">المنتج</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الكمية المباعة</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الإيراد (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topProducts as $row): ?>
                <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                    <td class="py-2.5 font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2.5 text-right" style="color: rgb(var(--foreground));"><?= (int)$row['qty_sold'] ?></td>
                    <td class="py-2.5 text-right font-medium" style="color: rgb(var(--foreground));"><?= number_format((float)$row['revenue'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
