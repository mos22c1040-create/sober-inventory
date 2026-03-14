<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$isAdmin        = ($_SESSION['role'] ?? '') === 'admin';
$bp             = $basePathSafe ?? '';
?>

<div class="dashboard-page min-h-full w-full">

<!-- ─── Hero Header ──────────────────────────────────────────────────── -->
<header class="dashboard-hero flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2.5 mb-1">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--primary-subtle)); color: rgb(var(--primary));">
                <i class="fa-solid fa-gauge-high text-sm" aria-hidden="true"></i>
            </div>
            <h1 class="page-title">لوحة التحكم</h1>
        </div>
        <p class="page-subtitle">نظرة عامة على المخزون والمبيعات اليومية</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="<?= $bp ?>/pos"
           class="btn-pos inline-flex items-center gap-2 text-sm text-white focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:outline-none"
           aria-label="فتح نقطة البيع">
            <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
            <span>نقطة البيع</span>
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?= $bp ?>/reports"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all btn-secondary focus:outline-none">
            <i class="fa-solid fa-chart-pie text-xs" aria-hidden="true"></i>
            <span>التقارير</span>
        </a>
        <?php endif; ?>
    </div>
</header>

<!-- ─── Stats Grid ────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6 stagger-children">

    <!-- مبيعات اليوم -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--primary-subtle)); color: rgb(var(--primary));">
                <i class="fa-solid fa-arrow-trend-up text-base" aria-hidden="true"></i>
            </div>
            <span class="badge badge-info">اليوم</span>
        </div>
        <p class="text-[10.5px] font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">مبيعات اليوم</p>
        <h3 class="stat-value text-2xl font-extrabold mb-2" style="color: rgb(var(--foreground));">
            <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($todaySales ?? 0), 0) ?>
        </h3>
        <div class="flex items-center gap-1.5 text-xs font-medium" style="color: rgb(var(--muted-foreground));">
            <i class="fa-solid fa-receipt text-[10px]" aria-hidden="true"></i>
            <span><?= (int)($todayCount ?? 0) ?> فاتورة مكتملة</span>
        </div>
        <?php
        $yesterdaySales = (float)($yesterdaySales ?? 0);
        $yesterdayCount  = (int)($yesterdayCount ?? 0);
        if ($yesterdaySales > 0):
            $salesDiff = round((((float)($todaySales ?? 0) - $yesterdaySales) / $yesterdaySales) * 100, 1);
        ?>
        <div class="mt-3 pt-3 flex items-center gap-1.5 text-[11px] font-semibold" style="border-top: 1px solid rgb(var(--border)); color: <?= $salesDiff >= 0 ? 'rgb(var(--color-success))' : 'rgb(var(--color-danger))' ?>;">
            <i class="fa-solid fa-arrow-<?= $salesDiff >= 0 ? 'up' : 'down' ?>-long" aria-hidden="true"></i>
            <?= $salesDiff >= 0 ? '+' : '' ?><?= $salesDiff ?>% مقارنة بالأمس
        </div>
        <?php endif; ?>
    </div>

    <!-- عدد الفواتير -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--color-success-light)); color: rgb(var(--color-success));">
                <i class="fa-solid fa-receipt text-base" aria-hidden="true"></i>
            </div>
            <span class="badge badge-success">مكتملة</span>
        </div>
        <p class="text-[10.5px] font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">عدد الفواتير</p>
        <h3 class="stat-value text-2xl font-extrabold mb-2" style="color: rgb(var(--foreground));">
            <?= (int)($todayCount ?? 0) ?>
        </h3>
        <div class="flex items-center gap-1.5 text-xs font-medium" style="color: rgb(var(--muted-foreground));">
            <i class="fa-regular fa-clock text-[10px]" aria-hidden="true"></i>
            <span>المبيعات المكتملة اليوم</span>
        </div>
    </div>

    <!-- إجمالي المنتجات -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 stat-icon-purple">
                <i class="fa-solid fa-boxes-stacked text-base" aria-hidden="true"></i>
            </div>
            <span class="badge badge-purple">الكتالوج</span>
        </div>
        <p class="text-[10.5px] font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">إجمالي المنتجات</p>
        <h3 class="stat-value text-2xl font-extrabold mb-2" style="color: rgb(var(--foreground));">
            <?= (int)($productCount ?? 0) ?>
        </h3>
        <div class="flex items-center gap-1.5 text-xs font-medium" style="color: rgb(var(--muted-foreground));">
            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block" aria-hidden="true"></span>
            <span>نشطة في المخزون</span>
        </div>
    </div>

    <!-- منخفضة المخزون -->
    <div class="stat-card stat-card-danger">
        <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                <i class="fa-solid fa-triangle-exclamation text-base" aria-hidden="true"></i>
            </div>
            <span class="badge badge-danger">تنبيه</span>
        </div>
        <p class="text-[10.5px] font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--color-danger));">منخفضة المخزون</p>
        <h3 class="stat-value text-2xl font-extrabold mb-2" style="color: rgb(var(--foreground));">
            <?= (int)($lowStockCount ?? 0) ?>
        </h3>
        <a href="<?= $bp ?>/products"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
           style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
            <i class="fa-solid fa-boxes-stacked text-[10px]" aria-hidden="true"></i>
            إعادة تخزين
        </a>
    </div>
