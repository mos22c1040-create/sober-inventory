<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <a href="/expenses" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">المصروفات</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));"><?= $expense ? 'تعديل مصروف' : 'إضافة مصروف' ?></span>
</nav>

<div class="max-w-lg">
    <h1 class="page-title mb-6"><?= $expense ? 'تعديل المصروف' : 'إضافة مصروف جديد' ?></h1>

    <form id="expense-form" class="app-card-flat p-6 space-y-5" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($expense): ?><input type="hidden" name="id" value="<?= (int)$expense['id'] ?>"><?php endif; ?>

        <!-- المبلغ -->
        <div>
            <label for="amount" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">
                المبلغ (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>) <span style="color: rgb(var(--color-danger));">*</span>
            </label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                   value="<?= htmlspecialchars($expense['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                   placeholder="0.00">
        </div>

        <!-- التصنيف -->
        <div>
            <label for="category" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">التصنيف <span style="color: rgb(var(--color-danger));">*</span></label>
            <select id="category" name="category" required
                    class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($expense['category'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- التاريخ -->
        <div>
            <label for="expense_date" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">تاريخ المصروف <span style="color: rgb(var(--color-danger));">*</span></label>
            <input type="date" id="expense_date" name="expense_date" required
                   value="<?= htmlspecialchars($expense['expense_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                   class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
        </div>

        <!-- الوصف -->
        <div>
            <label for="description" class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">الوصف (اختياري)</label>
            <textarea id="description" name="description" rows="3"
                      class="app-input w-full rounded-lg border px-4 py-2.5 text-sm resize-none" style="border-color: rgb(var(--border)); background: rgb(var(--muted));"
                      placeholder="تفاصيل إضافية عن المصروف..."><?= htmlspecialchars($expense['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div id="form-error" class="hidden rounded-lg px-4 py-3 text-sm bg-red-50 border border-red-200 text-red-700"></div>

        <div class="flex gap-3 pt-2">
            <button type="submit" id="submit-btn" class="flex-1 min-h-[44px] rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                <i class="fa-solid fa-<?= $expense ? 'floppy-disk' : 'plus' ?> me-2" aria-hidden="true"></i>
                <?= $expense ? 'حفظ التعديلات' : 'إضافة المصروف' ?>
            </button>
            <a href="/expenses" class="flex-1 min-h-[44px] flex items-center justify-center rounded-lg text-sm font-medium border cursor-pointer transition-colors duration-200" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">
                إلغاء
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('expense-form').onsubmit = async function(e) {
    e.preventDefault();
    var btn = document.getElementById('submit-btn');
    var errEl = document.getElementById('form-error');
    errEl.classList.add('hidden');
    btn.disabled = true;

    var data = new FormData(this);
    var body = {};
    data.forEach(function(v, k) { body[k] = v; });
    if (body.id) body.id = parseInt(body.id, 10);
    body.amount = parseFloat(body.amount) || 0;

    var url = body.id ? '/api/expenses/update' : '/api/expenses';
    try {
        var res  = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        var json = await res.json();
        if (json.success && json.redirect) window.location.href = json.redirect;
        else { errEl.textContent = json.error || 'حدث خطأ أثناء الحفظ'; errEl.classList.remove('hidden'); }
    } catch(err) {
        errEl.textContent = 'فشل الاتصال بالسيرفر'; errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
};
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
