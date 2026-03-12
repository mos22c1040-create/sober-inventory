<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$bp             = $basePathSafe ?? '';
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$csrfToken      = $csrfToken ?? '';
?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">المشتريات</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">المشتريات</h1>
        <p class="page-subtitle">إدخال مخزون وإعادة التعبئة</p>
    </header>
    <a href="<?= $bp ?>/purchases/create"
       class="inline-flex items-center min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium btn-primary focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200"
       style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
        <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> مشتريات جديدة
    </a>
</div>

<div class="app-card-flat overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead style="background: rgb(var(--muted));">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الرقم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المورد</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الإجمالي</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">بواسطة</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التاريخ</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));">
                <?php if (empty($purchases)): ?>
                <tr><td colspan="6" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i></div>
                        <p class="font-medium">لا توجد مشتريات بعد.</p>
                        <a href="<?= $bp ?>/purchases/create" class="inline-flex items-center gap-2 mt-3 text-sm font-bold" style="color: rgb(var(--primary));"><i class="fa-solid fa-plus" aria-hidden="true"></i> إضافة مشتريات</a>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($purchases as $pu): ?>
                <tr class="app-table-row transition-colors duration-200">
                    <td class="px-6 py-4 text-sm font-semibold" style="color: rgb(var(--foreground));">#<?= (int) $pu['id'] ?></td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars($pu['supplier'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-center font-bold" style="color: rgb(var(--foreground));">
                        <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float) $pu['total'], 0) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-center" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars($pu['created_by'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));">
                        <?= htmlspecialchars(date('Y/m/d H:i', strtotime($pu['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button type="button"
                                class="btn-delete-purchase inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 border border-red-100 hover:bg-red-100 transition-colors"
                                data-id="<?= (int) $pu['id'] ?>"
                                data-supplier="<?= htmlspecialchars($pu['supplier'] ?? '#' . $pu['id'], ENT_QUOTES, 'UTF-8') ?>"
                                title="حذف هذه العملية وعكس تأثيرها على المخزون">
                            <i class="fa-solid fa-trash-can text-xs"></i> حذف
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const BASE = (document.querySelector('meta[name="app-base"]') || {}).content || '';
    const CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;

    document.querySelectorAll('.btn-delete-purchase').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id       = btn.dataset.id;
            const supplier = btn.dataset.supplier;
            if (!confirm('حذف عملية الشراء "' + supplier + '"؟\nسيتم خصم الكميات من المخزون.')) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';

            fetch(BASE + '/api/purchases/delete', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ id: parseInt(id, 10), csrf_token: CSRF })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    btn.closest('tr').remove();
                } else {
                    alert(data.error || 'حدث خطأ');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-trash-can text-xs"></i> حذف';
                }
            })
            .catch(function () {
                alert('خطأ في الاتصال');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can text-xs"></i> حذف';
            });
        });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