</div>

<?php
$lowStockList = $lowStockProducts ?? [];
$hasLowStock  = ($lowStockCount ?? 0) > 0;
?>
<?php if ($hasLowStock && !empty($lowStockList)): ?>
<!-- ─── Low Stock Alert ───────────────────────────────────────────────── -->
<div class="app-card-flat p-5 mb-6 animate-slide-up alert-card-danger">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold" style="color: rgb(var(--foreground));">تنبيهات المخزون</h3>
                <p class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">
                    <?= count($lowStockList) ?> منتج يحتاج إعادة تخزين
                </p>
            </div>
        </div>
        <a href="<?= $bp ?>/products"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all"
           style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
            <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
            عرض الكل
        </a>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php foreach (array_slice($lowStockList, 0, 10) as $p): ?>
        <a href="<?= $bp ?>/products"
           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all low-stock-chip">
            <span class="truncate max-w-[120px]"><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge badge-danger shrink-0 text-[10px]"><?= (int)($p['quantity'] ?? 0) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (count($lowStockList) > 10): ?>
        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg"
              style="color: rgb(var(--muted-foreground)); background: rgb(var(--muted));">
            +<?= count($lowStockList) - 10 ?> منتج آخر
        </span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Chart + Recent Sales ─────────────────────────────────────────── -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    <!-- Sales Chart -->
    <div class="xl:col-span-2 app-card-flat p-6 flex flex-col animate-slide-up" style="animation-delay: 80ms;">
        <div class="flex flex-wrap justify-between items-start gap-3 mb-5">
            <div>
                <h3 class="text-sm font-bold" style="color: rgb(var(--foreground));">نظرة عامة على الإيرادات</h3>
                <p class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">آخر 7 أيام</p>
            </div>
            <?php
            $chartDays = $dailyTotals ?? [];
            $weekTotal = array_sum(array_column($chartDays, 'total'));
            ?>
            <div class="text-end">
                <p class="text-[10.5px] font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجمالي الأسبوع</p>
                <p class="text-xl font-extrabold mt-0.5 stat-value" style="color: rgb(var(--primary));">
                    <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$weekTotal, 0) ?>
                </p>
            </div>
        </div>
        <div class="relative flex-1 min-h-[240px]">
            <canvas id="salesChart" style="width:100%;height:100%;"></canvas>
        </div>
        <script>
        (function(){
            var labels = <?= json_encode(array_column($chartDays, 'label'), JSON_UNESCAPED_UNICODE) ?>;
            var values = <?= json_encode(array_map(fn($d) => (float)$d['total'], $chartDays)) ?>;
            var sym    = <?= json_encode($currencySymbol, JSON_UNESCAPED_UNICODE) ?>;
            var canvas = document.getElementById('salesChart');
            if (!canvas || typeof Chart === 'undefined') return;
            var ctx  = canvas.getContext('2d');
            var grad = ctx.createLinearGradient(0, 0, 0, 260);
            grad.addColorStop(0,   'rgba(79,70,229,0.14)');
            grad.addColorStop(0.7, 'rgba(79,70,229,0.03)');
            grad.addColorStop(1,   'rgba(79,70,229,0)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: 'rgb(79,70,229)',
                        backgroundColor: grad,
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgb(79,70,229)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: 'rgb(79,70,229)',
                        tension: 0.42,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: true,
                            padding: 10,
                            backgroundColor: 'rgb(9,11,17)',
                            titleColor: 'rgb(156,163,175)',
                            bodyColor: '#fff',
                            cornerRadius: 10,
                            borderColor: 'rgba(255,255,255,.06)',
                            borderWidth: 1,
                            callbacks: {
                                label: function(ctx) { return ' ' + sym + ' ' + ctx.parsed.y.toLocaleString(); }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { family: 'Tajawal, sans-serif', size: 11 }, color: 'rgb(107,114,128)' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                            border: { display: false },
                            ticks: {
                                font: { family: 'Tajawal, sans-serif', size: 11 },
                                color: 'rgb(107,114,128)',
                                callback: function(v) { return sym + ' ' + Number(v).toLocaleString(); }
                            }
                        }
                    }
                }
            });
        })();
        </script>
    </div>

    <!-- Recent Sales -->
    <div class="app-card-flat p-5 flex flex-col animate-slide-up" style="animation-delay: 160ms;">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-sm font-bold" style="color: rgb(var(--foreground));">آخر المبيعات</h3>
                <p class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">أحدث الفواتير</p>
            </div>
            <a href="<?= $bp ?><?= $isAdmin ? '/reports' : '/sales' ?>"
               class="w-8 h-8 flex items-center justify-center rounded-xl transition-all"
               style="background: rgb(var(--primary-subtle)); color: rgb(var(--primary));"
               aria-label="<?= $isAdmin ? 'التقارير' : 'المبيعات' ?>">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto space-y-1.5 min-h-[200px]">
            <?php
            $recentSales = $recentSales ?? [];
            if (empty($recentSales)):
            ?>
            <div class="empty-state py-10">
                <div class="empty-state-icon"><i class="fa-solid fa-receipt"></i></div>
                <p class="text-sm font-semibold mb-1">لا توجد مبيعات حديثة</p>
                <a href="<?= $bp ?>/sales/create" class="text-sm font-bold" style="color: rgb(var(--primary));">إنشاء فاتورة</a>
            </div>
            <?php else: ?>
            <?php foreach ($recentSales as $sale):
                $payMethod = $sale['payment_method'] ?? 'cash';
                $payLabel  = $payMethod === 'card' ? 'بطاقة' : ($payMethod === 'mixed' ? 'مختلط' : 'نقدي');
                $statusClass = $sale['status'] === 'paid' ? 'badge-success' : ($sale['status'] === 'pending' ? 'badge-warning' : 'badge-neutral');
            ?>
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group"
                 style="border: 1px solid transparent;"
                 onmouseover="this.style.background='rgb(var(--muted))'; this.style.borderColor='rgb(var(--border))'"
                 onmouseout="this.style.background=''; this.style.borderColor='transparent'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                     style="background: rgb(var(--color-success-light)); color: rgb(var(--color-success));">
                    <i class="fa-solid fa-receipt text-xs" aria-hidden="true"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate" style="color: rgb(var(--foreground));">
                        <?= htmlspecialchars($sale['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="text-[10.5px] truncate" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars($sale['customer_name'] ?? 'زائر', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="text-end shrink-0">
                    <p class="text-xs font-extrabold stat-value" style="color: rgb(var(--foreground));">
                        <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($sale['total'] ?? 0), 0) ?>
                    </p>
                    <span class="badge <?= $statusClass ?> mt-0.5"><?= $payLabel ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="<?= $bp ?><?= $isAdmin ? '/reports' : '/sales' ?>"
           class="mt-4 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all btn-secondary focus:outline-none">
            <i class="fa-solid fa-list text-[10px]" aria-hidden="true"></i>
            عرض كل المبيعات
        </a>
    </div>
</div>

</div><!-- .dashboard-page -->

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
