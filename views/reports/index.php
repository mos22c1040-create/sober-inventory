<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$bp             = $basePathSafe ?? '';
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$pr       = $profitRow ?? ['total_revenue' => 0, 'total_cost' => 0, 'gross_profit' => 0];
$revenue  = (float) ($pr['total_revenue'] ?? 0);
$cost     = (float) ($pr['total_cost']    ?? 0);
$profit   = (float) ($pr['gross_profit']  ?? 0);
$margin   = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
$from     = htmlspecialchars($dateFrom ?? date('Y-m-d', strtotime('-30 days')), ENT_QUOTES, 'UTF-8');
$to       = htmlspecialchars($dateTo   ?? date('Y-m-d'),                        ENT_QUOTES, 'UTF-8');
$exportQs = '?from=' . $from . '&to=' . $to;
?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">التقارير</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-5">
    <header class="page-header mb-0">
        <h1 class="page-title">التقارير</h1>
        <p class="page-subtitle">من <?= $from ?> إلى <?= $to ?></p>
    </header>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $bp ?>/reports/export/sales<?= $exportQs ?>"
           class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200"
           style="background: rgb(var(--color-success)); color: white;">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> تصدير المبيعات CSV
        </a>
        <a href="<?= $bp ?>/reports/export/products<?= $exportQs ?>"
           class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200 border"
           style="color: rgb(var(--foreground)); border-color: rgb(var(--border)); background: rgb(var(--muted));">
            <i class="fa-solid fa-file-export" aria-hidden="true"></i> تصدير المنتجات CSV
        </a>
    </div>
</div>

<!-- ── شريط فلترة التاريخ ───────────────────────────────────────────────── -->
<form method="GET" action="<?= $bp ?>/reports" id="date-filter-form"
      class="app-card-flat p-4 mb-6 flex flex-wrap items-end gap-3">
    <!-- أزرار الاختصار -->
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-semibold me-1" style="color: rgb(var(--muted-foreground));">اختصار:</span>
        <?php
        $presets = [
            '7'  => 'آخر 7 أيام',
            '30' => 'آخر 30 يوماً',
            '90' => 'آخر 3 أشهر',
        ];
        foreach ($presets as $days => $label):
            $pFrom = date('Y-m-d', strtotime("-{$days} days"));
            $pTo   = date('Y-m-d');
            $active = ($from === $pFrom && $to === $pTo);
        ?>
        <a href="?from=<?= $pFrom ?>&to=<?= $pTo ?>"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors <?= $active ? 'text-white' : '' ?>"
           style="<?= $active
               ? 'background: rgb(var(--primary)); color: rgb(var(--primary-foreground)); border-color: rgb(var(--primary));'
               : 'border-color: rgb(var(--border)); color: rgb(var(--muted-foreground)); background: rgb(var(--muted));' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
        <?php
        // هذا الشهر
        $mFrom  = date('Y-m-01');
        $mTo    = date('Y-m-d');
        $mActive = ($from === $mFrom && $to === $mTo);
        ?>
        <a href="?from=<?= $mFrom ?>&to=<?= $mTo ?>"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
           style="<?= $mActive
               ? 'background: rgb(var(--primary)); color: rgb(var(--primary-foreground)); border-color: rgb(var(--primary));'
               : 'border-color: rgb(var(--border)); color: rgb(var(--muted-foreground)); background: rgb(var(--muted));' ?>">
            هذا الشهر
        </a>
    </div>

    <!-- تاريخ مخصص -->
    <div class="flex flex-wrap items-end gap-2 ms-auto">
        <div>
            <label class="block text-xs font-semibold mb-1" style="color: rgb(var(--muted-foreground));">من</label>
            <input type="date" name="from" value="<?= $from ?>" id="inp-from"
                   class="app-input rounded-lg px-3 py-2 text-sm border"
                   style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1" style="color: rgb(var(--muted-foreground));">إلى</label>
            <input type="date" name="to" value="<?= $to ?>" id="inp-to"
                   class="app-input rounded-lg px-3 py-2 text-sm border"
                   style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
        </div>
        <button type="submit"
                class="min-h-[38px] px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
                style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
            <i class="fa-solid fa-magnifying-glass me-1"></i> عرض
        </button>
    </div>
