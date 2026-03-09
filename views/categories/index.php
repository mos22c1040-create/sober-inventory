<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">التصنيفات</span>
</nav>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">التصنيفات</h1>
        <p class="text-sm text-slate-500 mt-1">تنظيم المنتجات حسب التصنيف</p>
    </div>
    <a href="/categories/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium shadow-md btn-primary focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 cursor-pointer">
        <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> إضافة تصنيف
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الرابط</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الوصف</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($categories)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">لا توجد تصنيفات بعد. <a href="/categories/create" class="text-blue-600 hover:underline">أضف تصنيفاً</a>.</td></tr>
                <?php else: ?>
                <?php foreach ($categories as $c): ?>
                <tr class="table-row-hover transition-colors duration-200">
                    <td class="px-6 py-4 text-sm font-medium text-slate-800"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($c['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($c['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-center">
                        <a href="/categories/edit?id=<?= (int)$c['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium ms-3">تعديل</a>
                        <button type="button" onclick="deleteCategory(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name']), ENT_QUOTES, 'UTF-8') ?>')" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
function deleteCategory(id, name) {
    if (!confirm('حذف التصنيف «' + name + '»؟')) return;
    fetch('/api/categories/delete', {
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
