<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<div class="max-w-4xl">
    <h3 class="text-lg font-bold text-slate-800 mb-6">مشتريات جديدة (إعادة تخزين)</h3>
    <form id="purchase-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">المورد (اختياري)</label>
            <input type="text" name="supplier" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500" placeholder="اسم المورد">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">البنود</label>
            <div id="purchase-items" class="space-y-3">
                <div class="purchase-row flex gap-2 items-end border-b border-gray-100 pb-3">
                    <select name="product_id[]" class="flex-1 rounded-lg border-gray-300 px-3 py-2 border focus:ring-2 focus:ring-blue-500 product-select">
                        <option value="">— اختر منتجًا —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-price="<?= (float)$p['cost'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?> (د.ع <?= number_format((float)$p['cost'], 0) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity[]" min="1" value="1" placeholder="الكمية" class="w-24 rounded-lg border-gray-300 px-3 py-2 border focus:ring-2 focus:ring-blue-500 qty-input">
                    <input type="number" name="unit_cost[]" step="0.01" min="0" placeholder="سعر الوحدة" class="w-32 rounded-lg border-gray-300 px-3 py-2 border focus:ring-2 focus:ring-blue-500 cost-input">
                    <span class="line-total font-medium text-slate-800 w-24">د.ع 0</span>
                    <button type="button" class="remove-row text-red-500 hover:text-red-700 px-2">×</button>
                </div>
            </div>
            <button type="button" id="add-purchase-row" class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">+ إضافة بند آخر</button>
        </div>
        <div class="pt-4 flex justify-between items-center border-t border-gray-200">
            <p class="text-lg font-bold text-slate-800">الإجمالي: <span id="purchase-grand-total">د.ع 0</span></p>
            <div class="flex gap-3">
                <a href="/purchases" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">إلغاء</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium">حفظ المشتريات</button>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    const productOptions = <?= json_encode(array_map(function($p) {
        return ['id' => (int)$p['id'], 'name' => $p['name'], 'cost' => (float)$p['cost']];
    }, $products)) ?>;
    function addRow() {
        const tpl = document.querySelector('.purchase-row').cloneNode(true);
        tpl.querySelector('select').selectedIndex = 0;
        tpl.querySelector('.qty-input').value = 1;
        tpl.querySelector('.cost-input').value = '';
        tpl.querySelector('.line-total').textContent = 'د.ع 0';
        tpl.querySelectorAll('select option').forEach((o, i) => { if (i === 0) return; const pid = o.value; const prod = productOptions.find(p => p.id == pid); if (prod) o.setAttribute('data-price', prod.cost); });
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
            totalSpan.textContent = 'د.ع ' + (q * c).toLocaleString();
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
        document.getElementById('purchase-grand-total').textContent = 'د.ع ' + total.toLocaleString();
    }
    document.getElementById('add-purchase-row').addEventListener('click', addRow);
    document.querySelectorAll('.purchase-row').forEach(bindRow);

    document.getElementById('purchase-form').onsubmit = async function(e) {
        e.preventDefault();
        const rows = document.querySelectorAll('.purchase-row');
        const items = [];
        rows.forEach(row => {
            const productId = parseInt(row.querySelector('.product-select').value, 10);
            const qty = parseInt(row.querySelector('.qty-input').value, 10);
            const unitCost = parseFloat(row.querySelector('.cost-input').value) || 0;
            if (productId && qty > 0) items.push({ product_id: productId, quantity: qty, unit_cost: unitCost, total: qty * unitCost });
        });
        if (!items.length) { alert('أضف بنداً واحداً على الأقل'); return; }
        const body = {
            csrf_token: document.querySelector('input[name="csrf_token"]').value,
            supplier: document.querySelector('input[name="supplier"]').value.trim(),
            items
        };
        const res = await fetch('/api/purchases', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const json = await res.json();
        if (json.success && json.redirect) window.location.href = json.redirect;
        else alert(json.error || 'حدث خطأ أثناء الحفظ');
    };
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
