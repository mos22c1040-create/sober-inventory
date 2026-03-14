<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $bp = $basePathSafe ?? ''; $isAdmin = ($_SESSION['role'] ?? '') === 'admin'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">الأنواع</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">الأنواع</h1>
        <p class="page-subtitle">أنواع المنتجات — تصنيف إضافي للمنتجات</p>
    </header>
    <?php if ($isAdmin): ?>
    <a href="<?= $bp ?>/types/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium btn-primary focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
        <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> إضافة نوع
    </a>
    <?php endif; ?>
</div>

<div class="app-card-flat overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead style="background: rgb(var(--muted));">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الوصف</th>
                    <?php if ($isAdmin): ?><th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th><?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));">
                <?php if (empty($types)): ?>
                <tr><td colspan="<?= $isAdmin ? 3 : 2 ?>" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-shapes" aria-hidden="true"></i></div>
                        <p class="font-medium">لا توجد أنواع بعد.</p>
                        <?php if ($isAdmin): ?><a href="<?= $bp ?>/types/create" class="inline-flex items-center gap-2 mt-3 text-sm font-bold" style="color: rgb(var(--primary));"><i class="fa-solid fa-plus" aria-hidden="true"></i> إضافة نوع</a><?php endif; ?>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($types as $t): ?>
                <tr class="app-table-row transition-colors duration-200">
                    <td class="px-6 py-4 text-sm font-medium" style="color: rgb(var(--foreground));"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($t['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <?php if ($isAdmin): ?>
                    <td class="px-6 py-4 text-center">
                        <a href="<?= $bp ?>/types/edit?id=<?= (int)$t['id'] ?>" class="text-sm font-medium ms-3" style="color: rgb(var(--primary));">تعديل</a>
                        <button type="button" onclick="deleteType(<?= (int)$t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name']), ENT_QUOTES, 'UTF-8') ?>')" class="text-sm font-medium cursor-pointer" style="color: rgb(var(--color-danger));">حذف</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
function deleteType(id, name) {
    if (!confirm('حذف النوع «' + name + '»؟')) return;
    fetch((window.APP_BASE || '') + '/api/types/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, csrf_token: csrfToken })
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'فشل الحذف');
    });
}
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
