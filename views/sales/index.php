<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$todayTotal   = $todayTotal   ?? 0;
$todayCount   = $todayCount   ?? 0;
$monthlyTotal = $monthlyTotal ?? 0;
?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">المبيعات</span>
</nav>

<!-- Header -->
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">المبيعات والفواتير</h1>
        <p class="text-sm text-slate-500 mt-0.5">تتبع جميع الفواتير وحركة خروج المخزون</p>
    </div>
    <a href="/sales/create"
       class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-bold shadow-md focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 transition-colors">
        <i class="fa-solid fa-plus"></i> فاتورة جديدة
    </a>
</div>

<!-- بطاقات الإحصائيات -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-money-bill-wave text-emerald-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium">مبيعات اليوم</p>
            <p class="text-xl font-bold text-slate-800 mt-0.5"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($todayTotal, 0) ?></p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-receipt text-blue-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium">فواتير اليوم</p>
            <p class="text-xl font-bold text-slate-800 mt-0.5"><?= (int) $todayCount ?> فاتورة</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-calendar text-purple-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium">مبيعات الشهر</p>
            <p class="text-xl font-bold text-slate-800 mt-0.5"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($monthlyTotal, 0) ?></p>
        </div>
    </div>
</div>

<!-- شريط البحث والفلتر -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-center">
    <div class="flex-1 min-w-[200px] relative">
        <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
        <input type="text" id="table-search"
            placeholder="ابحث برقم الفاتورة أو اسم العميل..."
            class="w-full rounded-xl border border-gray-200 pr-9 pl-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 bg-slate-50 transition-all">
    </div>
    <select id="filter-payment"
        class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-400 bg-slate-50 transition-all">
        <option value="">كل طرق الدفع</option>
        <option value="cash">نقدي</option>
        <option value="card">بطاقة</option>
    </select>
    <select id="filter-date"
        class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-400 bg-slate-50 transition-all">
        <option value="">كل الفترات</option>
        <option value="today">اليوم</option>
        <option value="week">آخر 7 أيام</option>
        <option value="month">هذا الشهر</option>
    </select>
    <span id="results-count" class="text-xs text-slate-400 font-medium hidden"></span>
</div>

