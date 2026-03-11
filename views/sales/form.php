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

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">فاتورة مبيعات جديدة</h1>
        <p class="text-sm text-slate-500 mt-0.5">ابحث بالاسم أو الباركود لإضافة المنتجات</p>
    </div>
    <a href="/sales" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl px-4 py-2 transition-colors">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> العودة للمبيعات
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ======= الجانب الأيمن: بحث + سلة الفاتورة ======= -->
    <div class="lg:col-span-2 space-y-5">

        <!-- بحث ذكي: اسم أو باركود + مسح كاميرا -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fa-solid fa-magnifying-glass text-emerald-500 me-1"></i> ابحث عن منتج
            </label>
            <div class="relative flex gap-2">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="search-input"
                        placeholder="اكتب اسم المنتج أو رمز الباركود..."
                        autocomplete="off"
                        class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 bg-slate-50 transition-all"
                        autofocus>
                    <!-- قائمة نتائج البحث -->
                    <div id="search-dropdown"
                        class="absolute top-full right-0 left-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-50 hidden overflow-hidden max-h-72 overflow-y-auto">
                    </div>
                </div>
                <button type="button" id="btn-add-search"
                    class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm focus:ring-2 focus:ring-emerald-400 transition-colors shrink-0">
                    إضافة
                </button>
                <button type="button" id="btn-sale-camera"
                    class="px-4 py-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold text-sm focus:ring-2 focus:ring-blue-400 transition-colors shrink-0 flex items-center gap-2">
                    <i class="fa-solid fa-camera"></i><span class="hidden sm:inline">مسح</span>
                </button>
            </div>
            <div id="search-alert" class="hidden mt-3 text-sm font-medium px-4 py-2 rounded-lg" role="alert"></div>
        </div>

        <!-- جدول بنود الفاتورة -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 bg-slate-50/70 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-emerald-500"></i>
                    بنود الفاتورة
                    <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-0.5 rounded-full" id="items-count-badge">0</span>
                </h3>
                <button type="button" id="clear-cart"
                    class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 font-medium px-3 py-1.5 rounded-lg transition-colors">
                    <i class="fa-solid fa-trash-can me-1"></i> إفراغ
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 w-8">#</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">المنتج</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 w-28">السعر</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 w-36">الكمية</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 w-28">المجموع</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items" class="divide-y divide-gray-50">
                        <tr id="empty-cart-msg">
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <i class="fa-solid fa-cart-shopping text-4xl opacity-30"></i>
                                    <span class="text-sm font-medium">الفاتورة فارغة — ابحث أو امسح باركود للبدء</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======= الجانب الأيسر: ملخص الدفع ======= -->
    <div class="space-y-5">
        <form id="sale-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-5 space-y-5">
            <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <!-- اسم العميل -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    <i class="fa-solid fa-user text-slate-400 me-1"></i> اسم العميل
                </label>
                <input type="text" id="customer_name" placeholder="عميل نقدي"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none transition-all bg-slate-50">
            </div>

            <!-- طريقة الدفع -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-wallet text-slate-400 me-1"></i> طريقة الدفع
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer select-none">
                        <input type="radio" name="payment_method" value="cash" class="peer sr-only" checked>
                        <div class="rounded-xl border border-gray-200 py-2.5 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 peer-checked:font-bold hover:bg-gray-50 transition-all text-sm text-gray-600">
                            <i class="fa-solid fa-money-bill me-1"></i> نقدي
                        </div>
                    </label>
                    <label class="cursor-pointer select-none">
                        <input type="radio" name="payment_method" value="card" class="peer sr-only">
                        <div class="rounded-xl border border-gray-200 py-2.5 text-center peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 peer-checked:font-bold hover:bg-gray-50 transition-all text-sm text-gray-600">
                            <i class="fa-solid fa-credit-card me-1"></i> بطاقة
                        </div>
                    </label>
                </div>
            </div>

            <!-- الخصم -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    <i class="fa-solid fa-tag text-slate-400 me-1"></i> خصم
                </label>
                <div class="flex gap-2 items-center">
                    <div class="flex-1 relative">
                        <input type="number" id="discount-amount" min="0" step="any" placeholder="0"
                            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition-all bg-slate-50">
                    </div>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200 text-xs font-bold shrink-0">
                        <button type="button" id="disc-type-fixed"
                            class="px-3 py-2.5 bg-orange-500 text-white transition-colors"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" id="disc-type-pct"
                            class="px-3 py-2.5 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">%</button>
                    </div>
                </div>
            </div>

            <!-- ملاحظات -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    <i class="fa-solid fa-note-sticky text-slate-400 me-1"></i> ملاحظات (اختياري)
                </label>
                <textarea id="sale-notes" rows="2" placeholder="أي ملاحظات للفاتورة..."
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none transition-all bg-slate-50 resize-none"></textarea>
            </div>

            <!-- ملخص المبالغ -->
            <div class="bg-slate-50 rounded-xl p-4 space-y-2.5 border border-slate-100">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>المجموع الفرعي</span>
                    <span id="summary-subtotal" class="font-medium text-slate-700"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                </div>
                <div class="flex justify-between text-sm text-orange-600 hidden" id="discount-row">
                    <span>الخصم</span>
                    <span id="summary-discount" class="font-medium">− <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                    <span class="font-bold text-slate-800 text-base">الإجمالي</span>
                    <span id="summary-total" class="font-bold text-emerald-600 text-xl"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                </div>
            </div>

            <!-- المبلغ المدفوع والباقي (للدفع النقدي) -->
            <div id="cash-section">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    <i class="fa-solid fa-hand-holding-dollar text-slate-400 me-1"></i> المبلغ المستلم
                </label>
                <input type="number" id="amount-paid" min="0" step="any" placeholder="0"
                    class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:border-blue-400 outline-none transition-all bg-slate-50">
                <div id="change-row" class="hidden mt-2 flex justify-between items-center bg-blue-50 rounded-lg px-4 py-2.5 border border-blue-100">
                    <span class="text-sm font-semibold text-blue-700">الباقي للعميل</span>
                    <span id="change-amount" class="font-bold text-blue-700 text-base"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0</span>
                </div>
            </div>

            <!-- زر الحفظ -->
            <button type="submit" id="submit-btn" disabled
                class="w-full min-h-[52px] rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-base focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 transition-all flex items-center justify-center gap-2 shadow-md">
                <i class="fa-solid fa-check"></i> تأكيد وحفظ الفاتورة
            </button>
        </form>
    </div>
