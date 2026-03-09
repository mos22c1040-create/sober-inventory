<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <a href="/sales" class="hover:text-blue-600 transition-colors">المبيعات</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium">فاتورة جديدة</span>
</nav>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">فاتورة مبيعات جديدة</h1>
    <p class="text-sm text-slate-500 mt-1">امسح الباركود لإضافة المنتجات فوراً للفاتورة</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- الجانب الأيمن: الفاتورة والبنود -->
    <div class="lg:col-span-2 space-y-6">
        <!-- قسم البحث والباركود -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 flex items-center gap-2 bg-slate-50 border border-gray-200 rounded-xl px-4 py-3 focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all">
                    <i class="fa-solid fa-barcode text-gray-400 text-lg"></i>
                    <input type="text" id="barcode-input" placeholder="امسح الباركود بالكابل أو اكتب الرمز (SKU)..." autocomplete="off" autofocus
                           class="w-full border-0 bg-transparent text-sm outline-none placeholder-gray-400 font-medium text-slate-800">
                </div>
                <button type="button" id="search-btn" class="min-w-[44px] min-h-[44px] px-5 py-3 rounded-xl text-emerald-600 bg-emerald-50 hover:bg-emerald-100 font-bold focus:ring-2 focus:ring-emerald-400 transition-colors cursor-pointer whitespace-nowrap">
                    إضافة للفاتورة
                </button>
                <a href="/barcode-scan" target="_blank" class="min-w-[44px] min-h-[44px] px-5 py-3 rounded-xl text-blue-600 bg-blue-50 hover:bg-blue-100 font-bold focus:ring-2 focus:ring-blue-400 transition-colors cursor-pointer whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-mobile-screen"></i> كاميرا الجوال
                </a>
            </div>
            <div id="barcode-alert" class="hidden mt-3 text-sm font-medium px-4 py-2 rounded-lg" role="alert"></div>
        </div>

        <!-- جدول المنتجات في الفاتورة -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">المنتجات (<span id="items-count">0</span>)</h3>
                <button type="button" id="clear-cart" class="text-sm text-red-500 hover:text-red-700 font-medium"><i class="fa-solid fa-trash-can ms-1"></i> إفراغ الفاتورة</button>
            </div>
            <div class="overflow-x-auto min-h-[300px]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">المنتج</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">السعر</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 w-32">الكمية</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">المجموع</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 w-16">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items" class="divide-y divide-gray-100">
                        <tr id="empty-cart-msg"><td colspan="5" class="px-4 py-16 text-center text-gray-400 font-medium">الفاتورة فارغة. امسح الباركود للبدء.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- الجانب الأيسر: الدفع والإرسال -->
    <div class="space-y-6">
        <form id="sale-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل (اختياري)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-user text-gray-400"></i>
                    </div>
                    <input type="text" id="customer_name" placeholder="عميل نقدي" class="w-full rounded-xl border-gray-300 pr-10 pl-4 py-2.5 border focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">طريقة الدفع</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" class="peer sr-only" checked>
                        <div class="rounded-xl border border-gray-200 py-2.5 px-3 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 hover:bg-gray-50 transition-all font-medium text-sm">
                            <i class="fa-solid fa-money-bill ms-1"></i> نقدي
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="peer sr-only">
                        <div class="rounded-xl border border-gray-200 py-2.5 px-3 text-center peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 hover:bg-gray-50 transition-all font-medium text-sm">
                            <i class="fa-solid fa-credit-card ms-1"></i> بطاقة
                        </div>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 mb-6 space-y-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">إجمالي البنود:</span>
                    <span class="font-medium text-slate-800" id="summary-items">0</span>
                </div>
                <div class="flex justify-between items-center text-lg font-bold">
                    <span class="text-slate-800">المبلغ المطلوب:</span>
                    <span class="text-emerald-600" id="summary-total"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="w-full min-h-[50px] rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-lg focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 transition-colors cursor-pointer shadow-lg shadow-emerald-500/30 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-check ms-2"></i> تأكيد وحفظ الفاتورة
            </button>
        </form>
    </div>
</div>

