<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>
<?php
$pr       = $profitRow ?? ['total_revenue' => 0, 'total_cost' => 0, 'gross_profit' => 0];
$revenue  = (float)($pr['total_revenue'] ?? 0);
$cost     = (float)($pr['total_cost'] ?? 0);
$profit   = (float)($pr['gross_profit'] ?? 0);
$margin   = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">التقارير</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">التقارير</h1>
        <p class="page-subtitle">المبيعات والأرباح (آخر 30 يوماً)</p>
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

<!-- بطاقات ملخص الأرباح -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
    <div class="app-card p-5">
        <p class="text-xs font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">إجمالي الإيرادات</p>
        <p class="text-2xl font-bold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($revenue, 0) ?></p>
        <p class="text-xs mt-2 font-medium" style="color: rgb(var(--muted-foreground));">مجموع المبيعات المكتملة</p>
    </div>
    <div class="app-card p-5">
        <p class="text-xs font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">إجمالي التكاليف</p>
        <p class="text-2xl font-bold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($cost, 0) ?></p>
        <p class="text-xs mt-2 font-medium" style="color: rgb(var(--muted-foreground));">تكلفة البضاعة المباعة</p>
    </div>
    <div class="app-card p-5 relative overflow-hidden" style="border-color: rgb(<?= $profit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?> / 0.3);">
        <div class="absolute top-0 right-0 w-1 h-full rounded-l" style="background: rgb(var(--<?= $profit >= 0 ? 'color-success' : 'color-danger' ?>));"></div>
        <p class="text-xs font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--<?= $profit >= 0 ? 'color-success' : 'color-danger' ?>));">الربح الإجمالي</p>
        <p class="text-2xl font-bold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($profit, 0) ?></p>
        <p class="text-xs mt-2 font-bold" style="color: rgb(var(--<?= $profit >= 0 ? 'color-success' : 'color-danger' ?>));">هامش <?= $margin ?>%</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <!-- جدول مبيعات يومي -->
    <div class="app-card-flat p-6">
        <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">المبيعات (آخر 30 يوماً)</h4>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <?php if (empty($salesByDay)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات مبيعات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid rgb(var(--border));">
                        <th class="text-right py-2 font-semibold" style="color: rgb(var(--muted-foreground));">التاريخ</th>
                        <th class="text-center py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الفواتير</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الإجمالي (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($salesByDay as $row): ?>
                <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                    <td class="py-2.5" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['day'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2.5 text-center" style="color: rgb(var(--muted-foreground));"><?= (int)$row['count'] ?></td>
                    <td class="py-2.5 text-left font-semibold" style="color: rgb(var(--foreground));"><?= number_format((float)$row['total'], 0) ?></td>
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
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <?php if (empty($topProducts)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات بعد.</p>
            <?php else: ?>
            <table class="min-w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid rgb(var(--border));">
                        <th class="text-right py-2 font-semibold" style="color: rgb(var(--muted-foreground));">المنتج</th>
                        <th class="text-center py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الكمية</th>
                        <th class="text-left py-2 font-semibold" style="color: rgb(var(--muted-foreground));">الإيراد</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topProducts as $row): ?>
                <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                    <td class="py-2.5 font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="py-2.5 text-center" style="color: rgb(var(--muted-foreground));"><?= (int)$row['qty_sold'] ?></td>
                    <td class="py-2.5 text-left font-semibold" style="color: rgb(var(--foreground));"><?= number_format((float)$row['revenue'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- أرباح المنتجات -->
<div class="app-card-flat p-6">
    <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">
        <i class="fa-solid fa-chart-line me-2" style="color: rgb(var(--color-success));" aria-hidden="true"></i>
        أرباح المنتجات (آخر 30 يوماً)
    </h4>
    <?php if (empty($profitByProduct)): ?>
    <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات أرباح بعد.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr style="border-bottom: 1px solid rgb(var(--border));">
                    <th class="text-right py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">المنتج</th>
                    <th class="text-center py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">الكمية</th>
                    <th class="text-left py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">الإيراد (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    <th class="text-left py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">التكلفة (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    <th class="text-left py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">الربح (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    <th class="text-center py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">الهامش</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($profitByProduct as $row):
                $rowProfit  = (float)($row['profit'] ?? 0);
                $rowRevenue = (float)($row['revenue'] ?? 0);
                $rowMargin  = $rowRevenue > 0 ? round(($rowProfit / $rowRevenue) * 100, 1) : 0;
                $marginColor = $rowMargin >= 20 ? 'var(--color-success)' : ($rowMargin >= 0 ? 'var(--color-warning)' : 'var(--color-danger)');
            ?>
            <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                <td class="py-3 px-3 font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="py-3 px-3 text-center" style="color: rgb(var(--muted-foreground));"><?= (int)$row['qty_sold'] ?></td>
                <td class="py-3 px-3 text-left" style="color: rgb(var(--foreground));"><?= number_format($rowRevenue, 0) ?></td>
                <td class="py-3 px-3 text-left" style="color: rgb(var(--muted-foreground));"><?= number_format((float)($row['cost'] ?? 0), 0) ?></td>
                <td class="py-3 px-3 text-left font-semibold" style="color: rgb(<?= $rowProfit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>);"><?= number_format($rowProfit, 0) ?></td>
                <td class="py-3 px-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold" style="background: rgb(<?= $marginColor ?> / 0.1); color: rgb(<?= $marginColor ?>);"><?= $rowMargin ?>%</span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