</div>

<!-- ======= طبقة المسح بالكاميرا ======= -->
<div id="sale-cam-overlay"
     class="fixed inset-0 z-[200] flex items-center justify-center bg-black/85 p-4"
     style="display:none!important">
    <div class="bg-slate-900 rounded-2xl w-full max-w-sm shadow-2xl border border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-700">
            <span class="text-white font-semibold">مسح الباركود</span>
            <button type="button" id="sale-cam-close"
                class="w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div id="sale-qr-reader" class="w-full bg-black" style="min-height:260px"></div>
        <p id="sale-cam-status" class="text-slate-400 text-sm text-center px-4 py-3">جاري تشغيل الكاميرا...</p>
    </div>
</div>

<!-- ======= نافذة نجاح الحفظ ======= -->
<div id="success-overlay"
     class="fixed inset-0 z-[300] flex items-center justify-center bg-black/60 p-4"
     style="display:none!important">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-check text-emerald-600 text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-1">تم حفظ الفاتورة!</h2>
        <p id="success-invoice-num" class="text-slate-500 text-sm mb-6"></p>
        <div class="flex flex-col gap-3">
            <a href="#" id="btn-print-receipt" target="_blank"
               class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-print"></i> طباعة الوصل
            </a>
            <div class="flex gap-3">
                <a href="/sales/create" id="btn-new-sale"
                   class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition-colors">
                    <i class="fa-solid fa-plus me-1"></i> فاتورة جديدة
                </a>
                <a href="/sales" id="btn-go-sales"
                   class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-colors">
                    قائمة المبيعات
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    'use strict';
    const currencySym  = <?= json_encode($currencySymbol, JSON_UNESCAPED_UNICODE) ?>;
    const searchInput  = document.getElementById('search-input');
    const searchDrop   = document.getElementById('search-dropdown');
    const btnAddSearch = document.getElementById('btn-add-search');
    const alertBox     = document.getElementById('search-alert');
    const cartItemsEl  = document.getElementById('cart-items');
    const emptyMsg     = document.getElementById('empty-cart-msg');
    const discountInp  = document.getElementById('discount-amount');
    const amountPaidEl = document.getElementById('amount-paid');

    let cart         = [];
    let discType     = 'fixed'; // 'fixed' | 'pct'
    let searchTimer  = null;
    let selectedIdx  = -1;

    /* ===== utils ===== */
    function fmt(n) { return Number(n).toLocaleString('ar-IQ'); }

    function showAlert(msg, isError) {
        alertBox.textContent = msg;
        alertBox.className = `mt-3 text-sm font-medium px-4 py-2.5 rounded-lg block ${isError
            ? 'bg-red-50 text-red-600 border border-red-200'
            : 'bg-emerald-50 text-emerald-700 border border-emerald-200'}`;
        clearTimeout(showAlert._t);
        showAlert._t = setTimeout(() => { alertBox.classList.add('hidden'); }, 3500);
    }

    /* ===== الخصم ===== */
    document.getElementById('disc-type-fixed').addEventListener('click', function() {
        discType = 'fixed';
        this.className = 'px-3 py-2.5 bg-orange-500 text-white transition-colors';
        document.getElementById('disc-type-pct').className = 'px-3 py-2.5 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors';
        updateSummary();
    });
    document.getElementById('disc-type-pct').addEventListener('click', function() {
        discType = 'pct';
        this.className = 'px-3 py-2.5 bg-orange-500 text-white transition-colors';
        document.getElementById('disc-type-fixed').className = 'px-3 py-2.5 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors';
        updateSummary();
    });
    discountInp.addEventListener('input', updateSummary);

    /* ===== الباقي ===== */
    amountPaidEl.addEventListener('input', function() {
        const total = calcTotal();
        const paid  = parseFloat(this.value) || 0;
        const changeRow = document.getElementById('change-row');
        if (paid > 0) {
            changeRow.classList.remove('hidden');
            const change = paid - total;
            document.getElementById('change-amount').textContent =
                (change >= 0 ? '' : '− ') + currencySym + ' ' + fmt(Math.abs(change));
            document.getElementById('change-amount').parentElement.className =
                change >= 0
                ? 'mt-2 flex justify-between items-center bg-blue-50 rounded-lg px-4 py-2.5 border border-blue-100'
                : 'mt-2 flex justify-between items-center bg-red-50 rounded-lg px-4 py-2.5 border border-red-100';
            document.getElementById('change-amount').className =
                change >= 0 ? 'font-bold text-blue-700 text-base' : 'font-bold text-red-600 text-base';
        } else {
            changeRow.classList.add('hidden');
        }
    });

    /* إخفاء قسم المبلغ للدفع بالبطاقة */
    document.querySelectorAll('input[name="payment_method"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.getElementById('cash-section').style.display =
                this.value === 'cash' ? '' : 'none';
        });
    });

    /* ===== حساب الخصم والإجمالي ===== */
    function calcDiscount(subtotal) {
        const v = parseFloat(discountInp.value) || 0;
        if (v <= 0) return 0;
        if (discType === 'pct') return Math.min(subtotal, subtotal * v / 100);
        return Math.min(subtotal, v);
    }
    function calcSubtotal() {
        return cart.reduce((s, i) => s + i.qty * i.price, 0);
    }
    function calcTotal() {
        const sub  = calcSubtotal();
        const disc = calcDiscount(sub);
        return Math.max(0, sub - disc);
    }

    function updateSummary() {
        const sub  = calcSubtotal();
        const disc = calcDiscount(sub);
        const tot  = Math.max(0, sub - disc);

        document.getElementById('summary-subtotal').textContent = currencySym + ' ' + fmt(sub);
        document.getElementById('summary-total').textContent    = currencySym + ' ' + fmt(tot);
        document.getElementById('items-count-badge').textContent = cart.reduce((s, i) => s + i.qty, 0);

        const discRow = document.getElementById('discount-row');
        if (disc > 0) {
            discRow.classList.remove('hidden');
            document.getElementById('summary-discount').textContent = '− ' + currencySym + ' ' + fmt(disc);
        } else {
            discRow.classList.add('hidden');
        }

        /* تحديث الباقي */
        amountPaidEl.dispatchEvent(new Event('input'));
        document.getElementById('submit-btn').disabled = (cart.length === 0);
    }

    /* ===== عرض السلة ===== */
    function updateCart() {
        const rows = Array.from(cartItemsEl.children).filter(tr => tr.id !== 'empty-cart-msg');
        rows.forEach(tr => tr.remove());

        if (cart.length === 0) {
            emptyMsg.style.display = 'table-row';
        } else {
            emptyMsg.style.display = 'none';
            cart.forEach(function(item, index) {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/60 transition-colors';
                tr.innerHTML = `
                    <td class="px-4 py-3 text-xs text-slate-400 font-medium">${index + 1}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800 text-sm leading-tight">${item.name}</div>
                        ${item.sku ? `<div class="text-xs text-slate-400 mt-0.5">${item.sku}</div>` : ''}
                        <div class="text-xs text-slate-400">متوفر: ${item.stock}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-slate-600">${fmt(item.price)}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="inline-flex items-center border border-gray-200 rounded-lg overflow-hidden">
                            <button type="button" onclick="changeQty(${index},-1)"
                                class="w-8 h-8 bg-gray-50 hover:bg-gray-100 text-slate-600 font-bold text-lg leading-none transition-colors">−</button>
                            <input type="number" value="${item.qty}" min="1" max="${item.stock}"
                                class="w-12 h-8 border-x border-gray-200 text-center text-sm font-bold outline-none bg-white"
                                onchange="setQty(${index}, this.value)">
                            <button type="button" onclick="changeQty(${index},1)"
                                class="w-8 h-8 bg-gray-50 hover:bg-gray-100 text-slate-600 font-bold text-lg leading-none transition-colors">+</button>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-slate-800 text-sm">${fmt(item.qty * item.price)}</td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" onclick="removeItem(${index})"
                            class="w-7 h-7 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </td>`;
                cartItemsEl.appendChild(tr);
            });
        }
        updateSummary();
    }

    window.changeQty = function(i, d) {
        const item = cart[i];
        const nq   = item.qty + d;
        if (nq < 1) return;
        if (nq > item.stock) { showAlert('وصلت للحد الأقصى المتوفر!', true); return; }
        item.qty = nq;
        updateCart();
    };
    window.setQty = function(i, v) {
        const item = cart[i];
        let nq = parseInt(v, 10) || 1;
        if (nq > item.stock) { nq = item.stock; showAlert('تم التصحيح للحد الأقصى المتوفر', true); }
        if (nq < 1) nq = 1;
        item.qty = nq;
        updateCart();
    };
    window.removeItem = function(i) {
        cart.splice(i, 1);
        updateCart();
    };

    document.getElementById('clear-cart').addEventListener('click', function() {
        if (cart.length === 0) return;
        if (confirm('إفراغ الفاتورة بالكامل؟')) { cart = []; updateCart(); }
    });

    /* ===== إضافة منتج ===== */
    function addProduct(p) {
        const stock = parseInt(p.quantity, 10) || 0;
        if (stock <= 0) { showAlert('المنتج نفد من المخزون!', true); return; }
        const ex = cart.find(i => i.id == p.id);
        if (ex) {
            if (ex.qty < ex.stock) { ex.qty++; showAlert('تمت زيادة الكمية', false); }
            else showAlert('لا يمكن إضافة المزيد (نفد المخزون)', true);
        } else {
            cart.push({ id: p.id, name: p.name, sku: p.sku || '', price: parseFloat(p.price), stock, qty: 1 });
            showAlert('✓ تمت الإضافة', false);
        }
        updateCart();
        searchInput.value = '';
        closeDropdown();
        searchInput.focus();
    }

    function addBySku(sku) {
        if (!sku) return;
        fetch('/api/products/barcode?sku=' + encodeURIComponent(sku))
        .then(r => r.json())
        .then(data => {
            if (data.error) { showAlert(data.error, true); return; }
            addProduct(data.product || data);
        })
        .catch(() => showAlert('خطأ في الاتصال!', true));
    }

    /* ===== البحث بالاسم ===== */
    function closeDropdown() {
        searchDrop.classList.add('hidden');
        searchDrop.innerHTML = '';
        selectedIdx = -1;
    }

    function renderDropdown(products) {
        searchDrop.innerHTML = '';
        if (!products.length) {
            searchDrop.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400 text-center">لا توجد نتائج</div>';
            searchDrop.classList.remove('hidden');
            return;
        }
        products.forEach(function(p, idx) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-emerald-50 transition-colors border-b border-gray-50 last:border-0';
            div.dataset.idx = idx;
            const stockColor = p.quantity <= 3 ? 'text-red-500' : 'text-slate-400';
            div.innerHTML = `
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">${p.name}</div>
                    ${p.sku ? `<div class="text-xs text-slate-400">${p.sku}</div>` : ''}
                </div>
                <div class="text-right ms-3 shrink-0">
                    <div class="text-sm font-bold text-emerald-600">${fmt(p.price)}</div>
                    <div class="text-xs ${stockColor}">${p.quantity} متوفر</div>
                </div>`;
            div.addEventListener('click', function() { addProduct(p); });
            searchDrop.appendChild(div);
        });
        searchDrop.classList.remove('hidden');
        selectedIdx = -1;
    }

    function doSearch(q) {
        if (q.length < 1) { closeDropdown(); return; }
        fetch('/api/products/search?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(renderDropdown)
        .catch(closeDropdown);
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const v = this.value.trim();
        if (!v) { closeDropdown(); return; }
        searchTimer = setTimeout(() => doSearch(v), 220);
    });

    /* تنقل بالكيبورد في الدروبداون */
    searchInput.addEventListener('keydown', function(e) {
        const items = searchDrop.querySelectorAll('[data-idx]');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIdx = Math.min(selectedIdx + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('bg-emerald-50', i === selectedIdx));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIdx = Math.max(selectedIdx - 1, -1);
            items.forEach((el, i) => el.classList.toggle('bg-emerald-50', i === selectedIdx));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIdx >= 0 && items[selectedIdx]) {
                items[selectedIdx].click();
            } else {
                /* محاولة باركود */
                const val = this.value.trim();
                if (val) addBySku(val);
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    btnAddSearch.addEventListener('click', function() {
        const v = searchInput.value.trim();
        const items = searchDrop.querySelectorAll('[data-idx]');
        if (selectedIdx >= 0 && items[selectedIdx]) {
            items[selectedIdx].click();
        } else if (v) {
            addBySku(v);
        }
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchDrop.contains(e.target)) closeDropdown();
    });

    /* ===== مسح الكاميرا ===== */
    (function() {
        var btnCam   = document.getElementById('btn-sale-camera');
        var overlay  = document.getElementById('sale-cam-overlay');
        var btnClose = document.getElementById('sale-cam-close');
        var statusEl = document.getElementById('sale-cam-status');
        var scanner  = null;

        function showOverlay() { overlay.style.removeProperty('display'); overlay.style.display = 'flex'; }
        function hideOverlay() { overlay.style.display = 'none'; }
        function closeCam() {
            if (scanner) { scanner.stop().catch(function(){}); scanner = null; }
            hideOverlay();
        }

        btnCam && btnCam.addEventListener('click', function() {
            showOverlay();
            statusEl && (statusEl.textContent = 'جاري تشغيل الكاميرا...');
            document.getElementById('sale-qr-reader').innerHTML = '';
            scanner = new Html5Qrcode('sale-qr-reader', { verbose: false });
            scanner.start({ facingMode: 'environment' }, {
                fps: 15, qrbox: { width: 260, height: 160 }, aspectRatio: 1.5,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.QR_CODE
                ]
            }, function(code) { closeCam(); addBySku(code); }, function(){})
            .then(function() { statusEl && (statusEl.textContent = 'وجّه الكاميرا نحو الباركود'); })
            .catch(function() { statusEl && (statusEl.textContent = 'تعذّر فتح الكاميرا. تأكد من منح الإذن.'); });
        });
        btnClose && btnClose.addEventListener('click', closeCam);
        overlay  && overlay.addEventListener('click', function(e) { if (e.target === overlay) closeCam(); });
    })();

    /* ===== إرسال الفاتورة ===== */
    document.getElementById('sale-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (cart.length === 0) return;

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> جاري الحفظ...';

        const subtotal = calcSubtotal();
        const discount = calcDiscount(subtotal);

        const body = {
            items: cart.map(i => ({ product_id: i.id, quantity: i.qty })),
            customer_name:  document.getElementById('customer_name').value.trim(),
            payment_method: document.querySelector('input[name="payment_method"]:checked').value,
            discount,
            notes:     document.getElementById('sale-notes').value.trim(),
            csrf_token: document.getElementById('csrf_token').value
        };

        try {
            const res  = await fetch('/api/sales', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            const data = await res.json();
            if (data.success) {
                /* نافذة النجاح */
                const so = document.getElementById('success-overlay');
                document.getElementById('success-invoice-num').textContent =
                    'الإجمالي: ' + currencySym + ' ' + fmt(calcTotal());
                /* رابط طباعة الوصل */
                if (data.sale_id) {
                    document.getElementById('btn-print-receipt').href = '/sales/receipt?id=' + data.sale_id;
                }
                so.style.removeProperty('display');
                so.style.display = 'flex';
            } else {
                showAlert(data.error || 'حدث خطأ أثناء الحفظ', true);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check me-2"></i> تأكيد وحفظ الفاتورة';
            }
        } catch (err) {
            showAlert('خطأ في الاتصال بالخادم', true);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check me-2"></i> تأكيد وحفظ الفاتورة';
        }
    });

    /* أول تهيئة */
    updateCart();
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
