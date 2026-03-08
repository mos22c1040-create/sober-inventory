<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<div class="max-w-2xl">
    <h3 class="text-lg font-bold text-slate-800 mb-6"><?= $category ? 'تعديل التصنيف' : 'إضافة تصنيف' ?></h3>
    <form id="category-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($category): ?><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><?php endif; ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الاسم *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium"><?= $category ? 'حفظ التعديلات' : 'إضافة' ?></button>
            <a href="/categories" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">إلغاء</a>
        </div>
    </form>
</div>

<script>
document.getElementById('category-form').onsubmit = async function(e) {
    e.preventDefault();
    const form = this;
    const data = new FormData(form);
    const body = {};
    data.forEach((v, k) => body[k] = v);
    if (body.id) body.id = parseInt(body.id, 10);
    const url = body.id ? '/api/categories/update' : '/api/categories';
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const json = await res.json();
    if (json.success && json.redirect) window.location.href = json.redirect;
    else alert(json.error || 'حدث خطأ أثناء الحفظ');
};
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