<script>
(function() {
    const currencySym = <?= json_encode($currencySymbol, JSON_UNESCAPED_UNICODE) ?>;
    const barcodeInput = document.getElementById('barcode-input');
    const searchBtn = document.getElementById('search-btn');
    const alertBox = document.getElementById('barcode-alert');
    const cartItemsEl = document.getElementById('cart-items');
    const emptyMsg = document.getElementById('empty-cart-msg');
    
    let cart = [];

    function showAlert(msg, isError) {
        alertBox.textContent = msg;
        alertBox.className = `mt-3 text-sm font-medium px-4 py-2 rounded-lg block ${isError ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200'}`;
        setTimeout(() => { alertBox.classList.add('hidden'); alertBox.classList.remove('block'); }, 3000);
    }

    function updateCart() {
        if (cart.length === 0) {
            emptyMsg.style.display = 'table-row';
            Array.from(cartItemsEl.children).forEach(tr => { if (tr.id !== 'empty-cart-msg') tr.remove(); });
        } else {
            emptyMsg.style.display = 'none';
            Array.from(cartItemsEl.children).forEach(tr => { if (tr.id !== 'empty-cart-msg') tr.remove(); });
            
            cart.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.className = 'table-row-hover';
                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm font-medium text-slate-800">
                        <div class="line-clamp-2">${item.name}</div>
                        <div class="text-xs text-gray-500 mt-1">المتوفر: ${item.stock}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-center font-medium text-gray-600">${currencySym} ${Number(item.price).toLocaleString()}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center">
                            <button type="button" class="w-8 h-8 rounded-r-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 z-10" onclick="changeQty(${index}, 1)">+</button>
                            <input type="number" value="${item.qty}" min="1" max="${item.stock}" class="w-12 h-8 border-y border-gray-100 text-center text-sm font-bold outline-none focus:ring-2 focus:ring-emerald-500 z-20" onchange="setQty(${index}, this.value)">
                            <button type="button" class="w-8 h-8 rounded-l-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 z-10" onclick="changeQty(${index}, -1)">−</button>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-left font-bold text-slate-800">${currencySym} ${(item.qty * item.price).toLocaleString()}</td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" onclick="removeItem(${index})" class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors focus:ring-2 focus:ring-red-400"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                `;
                cartItemsEl.appendChild(tr);
            });
        }

        let totalQty = 0;
        let totalPrice = 0;
        cart.forEach(i => { totalQty += i.qty; totalPrice += (i.qty * i.price); });
        
        document.getElementById('items-count').textContent = totalQty;
        document.getElementById('summary-items').textContent = totalQty;
        document.getElementById('summary-total').textContent = `${currencySym} ${totalPrice.toLocaleString()}`;
        document.getElementById('submit-btn').disabled = (cart.length === 0);
    }

    window.changeQty = function(index, delta) {
        const item = cart[index];
        const newQty = item.qty + delta;
        if (newQty > 0 && newQty <= item.stock) {
            item.qty = newQty;
            updateCart();
        } else if (newQty > item.stock) {
            showAlert('الكمية المطلوبة تتجاوز المخزون المتوفر!', true);
        }
    };

    window.setQty = function(index, val) {
        let newQty = parseInt(val, 10) || 1;
        const item = cart[index];
        if (newQty > item.stock) {
            newQty = item.stock;
            showAlert('تم تصحيح الكمية للحد الأقصى المتوفر', true);
        }
        if (newQty < 1) newQty = 1;
        item.qty = newQty;
        updateCart();
    };

    window.removeItem = function(index) {
        cart.splice(index, 1);
        updateCart();
    };

    document.getElementById('clear-cart').addEventListener('click', function() {
        if (cart.length === 0) return;
        if (confirm('هل أنت متأكد من إفراغ الفاتورة؟')) {
            cart = [];
            updateCart();
        }
    });

    function addProductBySku(sku) {
        if (!sku) return;
        fetch('/api/products/barcode?sku=' + encodeURIComponent(sku))
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                showAlert(data.error, true);
                return;
            }
            if (data.quantity <= 0) {
                showAlert('المنتج نفد من المخزون!', true);
                return;
            }
            const existing = cart.find(i => i.id === data.id);
            if (existing) {
                if (existing.qty < data.quantity) {
                    existing.qty++;
                    showAlert('تمت زيادة الكمية', false);
                } else {
                    showAlert('لا يمكن إضافة المزيد (نفد المخزون)', true);
                }
            } else {
                cart.push({
                    id: data.id,
                    name: data.name,
                    sku: data.sku,
                    price: parseFloat(data.price),
                    stock: parseInt(data.quantity, 10),
                    qty: 1
                });
                showAlert('تمت الإضافة للفاتورة', false);
            }
            updateCart();
        })
        .catch(() => showAlert('خطأ في الاتصال!', true));
    }

    // Handle barcode scanner input (waits for enter)
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addProductBySku(this.value.trim());
            this.value = '';
        }
    });
    searchBtn.addEventListener('click', function() {
        addProductBySku(barcodeInput.value.trim());
        barcodeInput.value = '';
        barcodeInput.focus();
    });

    // Mobile scanner polling — checks for new barcode from bridge every 2s
    let _lastBridgeSku = null;
    setInterval(function() {
        fetch('/api/barcode-last')
        .then(r => r.json())
        .then(data => {
            if (data && data.sku && data.sku !== _lastBridgeSku) {
                _lastBridgeSku = data.sku;
                addProductBySku(data.sku);
            }
        }).catch(()=>null);
    }, 2000);

    // Form Submission
    document.getElementById('sale-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (cart.length === 0) return;

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin ms-2"></i> جاري الحفظ...';

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const customerName = document.getElementById('customer_name').value.trim();
        const csrfToken = document.getElementById('csrf_token').value;

        const items = cart.map(i => ({ product_id: i.id, quantity: i.qty }));

        try {
            const res = await fetch('/api/sales', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items, customer_name: customerName, payment_method: paymentMethod, csrf_token: csrfToken })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                showAlert(data.error || 'حدث خطأ أثناء الحفظ', true);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check ms-2"></i> تأكيد وحفظ الفاتورة';
            }
        } catch (e) {
            showAlert('خطأ في الاتصال بالخادم', true);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check ms-2"></i> تأكيد وحفظ الفاتورة';
        }
    });

    updateCart();
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
