<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">المصروفات</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">المصروفات</h1>
        <p class="page-subtitle">تتبع النفقات التشغيلية للمتجر</p>
    </header>
    <a href="/expenses/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium btn-primary focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
        <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> إضافة مصروف
    </a>
</div>

<!-- ملخص هذا الشهر + تصنيفات -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
    <div class="app-card p-5">
        <p class="text-xs font-bold uppercase tracking-widest mb-1.5" style="color: rgb(var(--muted-foreground));">مصاريف الشهر</p>
        <p class="text-2xl font-bold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)($monthlyTotal ?? 0), 0) ?></p>
        <p class="text-xs mt-2" style="color: rgb(var(--muted-foreground));"><?= date('F Y') ?></p>
    </div>
    <div class="app-card-flat p-5 md:col-span-2">
        <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: rgb(var(--muted-foreground));">توزيع المصاريف (آخر 30 يوماً)</p>
        <?php if (empty($summary)): ?>
        <p class="text-sm" style="color: rgb(var(--muted-foreground));">لا توجد بيانات.</p>
        <?php else: ?>
        <?php
        $summaryTotal = array_sum(array_column($summary, 'total'));
        foreach ($summary as $row):
            $pct = $summaryTotal > 0 ? round(($row['total'] / $summaryTotal) * 100) : 0;
        ?>
        <div class="mb-2">
            <div class="flex justify-between text-xs font-medium mb-0.5">
                <span style="color: rgb(var(--foreground));"><?= htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') ?></span>
                <span style="color: rgb(var(--muted-foreground));"><?= $pct ?>% — <?= number_format((float)$row['total'], 0) ?></span>
            </div>
            <div class="w-full rounded-full h-1.5" style="background: rgb(var(--muted));">
                <div class="h-1.5 rounded-full" style="width: <?= $pct ?>%; background: rgb(var(--primary));"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- تنبيه حذف -->
<div id="alert-box" class="hidden rounded-lg p-4 mb-4 border text-sm font-medium" role="alert" aria-live="polite"></div>

<!-- جدول المصروفات -->
<div class="app-card-flat overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead style="background: rgb(var(--muted));">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التاريخ</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التصنيف</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الوصف</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المبلغ (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المسجِّل</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));" id="expenses-tbody">
                <?php if (empty($expenses)): ?>
                <tr><td colspan="6" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-receipt" aria-hidden="true"></i></div>
                        <p class="font-medium">لا توجد مصروفات مسجّلة بعد.</p>
                        <a href="/expenses/create" class="inline-flex items-center gap-2 mt-3 text-sm font-bold" style="color: rgb(var(--primary));"><i class="fa-solid fa-plus" aria-hidden="true"></i> إضافة مصروف</a>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($expenses as $exp): ?>
                <tr class="app-table-row transition-colors duration-200" id="row-<?= (int)$exp['id'] ?>">
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--foreground));"><?= htmlspecialchars($exp['expense_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4">
                        <span class="badge" style="background: rgb(var(--primary) / 0.1); color: rgb(var(--primary));"><?= htmlspecialchars($exp['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm max-w-xs truncate" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($exp['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-left text-sm font-semibold" style="color: rgb(var(--foreground));"><?= number_format((float)$exp['amount'], 0) ?></td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($exp['created_by'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-1">
                            <a href="/expenses/edit?id=<?= (int)$exp['id'] ?>" class="p-2 rounded-lg transition-colors cursor-pointer" style="color: rgb(var(--primary));" title="تعديل">
                                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                            </a>
                            <button type="button" onclick="deleteExpense(<?= (int)$exp['id'] ?>, '<?= htmlspecialchars($exp['category'], ENT_QUOTES, 'UTF-8') ?>')"
                                    class="p-2 rounded-lg transition-colors cursor-pointer" style="color: rgb(var(--color-danger));" title="حذف">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (($pagination['pages'] ?? 1) > 1): ?>
    <div class="flex items-center justify-between px-6 py-4" style="border-top: 1px solid rgb(var(--border));">
        <p class="text-sm" style="color: rgb(var(--muted-foreground));">
            <?= (int)$pagination['total'] ?> مصروف — صفحة <?= (int)$pagination['page'] ?> من <?= (int)$pagination['pages'] ?>
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['page'] > 1): ?>
            <a href="?page=<?= $pagination['page'] - 1 ?>" class="min-h-[36px] px-4 py-1.5 rounded-lg text-sm font-medium border transition-colors cursor-pointer" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">السابق</a>
            <?php endif; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?>
            <a href="?page=<?= $pagination['page'] + 1 ?>" class="min-h-[36px] px-4 py-1.5 rounded-lg text-sm font-medium transition-colors cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">التالي</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>

<script>
const _csrf = '<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>';
function showAlert(msg, ok) {
    var b = document.getElementById('alert-box');
    b.className = 'rounded-lg p-4 mb-4 border text-sm font-medium ' + (ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700');
    b.textContent = msg;
    b.classList.remove('hidden');
    setTimeout(() => b.classList.add('hidden'), 4000);
}
function deleteExpense(id, cat) {
    if (!confirm('حذف مصروف «' + cat + '»؟')) return;
    fetch('/api/expenses/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, csrf_token: _csrf })
    }).then(r => r.json()).then(d => {
        if (d.success) { document.getElementById('row-' + id)?.remove(); showAlert('تم الحذف.', true); }
        else showAlert(d.error || 'فشل الحذف.', false);
    }).catch(() => showAlert('تعذّر الاتصال.', false));
}
</script>
