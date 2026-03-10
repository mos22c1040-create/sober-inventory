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
    
    <div class="app-card p-6 hover:-translate-y-0.5 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">مبيعات اليوم</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold text-slate-800 mt-1.5"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($todaySales ?? 0), 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-300 shrink-0">
                <i class="fa-solid fa-money-bill-wave text-lg"></i>
            </div>
        </div>
        <p class="mt-4 text-sm text-slate-500 font-medium"><?= (int)($todayCount ?? 0) ?> فاتورة اليوم</p>
    </div>

    <div class="app-card p-6 hover:-translate-y-0.5 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">عدد الفواتير</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold text-slate-800 mt-1.5"><?= (int)($todayCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-purple-500/25 group-hover:scale-105 transition-transform duration-300 shrink-0">
                <i class="fa-solid fa-receipt text-lg"></i>
            </div>
        </div>
        <p class="mt-4 text-sm text-slate-500 font-medium">المبيعات المكتملة اليوم</p>
    </div>

    <div class="app-card p-6 hover:-translate-y-0.5 group">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">إجمالي المنتجات</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold text-slate-800 mt-1.5"><?= (int)($productCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-orange-500/25 group-hover:scale-105 transition-transform duration-300 shrink-0">
                <i class="fa-solid fa-boxes-stacked text-lg"></i>
            </div>
        </div>
        <p class="mt-4 text-sm text-slate-500 font-medium flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> في الكتالوج
        </p>
    </div>

    <div class="app-card p-6 hover:-translate-y-0.5 group relative overflow-hidden border-red-100">
        <div class="absolute top-0 right-0 w-1 h-full bg-red-400 rounded-l"></div>
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-red-500 uppercase tracking-widest">منخفضة المخزون</p>
                <h3 class="stat-value text-2xl md:text-3xl font-bold text-slate-800 mt-1.5"><?= (int)($lowStockCount ?? 0) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-white flex items-center justify-center shadow-lg shadow-red-500/25 group-hover:scale-105 transition-transform duration-300 shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            </div>
        </div>
        <a href="/products" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-red-600 hover:text-red-700 px-4 py-2.5 bg-red-50 hover:bg-red-100 rounded-xl transition-colors focus:ring-2 focus:ring-red-400 focus:ring-offset-2 cursor-pointer">
            إعادة تخزين <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>
    </div>
</div>

<!-- مخطط وجدول المبيعات -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 app-card-flat p-6 flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">نظرة عامة على المبيعات</h3>
                <p class="text-xs text-slate-400 font-medium mt-1">إيرادات آخر 7 أيام</p>
            </div>
            <span class="text-sm bg-slate-100 border border-slate-200 rounded-xl px-4 py-2 text-slate-600 font-medium">آخر 7 أيام</span>
        </div>
        
        <!-- رسم المبيعات (آخر 7 أيام) -->
        <?php
        $chartDays = $dailyTotals ?? [];
        $maxTotal = 1;
        $sumTotal = 0;
        foreach ($chartDays as $d) {
            if ($d['total'] > $maxTotal) $maxTotal = $d['total'];
            $sumTotal += $d['total'];
        }
        $isToday = date('Y-m-d');
        $noSales = ($sumTotal == 0);
        ?>
        <?php if ($noSales): ?>
        <p class="text-sm text-slate-500 mb-4 py-2 px-4 bg-slate-50 rounded-xl border border-slate-100">لا توجد مبيعات في آخر 7 أيام.</p>
        <?php endif; ?>
        <div class="flex-1 min-h-[250px] flex items-end justify-between gap-3 px-2 pb-2">
            <?php foreach ($chartDays as $day): 
                $pct = $maxTotal > 0 ? round(($day['total'] / $maxTotal) * 100) : 0;
                if ($pct < 6 && $day['total'] > 0) $pct = 6;
                if ($day['total'] == 0) $pct = 4;
                $isCurrentDay = ($day['date'] === $isToday);
                $barClass = $isCurrentDay ? 'bg-gradient-to-t from-blue-600 to-blue-400 shadow-md border-t-2 border-blue-400' : ($day['total'] > 0 ? 'bg-blue-100 hover:bg-blue-200' : 'bg-blue-50 hover:bg-blue-100');
            ?>
            <div class="w-full <?= $barClass ?> rounded-t-lg transition-colors relative group flex flex-col items-center justify-end" style="height: <?= $pct ?>%; min-height: 20px;">
                <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-slate-800 text-white text-xs py-1.5 px-3 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity font-bold z-10 whitespace-nowrap"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$day['total'], 0) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-between text-xs font-semibold text-slate-400 mt-4 px-2 border-t border-gray-100 pt-4">
            <?php foreach ($chartDays as $day): 
                $isCurrentDay = ($day['date'] === $isToday);
            ?>
            <span class="w-full text-center <?= $isCurrentDay ? 'text-blue-600 font-bold' : '' ?>"><?= htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>
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
