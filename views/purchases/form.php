<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <a href="/purchases" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">المشتريات</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">مشتريات جديدة</span>
</nav>

<div class="max-w-4xl">
    <h1 class="page-title mb-6">مشتريات جديدة (إعادة تخزين)</h1>
    <form id="purchase-form" class="app-card-flat p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">المورد (اختياري)</label>
            <input type="text" name="supplier" class="app-input w-full rounded-lg border px-4 py-2.5 text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));" placeholder="اسم المورد">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-2" style="color: rgb(var(--foreground));">البنود</label>
            <div id="purchase-items" class="space-y-3">
                <div class="purchase-row flex flex-wrap gap-2 items-end pb-3" style="border-bottom: 1px solid rgb(var(--border));">
                    <select name="product_id[]" class="app-input flex-1 min-w-[160px] rounded-lg border px-3 py-2.5 text-sm product-select" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                        <option value="">— اختر منتجًا —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-price="<?= (float)$p['cost'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$p['cost'], 0) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity[]" min="1" value="1" placeholder="الكمية" class="app-input w-24 rounded-lg border px-3 py-2.5 text-sm qty-input" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                    <input type="number" name="unit_cost[]" step="0.01" min="0" placeholder="سعر الوحدة" class="app-input w-32 rounded-lg border px-3 py-2.5 text-sm cost-input" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                    <span class="line-total font-semibold w-28 text-sm" data-currency="<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>" style="color: rgb(var(--foreground));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                    <button type="button" class="remove-row min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg transition-colors duration-200 cursor-pointer" style="color: rgb(var(--color-danger));" aria-label="حذف البند">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button type="button" id="add-purchase-row" class="mt-3 inline-flex items-center gap-2 text-sm font-medium transition-colors duration-200 cursor-pointer" style="color: rgb(var(--primary));">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> إضافة بند آخر
            </button>
        </div>
        <div class="pt-4 flex flex-wrap justify-between items-center gap-4" style="border-top: 1px solid rgb(var(--border));">
            <p class="text-lg font-bold" style="color: rgb(var(--foreground));">الإجمالي: <span id="purchase-grand-total" class="stat-value"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span></p>
            <div class="flex gap-3">
                <a href="/purchases" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium border flex items-center transition-colors duration-200 cursor-pointer" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">إلغاء</a>
                <button type="submit" id="submit-btn" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
                    <i class="fa-solid fa-floppy-disk ms-2" aria-hidden="true"></i> حفظ المشتريات
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    const currencySym = <?= json_encode($currencySymbol, JSON_UNESCAPED_UNICODE) ?>;
    const productOptions = <?= json_encode(array_map(function($p) {
        return ['id' => (int)$p['id'], 'name' => $p['name'], 'cost' => (float)$p['cost']];
    }, $products)) ?>;
    function addRow() {
        const tpl = document.querySelector('.purchase-row').cloneNode(true);
        tpl.querySelector('select').selectedIndex = 0;
        tpl.querySelector('.qty-input').value = 1;
        tpl.querySelector('.cost-input').value = '';
        tpl.querySelector('.line-total').textContent = currencySym + ' 0';
        tpl.querySelectorAll('select option').forEach((o, i) => {
            if (i === 0) return;
            const prod = productOptions.find(p => p.id == o.value);
            if (prod) o.setAttribute('data-price', prod.cost);
        });
        document.getElementById('purchase-items').appendChild(tpl);
        bindRow(tpl);
    }
    function bindRow(row) {
        const sel = row.querySelector('.product-select');
        const qty = row.querySelector('.qty-input');
        const cost = row.querySelector('.cost-input');
        const totalSpan = row.querySelector('.line-total');
        function update() {
            const q = parseInt(qty.value, 10) || 0;
            const c = parseFloat(cost.value) || 0;
            totalSpan.textContent = currencySym + ' ' + (q * c).toLocaleString();
            updateGrandTotal();
        }
        sel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price) cost.value = opt.dataset.price;
            update();
        });
        qty.addEventListener('input', update);
        cost.addEventListener('input', update);
        update();
        row.querySelector('.remove-row').onclick = () => { row.remove(); updateGrandTotal(); };
    }
    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.purchase-row').forEach(row => {
            const q = parseInt(row.querySelector('.qty-input').value, 10) || 0;
            const c = parseFloat(row.querySelector('.cost-input').value) || 0;
            total += q * c;
        });
        document.getElementById('purchase-grand-total').textContent = currencySym + ' ' + total.toLocaleString();
    }
    document.getElementById('add-purchase-row').addEventListener('click', addRow);
    document.querySelectorAll('.purchase-row').forEach(bindRow);

    document.getElementById('purchase-form').onsubmit = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        const rows = document.querySelectorAll('.purchase-row');
        const items = [];
        rows.forEach(row => {
            const productId = parseInt(row.querySelector('.product-select').value, 10);
            const qty = parseInt(row.querySelector('.qty-input').value, 10);
            const unitCost = parseFloat(row.querySelector('.cost-input').value) || 0;
            if (productId && qty > 0) items.push({ product_id: productId, quantity: qty, unit_cost: unitCost, total: qty * unitCost });
        });
        if (!items.length) { alert('أضف بنداً واحداً على الأقل'); btn.disabled = false; return; }
        try {
            const res = await fetch('/api/purchases', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: document.querySelector('input[name="csrf_token"]').value,
                    supplier: document.querySelector('input[name="supplier"]').value.trim(),
                    items
                })
            });
            const json = await res.json();
            if (json.success && json.redirect) window.location.href = json.redirect;
            else alert(json.error || 'حدث خطأ أثناء الحفظ');
        } catch(err) {
            alert('فشل الاتصال بالسيرفر');
        } finally {
            btn.disabled = false;
        }
    };
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