</form>

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
        <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">المبيعات اليومية</h4>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <?php if (empty($salesByDay)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد مبيعات في هذه الفترة.</p>
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
                    <td class="py-2.5 text-center" style="color: rgb(var(--muted-foreground));"><?= (int) $row['count'] ?></td>
                    <td class="py-2.5 text-left font-semibold" style="color: rgb(var(--foreground));"><?= number_format((float) $row['total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- أكثر المنتجات مبيعاً -->
    <div class="app-card-flat p-6">
        <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">أكثر المنتجات مبيعاً</h4>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <?php if (empty($topProducts)): ?>
            <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات في هذه الفترة.</p>
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
                    <td class="py-2.5 text-center" style="color: rgb(var(--muted-foreground));"><?= (int) $row['qty_sold'] ?></td>
                    <td class="py-2.5 text-left font-semibold" style="color: rgb(var(--foreground));"><?= number_format((float) $row['revenue'], 0) ?></td>
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
        أرباح المنتجات
    </h4>
    <?php if (empty($profitByProduct)): ?>
    <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات أرباح في هذه الفترة.</p>
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
                $rowProfit  = (float) ($row['profit']  ?? 0);
                $rowRevenue = (float) ($row['revenue'] ?? 0);
                $rowMargin  = $rowRevenue > 0 ? round(($rowProfit / $rowRevenue) * 100, 1) : 0;
                $marginColor = $rowMargin >= 20 ? 'var(--color-success)' : ($rowMargin >= 0 ? 'var(--color-warning)' : 'var(--color-danger)');
            ?>
            <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                <td class="py-3 px-3 font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="py-3 px-3 text-center" style="color: rgb(var(--muted-foreground));"><?= (int) $row['qty_sold'] ?></td>
                <td class="py-3 px-3 text-left" style="color: rgb(var(--foreground));"><?= number_format($rowRevenue, 0) ?></td>
                <td class="py-3 px-3 text-left" style="color: rgb(var(--muted-foreground));"><?= number_format((float) ($row['cost'] ?? 0), 0) ?></td>
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

<?php
$lowStockList = $lowStockProducts ?? [];
?>
<?php if (!empty($lowStockList)): ?>
<!-- تنبيهات المخزون — منتجات تحتاج إعادة تخزين -->
<div class="app-card-flat p-6 mt-6" style="border-color: rgb(254 202 202);">
    <h4 class="text-base font-bold mb-4" style="color: rgb(var(--foreground));">
        <i class="fa-solid fa-triangle-exclamation me-2" style="color: rgb(var(--color-danger));" aria-hidden="true"></i>
        تنبيهات المخزون
    </h4>
    <p class="text-sm mb-4" style="color: rgb(var(--muted-foreground));">منتجات وصلت لحد التنبيه أو أقل — تحتاج إعادة تخزين.</p>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr style="border-bottom: 1px solid rgb(var(--border));">
                    <th class="text-right py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">المنتج</th>
                    <th class="text-center py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">الكمية الحالية</th>
                    <th class="text-center py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">حد التنبيه</th>
                    <th class="text-left py-2.5 px-3 font-semibold" style="color: rgb(var(--muted-foreground));">السعر</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lowStockList as $p): ?>
            <tr class="app-table-row" style="border-bottom: 1px solid rgb(var(--border));">
                <td class="py-3 px-3 font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="py-3 px-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold" style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));"><?= (int)($p['quantity'] ?? 0) ?></span>
                </td>
                <td class="py-3 px-3 text-center" style="color: rgb(var(--muted-foreground));"><?= (int)($p['low_stock_threshold'] ?? 0) ?></td>
                <td class="py-3 px-3 text-left font-semibold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($p['price'] ?? 0), 0) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <a href="<?= $bp ?>/products" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors cursor-pointer"
           style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
            <i class="fa-solid fa-boxes-stacked text-xs" aria-hidden="true"></i>
            الذهاب إلى المنتجات
        </a>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
