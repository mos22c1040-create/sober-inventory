<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-8rem)]">
    <!-- Products panel -->
    <div class="lg:col-span-2 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <input type="text" id="pos-search" placeholder="البحث بالاسم أو الرمز..." class="w-full rounded-xl border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div id="pos-products" class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
            <p class="col-span-full text-gray-500 text-center py-8">جاري تحميل المنتجات...</p>
        </div>
    </div>

    <!-- Cart panel -->
    <div class="flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">البيع الحالي</h3>
            <button type="button" id="pos-clear" class="text-sm text-red-600 hover:text-red-800">تفريغ</button>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
            <input type="text" id="pos-customer" placeholder="اسم العميل (اختياري)" class="w-full rounded-lg border-gray-300 px-3 py-2 border text-sm mb-3">
            <select id="pos-payment" class="w-full rounded-lg border-gray-300 px-3 py-2 border text-sm mb-4">
                <option value="cash">نقدي</option>
                <option value="card">بطاقة</option>
                <option value="mixed">مختلط</option>
            </select>
            <ul id="pos-cart" class="space-y-2">
            </ul>
            <p id="pos-cart-empty" class="text-gray-500 text-sm py-4">السلة فارغة. أضف منتجات من القائمة.</p>
        </div>
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between text-lg font-bold text-slate-800 mb-4">
                <span>الإجمالي</span>
                <span id="pos-total">د.ع 0</span>
            </div>
            <button type="button" id="pos-complete" disabled class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                إتمام البيع
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const csrfToken = '<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>';
    let products = [];
    let cart = [];

    function loadProducts(q) {
        const url = q ? '/api/pos/products?q=' + encodeURIComponent(q) : '/api/pos/products';
        fetch(url).then(r => r.json()).then(data => {
            products = data.products || [];
            renderProducts();
        });
    }
    function renderProducts() {
        const el = document.getElementById('pos-products');
        if (!products.length) {
            el.innerHTML = '<p class="col-span-full text-gray-500 text-center py-8">لا توجد منتجات.</p>';
            return;
        }
        el.innerHTML = products.map(p => {
            const out = p.quantity <= 0;
            return '<button type="button" class="text-left p-3 rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" ' + (out ? 'disabled' : '') + ' data-id="' + p.id + '" data-name="' + (p.name || '').replace(/"/g, '&quot;') + '" data-price="' + parseFloat(p.price) + '" data-qty="' + parseInt(p.quantity, 10) + '">' +
                '<p class="font-medium text-slate-800 truncate">' + (p.name || '') + '</p>' +
                '<p class="text-sm text-gray-500">د.ع ' + Number(p.price).toLocaleString() + '</p>' +
                (out ? '<p class="text-xs text-red-500 mt-1">نفد من المخزون</p>' : '<p class="text-xs text-gray-400">المخزون: ' + p.quantity + '</p>') +
            '</button>';
        }).join('');
        el.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => addToCart(parseInt(btn.dataset.id, 10), btn.dataset.name, parseFloat(btn.dataset.price), parseInt(btn.dataset.qty, 10)));
        });
    }
    function addToCart(id, name, price, maxQty) {
        const existing = cart.find(x => x.product_id === id);
        if (existing) {
            if (existing.quantity >= maxQty) return;
            existing.quantity++;
            existing.total = existing.quantity * existing.unit_price;
        } else {
            cart.push({ product_id: id, name, quantity: 1, unit_price: price, total: price });
        }
        renderCart();
    }
    function renderCart() {
        const list = document.getElementById('pos-cart');
        const empty = document.getElementById('pos-cart-empty');
        const totalEl = document.getElementById('pos-total');
        const completeBtn = document.getElementById('pos-complete');
        if (!cart.length) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            totalEl.textContent = 'د.ع 0';
            completeBtn.disabled = true;
            return;
        }
        empty.classList.add('hidden');
        let total = 0;
        list.innerHTML = cart.map((item, i) => {
            total += item.total;
            return '<li class="flex justify-between items-center text-sm py-2 border-b border-gray-100">' +
                '<span class="truncate flex-1">' + (item.name || '') + ' × ' + item.quantity + '</span>' +
                '<span class="font-medium ms-2">د.ع ' + Number(item.total).toLocaleString() + '</span>' +
                '<button type="button" class="ml-2 text-red-500 hover:text-red-700" data-index="' + i + '">×</button>' +
            '</li>';
        }).join('');
        totalEl.textContent = 'د.ع ' + Number(total).toLocaleString();
        completeBtn.disabled = false;
        list.querySelectorAll('button[data-index]').forEach(btn => {
            btn.addEventListener('click', () => { cart.splice(parseInt(btn.dataset.index, 10), 1); renderCart(); });
        });
    }
    document.getElementById('pos-clear').addEventListener('click', () => { cart = []; renderCart(); });
    document.getElementById('pos-search').addEventListener('input', function() {
        clearTimeout(this._t);
        this._t = setTimeout(() => loadProducts(this.value.trim()), 200);
    });
    document.getElementById('pos-complete').addEventListener('click', function() {
        if (!cart.length) return;
        this.disabled = true;
        fetch('/api/pos/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: csrfToken,
                customer_name: document.getElementById('pos-customer').value.trim() || 'Walk-in Customer',
                payment_method: document.getElementById('pos-payment').value,
                items: cart.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
            })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                alert('تم إتمام البيع. الفاتورة: ' + (data.invoice_number || ''));
                cart = [];
                renderCart();
                loadProducts(document.getElementById('pos-search').value.trim());
            } else {
                alert(data.error || 'فشل إتمام البيع');
            }
            document.getElementById('pos-complete').disabled = !cart.length;
        }).catch(() => {
            alert('خطأ في الاتصال');
            document.getElementById('pos-complete').disabled = false;
        });
    });
    loadProducts();
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
