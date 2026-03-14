<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $bp = $basePathSafe ?? ''; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <a href="<?= $bp ?>/types" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">الأنواع</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));"><?= $type ? 'تعديل نوع' : 'إضافة نوع' ?></span>
</nav>

<div class="max-w-2xl">
    <h1 class="page-title mb-6"><?= $type ? 'تعديل النوع' : 'إضافة نوع' ?></h1>
    <form id="type-form" class="app-card-flat p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($type): ?><input type="hidden" name="id" value="<?= (int)$type['id'] ?>"><?php endif; ?>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">الاسم <span style="color: rgb(var(--color-danger));">*</span></label>
            <input type="text" name="name" required value="<?= htmlspecialchars($type['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="app-input w-full rounded-lg px-4 py-2.5 text-sm border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">الوصف</label>
            <textarea name="description" rows="3"
                      class="app-input w-full rounded-lg px-4 py-2.5 text-sm border resize-none" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"><?= htmlspecialchars($type['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" id="submit-btn" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-semibold btn-primary cursor-pointer transition-colors duration-200" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <?= $type ? 'حفظ التعديلات' : 'إضافة' ?>
            </button>
            <a href="<?= $bp ?>/types" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium border cursor-pointer flex items-center transition-colors duration-200" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">
                إلغاء
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('type-form').onsubmit = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    const data = new FormData(this);
    const body = {};
    data.forEach((v, k) => body[k] = v);
    if (body.id) body.id = parseInt(body.id, 10);
    const url = body.id ? '/api/types/update' : '/api/types';
    const base = (window.APP_BASE || '');
    try {
        const res = await fetch(base + url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const json = await res.json();
        if (json.success && json.redirect) window.location.href = base + json.redirect;
        else alert(json.error || 'حدث خطأ أثناء الحفظ');
    } catch(err) {
        alert('فشل الاتصال بالسيرفر');
    } finally {
        btn.disabled = false;
    }
};
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
