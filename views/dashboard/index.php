<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$isAdmin        = ($_SESSION['role'] ?? '') === 'admin';
$bp             = $basePathSafe ?? '';
?>
<div class="dashboard-page min-h-full w-full">
<header class="dashboard-hero flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">لوحة التحكم</h1>
        <p class="page-subtitle">نظرة عامة على المخزون والمبيعات</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= $bp ?>/pos" class="btn-pos inline-flex items-center gap-2 text-sm text-white focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:outline-none cursor-pointer"
           aria-label="فتح نقطة البيع">
            <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
            <span>نقطة البيع</span>
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?= $bp ?>/reports" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all cursor-pointer"
           style="background: rgb(219 234 254); color: rgb(var(--primary)); border: 1.5px solid rgb(var(--border));">
            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
            <span>التقارير</span>
        </a>
        <?php endif; ?>
    </div>
</header>

<!-- ─── Stats Row ─────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-7 stagger-children">

    <!-- مبيعات اليوم -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(219 234 254); color: rgb(var(--primary));">
                <i class="fa-solid fa-money-bill-wave text-lg" aria-hidden="true"></i>
            </div>
            <span class="badge badge-info">اليوم</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-widest mb-1" style="color: rgb(var(--muted-foreground));">مبيعات اليوم</p>
        <h3 class="stat-value text-2xl font-extrabold" style="color: rgb(var(--foreground));">
            <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($todaySales ?? 0), 0) ?>
        </h3>
        <p class="mt-2 text-xs font-semibold" style="color: rgb(var(--muted-foreground));">
            <i class="fa-solid fa-receipt me-1" aria-hidden="true"></i><?= (int)($todayCount ?? 0) ?> فاتورة
        </p>
        <?php
        $yesterdaySales = (float)($yesterdaySales ?? 0);
        $yesterdayCount  = (int)($yesterdayCount ?? 0);
        if ($yesterdaySales > 0 || $yesterdayCount > 0):
            $salesDiff = $yesterdaySales > 0 ? round((((float)($todaySales ?? 0) - $yesterdaySales) / $yesterdaySales) * 100, 1) : 0;
            $countDiff = $yesterdayCount > 0 ? (int)($todayCount ?? 0) - $yesterdayCount : 0;
        ?>
        <p class="mt-1.5 text-[11px] font-medium" style="color: rgb(var(--muted-foreground));">
            <i class="fa-solid fa-arrow-trend-up me-1" aria-hidden="true"></i>
            مقارنة بأمس: <?= $salesDiff >= 0 ? '+' : '' ?><?= $salesDiff ?>% إيراد · <?= $countDiff >= 0 ? '+' : '' ?><?= $countDiff ?> فاتورة
        </p>
        <?php endif; ?>
    </div>

    <!-- عدد الفواتير -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--color-success-light)); color: rgb(var(--color-success));">
                <i class="fa-solid fa-receipt text-lg" aria-hidden="true"></i>
            </div>
            <span class="badge badge-success">مكتملة</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-widest mb-1" style="color: rgb(var(--muted-foreground));">عدد الفواتير</p>
        <h3 class="stat-value text-2xl font-extrabold" style="color: rgb(var(--foreground));">
            <?= (int)($todayCount ?? 0) ?>
        </h3>
        <p class="mt-2 text-xs font-semibold" style="color: rgb(var(--muted-foreground));">
            <i class="fa-solid fa-clock me-1" aria-hidden="true"></i>المبيعات المكتملة اليوم
        </p>
    </div>

    <!-- إجمالي المنتجات -->
    <div class="stat-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(237 233 254); color: rgb(109 40 217);">
                <i class="fa-solid fa-boxes-stacked text-lg" aria-hidden="true"></i>
            </div>
            <span class="badge" style="background: rgb(237 233 254); color: rgb(109 40 217);">الكتالوج</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-widest mb-1" style="color: rgb(var(--muted-foreground));">إجمالي المنتجات</p>
        <h3 class="stat-value text-2xl font-extrabold" style="color: rgb(var(--foreground));">
            <?= (int)($productCount ?? 0) ?>
        </h3>
        <p class="mt-2 text-xs font-semibold flex items-center gap-1.5" style="color: rgb(var(--muted-foreground));">
            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block" aria-hidden="true"></span>نشطة في المخزون
        </p>
    </div>

    <!-- منخفضة المخزون -->
    <div class="stat-card" style="border-color: rgb(254 202 202);">
        <div class="absolute inset-0 rounded-xl opacity-30 pointer-events-none"
             style="background: linear-gradient(135deg, rgb(254 226 226) 0%, transparent 60%);"></div>
        <div class="relative z-10">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                     style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                    <i class="fa-solid fa-triangle-exclamation text-lg" aria-hidden="true"></i>
                </div>
                <span class="badge badge-danger">تنبيه</span>
            </div>
            <p class="text-[11px] font-bold uppercase tracking-widest mb-1" style="color: rgb(var(--color-danger));">منخفضة المخزون</p>
            <h3 class="stat-value text-2xl font-extrabold" style="color: rgb(var(--foreground));">
                <?= (int)($lowStockCount ?? 0) ?>
            </h3>
            <a href="<?= $bp ?>/products" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors duration-200 cursor-pointer"
               style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                إعادة تخزين <i class="fa-solid fa-arrow-left text-[10px]" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<?php
