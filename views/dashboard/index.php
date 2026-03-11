<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$isAdmin        = ($_SESSION['role'] ?? '') === 'admin';
?>

<header class="page-header">
    <h1 class="page-title">لوحة التحكم</h1>
    <p class="page-subtitle">نظرة عامة على المخزون والمبيعات</p>
</header>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8 stagger-children">
    
    <div class="app-card p-6 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest" style="color: rgb(var(--muted-foreground));">مبيعات اليوم</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold mt-1.5" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($todaySales ?? 0), 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <i class="fa-solid fa-money-bill-wave text-lg" aria-hidden="true"></i>
            </div>
        </div>
        <p class="mt-4 text-sm font-medium" style="color: rgb(var(--muted-foreground));"><?= (int)($todayCount ?? 0) ?> فاتورة اليوم</p>
    </div>

    <div class="app-card p-6 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest" style="color: rgb(var(--muted-foreground));">عدد الفواتير</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold mt-1.5" style="color: rgb(var(--foreground));"><?= (int)($todayCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <i class="fa-solid fa-receipt text-lg" aria-hidden="true"></i>
            </div>
        </div>
        <p class="mt-4 text-sm font-medium" style="color: rgb(var(--muted-foreground));">المبيعات المكتملة اليوم</p>
    </div>

    <div class="app-card p-6 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest" style="color: rgb(var(--muted-foreground));">إجمالي المنتجات</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold mt-1.5" style="color: rgb(var(--foreground));"><?= (int)($productCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <i class="fa-solid fa-boxes-stacked text-lg" aria-hidden="true"></i>
            </div>
        </div>
        <p class="mt-4 text-sm font-medium flex items-center gap-1.5" style="color: rgb(var(--muted-foreground));">
            <span class="w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></span> في الكتالوج
        </p>
    </div>

    <div class="app-card p-6 group relative overflow-hidden" style="border-color: rgb(254 202 202);">
        <div class="absolute top-0 right-0 w-1 h-full rounded-l" style="background: rgb(var(--color-danger));"></div>
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest" style="color: rgb(var(--color-danger));">منخفضة المخزون</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold mt-1.5" style="color: rgb(var(--foreground));"><?= (int)($lowStockCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0" style="background: rgb(var(--color-danger)); color: white;">
                <i class="fa-solid fa-triangle-exclamation text-lg" aria-hidden="true"></i>
            </div>
        </div>
        <a href="/products" class="mt-4 inline-flex items-center gap-2 text-sm font-bold px-4 py-2.5 rounded-lg transition-colors duration-200 focus:ring-2 focus:ring-red-400 focus:ring-offset-2 cursor-pointer bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700">
            إعادة تخزين <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
        </a>
    </div>
</div>

<!-- مخطط وجدول المبيعات -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 app-card-flat p-6 flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-bold" style="color: rgb(var(--foreground));">نظرة عامة على المبيعات</h3>
                <p class="text-xs font-medium mt-1" style="color: rgb(var(--muted-foreground));">إيرادات آخر 7 أيام</p>
            </div>
            <?php
            $chartDays = $dailyTotals ?? [];
            $weekTotal = array_sum(array_column($chartDays, 'total'));
            ?>
            <div class="text-left">
                <p class="text-xs font-medium" style="color: rgb(var(--muted-foreground));">إجمالي الأسبوع</p>
                <p class="text-lg font-bold" style="color: rgb(var(--primary));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$weekTotal, 0) ?></p>
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
            var grad = ctx.createLinearGradient(0, 0, 0, 240);
            grad.addColorStop(0, 'rgba(59,130,246,0.25)');
            grad.addColorStop(1, 'rgba(59,130,246,0.01)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: 'rgb(59,130,246)',
                        backgroundColor: grad,
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgb(59,130,246)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
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
                            callbacks: {
                                label: function(ctx) { return ' ' + sym + ' ' + ctx.parsed.y.toLocaleString(); }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Tajawal, sans-serif', size: 12 } } },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                font: { family: 'Tajawal, sans-serif', size: 11 },
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
    <div class="app-card-flat p-6 flex flex-col relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-slate-100 rounded-full opacity-60"></div>
        <div class="flex justify-between items-center mb-5 relative z-10">
            <h3 class="text-lg font-bold text-slate-800">آخر المبيعات</h3>
            <a href="<?= $isAdmin ? '/reports' : '/sales' ?>" class="touch-target flex items-center justify-center text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl focus:ring-2 focus:ring-blue-400 transition-colors cursor-pointer" aria-label="<?= $isAdmin ? 'التقارير' : 'المبيعات' ?>">
                <i class="fa-solid fa-<?= $isAdmin ? 'chart-pie' : 'list' ?> text-sm"></i>
            </a>
        </div>
        <div class="flex-1 overflow-y-auto space-y-4 pr-2 relative z-10 min-h-[200px]">
            <?php 
            $recentSales = $recentSales ?? [];
            if (empty($recentSales)): 
            ?>
            <div class="empty-state py-8">
                <div class="empty-state-icon"><i class="fa-solid fa-receipt"></i></div>
                <p class="text-sm font-medium">لا توجد مبيعات حديثة</p>
                <a href="/sales/create" class="inline-block mt-3 text-sm font-bold text-blue-600 hover:text-blue-700">إنشاء فاتورة</a>
            </div>
            <?php else: ?>
            <?php foreach ($recentSales as $sale): 
                $statusClass = $sale['status'] === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($sale['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700');
                $payLabel = $sale['payment_method'] === 'card' ? 'بطاقة' : 'نقدي';
            ?>
            <div class="flex items-center justify-between group cursor-pointer hover:bg-slate-50 p-2 -mx-2 rounded-xl transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center border border-emerald-200 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($sale['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs font-medium text-slate-400 mt-0.5"><?= htmlspecialchars($sale['customer_name'] ?? 'زائر', ENT_QUOTES, 'UTF-8') ?> &bull; <?= date('Y/m/j g:i A', strtotime($sale['created_at'] ?? 'now')) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($sale['total'] ?? 0), 0) ?></p>
                    <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[10px] font-bold <?= $statusClass ?>"><?= $payLabel ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="<?= $isAdmin ? '/reports' : '/sales' ?>" class="mt-4 block w-full min-h-[44px] flex items-center justify-center gap-2 py-2.5 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-blue-600 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all text-center cursor-pointer">
            <i class="fa-solid fa-list text-xs"></i> عرض كل المبيعات
        </a>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