<!-- الجدول -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full" id="sales-table">
            <thead class="bg-slate-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500">رقم الفاتورة</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500">العميل</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500">الدفع</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500">الإجمالي</th>
                    <?php if (!empty($sales) && isset($sales[0]['discount'])): ?>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500">الخصم</th>
                    <?php endif; ?>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500">الكاشير</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50" id="sales-tbody">
                <?php if (empty($sales)): ?>
                <tr><td colspan="7" class="px-5 py-16 text-center text-gray-400">
                    <i class="fa-solid fa-receipt text-4xl opacity-20 block mb-3"></i>
                    لا توجد مبيعات بعد. <a href="/sales/create" class="text-emerald-600 hover:underline font-medium">أضف فاتورة جديدة</a>
                </td></tr>
                <?php else: ?>
                <?php foreach ($sales as $sale): ?>
                <?php
                    $pm    = $sale['payment_method'] ?? 'cash';
                    $disc  = isset($sale['discount']) ? (float) $sale['discount'] : 0;
                    $notes = $sale['notes'] ?? '';
                    $ts    = strtotime($sale['created_at'] ?? '');
                    $dateStr = $ts ? date('Y/m/d', $ts) : '';
                    $timeStr = $ts ? date('H:i', $ts) : '';
                    $isToday = $dateStr === date('Y/m/d');
                ?>
                <tr class="table-row-hover transition-colors duration-150 cursor-default sale-row"
                    data-invoice="<?= htmlspecialchars($sale['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-customer="<?= htmlspecialchars($sale['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-payment="<?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>"
                    data-ts="<?= (int) $ts ?>">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-emerald-500 text-xs"></i>
                            <span class="font-semibold text-slate-800 text-sm">
                                <?= htmlspecialchars($sale['invoice_number'] ?? '#' . $sale['id'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <?php if ($notes): ?>
                        <div class="text-xs text-slate-400 mt-0.5 ms-5 truncate max-w-[180px]" title="<?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-note-sticky me-1"></i><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700">
                        <?= htmlspecialchars($sale['customer_name'] ?? 'عميل نقدي', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <?php if ($pm === 'card'): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fa-solid fa-credit-card text-xs"></i> بطاقة
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            <i class="fa-solid fa-money-bill text-xs"></i> نقدي
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-center font-bold text-slate-800 text-sm">
                        <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float) $sale['total'], 0) ?>
                    </td>
                    <?php if (isset($sale['discount'])): ?>
                    <td class="px-5 py-3.5 text-center text-sm">
                        <?php if ($disc > 0): ?>
                        <span class="text-orange-600 font-medium">− <?= number_format($disc, 0) ?></span>
                        <?php else: ?>
                        <span class="text-slate-300">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="px-5 py-3.5 text-center text-sm text-slate-500">
                        <?= htmlspecialchars($sale['cashier_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="text-sm text-slate-700 font-medium"><?= $dateStr ?></div>
                        <div class="text-xs text-slate-400 flex items-center gap-1 justify-end">
                            <i class="fa-regular fa-clock text-xs"></i><?= $timeStr ?>
                            <?php if ($isToday): ?>
                            <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-1.5 rounded-full">اليوم</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- لا توجد نتائج عند الفلتر -->
    <div id="no-results" class="hidden px-5 py-12 text-center text-gray-400">
        <i class="fa-solid fa-magnifying-glass text-3xl opacity-20 block mb-2"></i>
        لا توجد فواتير تطابق البحث
    </div>
</div>

<?php if (($pagination['pages'] ?? 1) > 1): ?>
<div class="flex items-center justify-between mt-4 px-1">
    <p class="text-sm text-slate-500">
        صفحة <?= $pagination['page'] ?> من <?= $pagination['pages'] ?> — إجمالي <?= number_format($pagination['total']) ?> فاتورة
    </p>
    <div class="flex items-center gap-1">
        <?php if ($pagination['page'] > 1): ?>
        <a href="?page=<?= $pagination['page'] - 1 ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-slate-600">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </a>
        <?php endif; ?>
        <?php for ($pg = max(1, $pagination['page'] - 2); $pg <= min($pagination['pages'], $pagination['page'] + 2); $pg++): ?>
        <a href="?page=<?= $pg ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors <?= $pg === $pagination['page'] ? 'bg-emerald-600 text-white shadow-sm' : 'border border-slate-200 hover:bg-slate-50 text-slate-600' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a href="?page=<?= $pagination['page'] + 1 ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-slate-600">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    const searchEl   = document.getElementById('table-search');
    const filterPay  = document.getElementById('filter-payment');
    const filterDate = document.getElementById('filter-date');
    const rows       = document.querySelectorAll('.sale-row');
    const noResults  = document.getElementById('no-results');
    const countEl    = document.getElementById('results-count');

    function applyFilters() {
        const q    = searchEl.value.trim().toLowerCase();
        const pay  = filterPay.value;
        const dFil = filterDate.value;
        const now  = Date.now() / 1000;
        let visible = 0;

        rows.forEach(function(tr) {
            const inv  = tr.dataset.invoice.toLowerCase();
            const cust = tr.dataset.customer.toLowerCase();
            const pm   = tr.dataset.payment;
            const ts   = parseInt(tr.dataset.ts);

            const matchQ   = !q   || inv.includes(q) || cust.includes(q);
            const matchPay = !pay || pm === pay;
            let   matchDate = true;
            if (dFil === 'today') {
                const d = new Date(ts * 1000), t = new Date();
                matchDate = d.toDateString() === t.toDateString();
            } else if (dFil === 'week') {
                matchDate = (now - ts) <= 7 * 86400;
            } else if (dFil === 'month') {
                const d = new Date(ts * 1000), t = new Date();
                matchDate = d.getMonth() === t.getMonth() && d.getFullYear() === t.getFullYear();
            }

            const show = matchQ && matchPay && matchDate;
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
        if (q || pay || dFil) {
            countEl.classList.remove('hidden');
            countEl.textContent = visible + ' نتيجة';
        } else {
            countEl.classList.add('hidden');
        }
    }

    searchEl.addEventListener('input', applyFilters);
    filterPay.addEventListener('change', applyFilters);
    filterDate.addEventListener('change', applyFilters);
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