$lowStockList = $lowStockProducts ?? [];
$hasLowStock = ($lowStockCount ?? 0) > 0;
?>
<?php if ($hasLowStock && !empty($lowStockList)): ?>
<!-- ─── تنبيهات المخزون ───────────────────────────────────────────────── -->
<div class="app-card-flat p-5 mb-5 animate-slide-up" style="animation-delay:50ms; border-color: rgb(254 202 202);">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                <i class="fa-solid fa-triangle-exclamation text-lg" aria-hidden="true"></i>
            </div>
            <div>
                <h3 class="text-base font-bold" style="color: rgb(var(--foreground));">تنبيهات المخزون</h3>
                <p class="text-xs font-medium" style="color: rgb(var(--muted-foreground));">منتجات تحتاج إعادة تخزين</p>
            </div>
        </div>
        <a href="<?= $bp ?>/products" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-colors cursor-pointer"
           style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
            <i class="fa-solid fa-boxes-stacked text-xs" aria-hidden="true"></i>
            عرض المنتجات
        </a>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php foreach (array_slice($lowStockList, 0, 10) as $p): ?>
        <a href="<?= $bp ?>/products" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm border transition-colors cursor-pointer"
           style="border-color: rgb(254 202 202); background: rgb(255 247 247); color: rgb(var(--foreground));">
            <span class="font-medium truncate max-w-[140px]"><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge badge-danger shrink-0"><?= (int)($p['quantity'] ?? 0) ?> / <?= (int)($p['low_stock_threshold'] ?? 0) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (count($lowStockList) > 10): ?>
        <span class="inline-flex items-center px-3 py-2 text-xs font-semibold" style="color: rgb(var(--muted-foreground));">+<?= count($lowStockList) - 10 ?> أخرى</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Chart + Recent Sales ──────────────────────────────────────────── -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    <!-- مخطط المبيعات -->
    <div class="xl:col-span-2 app-card-flat p-5 flex flex-col animate-slide-up" style="animation-delay:100ms;">
        <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
            <div>
                <h3 class="text-base font-bold" style="color: rgb(var(--foreground));">نظرة عامة على المبيعات</h3>
                <p class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">إيرادات آخر 7 أيام</p>
            </div>
            <?php
            $chartDays = $dailyTotals ?? [];
            $weekTotal = array_sum(array_column($chartDays, 'total'));
            ?>
            <div class="text-end">
                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجمالي الأسبوع</p>
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
            var ctx = canvas.getContext('2d');
            var grad = ctx.createLinearGradient(0, 0, 0, 280);
            grad.addColorStop(0, 'rgba(37,99,235,0.18)');
            grad.addColorStop(0.7, 'rgba(37,99,235,0.04)');
            grad.addColorStop(1, 'rgba(37,99,235,0)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: 'rgb(37,99,235)',
                        backgroundColor: grad,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgb(37,99,235)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'rgb(37,99,235)',
                        tension: 0.45,
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
                            backgroundColor: 'rgb(15 23 42)',
                            titleColor: 'rgb(148 163 184)',
                            bodyColor: '#fff',
                            cornerRadius: 10,
                            callbacks: {
                                label: function(ctx) { return ' ' + sym + ' ' + ctx.parsed.y.toLocaleString(); }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { family: 'Tajawal, sans-serif', size: 11 }, color: 'rgb(100 116 139)' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                            border: { display: false },
                            ticks: {
                                font: { family: 'Tajawal, sans-serif', size: 11 },
                                color: 'rgb(100 116 139)',
                                callback: function(v) { return sym + ' ' + Number(v).toLocaleString(); }
                            }
                        }
                    }
                }
            });
        })();
        </script>
    </div>

    <!-- آخر المبيعات -->
    <div class="app-card-flat p-5 flex flex-col animate-slide-up" style="animation-delay:200ms;">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-base font-bold" style="color: rgb(var(--foreground));">آخر المبيعات</h3>
                <p class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">أحدث الفواتير المسجّلة</p>
            </div>
            <a href="<?= $bp ?><?= $isAdmin ? '/reports' : '/sales' ?>"
               class="w-9 h-9 flex items-center justify-center rounded-xl transition-colors duration-200 focus:ring-2 focus:ring-blue-400 cursor-pointer"
               style="background: rgb(219 234 254); color: rgb(var(--primary));"
               aria-label="<?= $isAdmin ? 'التقارير' : 'المبيعات' ?>">
                <i class="fa-solid fa-<?= $isAdmin ? 'chart-pie' : 'list' ?> text-sm" aria-hidden="true"></i>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 min-h-[200px]">
            <?php
            $recentSales = $recentSales ?? [];
            if (empty($recentSales)):
            ?>
            <div class="empty-state py-8">
                <div class="empty-state-icon"><i class="fa-solid fa-receipt"></i></div>
                <p class="text-sm font-medium">لا توجد مبيعات حديثة</p>
                <a href="<?= $bp ?>/sales/create" class="inline-block mt-3 text-sm font-bold text-blue-600 hover:text-blue-700">إنشاء فاتورة</a>
            </div>
            <?php else: ?>
            <?php foreach ($recentSales as $sale):
                $payMethod = $sale['payment_method'] ?? 'cash';
                $payLabel  = $payMethod === 'card' ? 'بطاقة' : ($payMethod === 'mixed' ? 'مختلط' : 'نقدي');
                $statusBg  = $sale['status'] === 'paid' ? 'badge-success' : ($sale['status'] === 'pending' ? 'badge-warning' : 'badge-neutral');
            ?>
            <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl transition-colors duration-150 hover:bg-slate-50 cursor-default group">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-105"
                     style="background: rgb(var(--color-success-light)); color: rgb(var(--color-success));">
                    <i class="fa-solid fa-receipt text-sm" aria-hidden="true"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold truncate" style="color: rgb(var(--foreground));">
                        <?= htmlspecialchars($sale['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="text-xs truncate" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars($sale['customer_name'] ?? 'زائر', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="text-end shrink-0">
                    <p class="text-sm font-bold" style="color: rgb(var(--foreground));">
                        <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($sale['total'] ?? 0), 0) ?>
                    </p>
                    <span class="badge <?= $statusBg ?> mt-0.5"><?= $payLabel ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="<?= $bp ?><?= $isAdmin ? '/reports' : '/sales' ?>"
           class="mt-4 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 border-2 hover:text-blue-600 cursor-pointer"
           style="border-color: rgb(var(--border)); color: rgb(var(--muted-foreground));">
            <i class="fa-solid fa-list text-xs" aria-hidden="true"></i>
            عرض كل المبيعات
        </a>
    </div>
</div>
</div><!-- .dashboard-page -->

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
