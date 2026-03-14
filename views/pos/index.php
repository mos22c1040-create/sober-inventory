<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
?>

<style>
/* POS-specific styles */
.pos-product-card {
    display: flex;
    flex-direction: column;
    background: rgb(var(--card));
    border: 1.5px solid rgb(var(--border));
    border-radius: 1rem;
    padding: 0.875rem 0.75rem;
    cursor: pointer;
    user-select: none;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
    position: relative;
    overflow: hidden;
}
.pos-product-card:hover:not(:disabled) {
    border-color: rgb(var(--primary));
    box-shadow: 0 0 0 3px rgb(var(--primary) / 0.09), 0 4px 14px rgb(0 0 0 / 0.06);
    transform: translateY(-2px);
}
.pos-product-card:active:not(:disabled) { transform: scale(0.97); }
.pos-product-card:disabled { opacity: 0.40; cursor: not-allowed; }

.pos-qty-btn {
    width: 26px; height: 26px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid rgb(var(--border));
    background: rgb(var(--card));
    color: rgb(var(--foreground));
    cursor: pointer;
    transition: background 0.12s, border-color 0.12s;
    font-size: 14px;
    line-height: 1;
    flex-shrink: 0;
}
.pos-qty-btn:hover {
    background: rgb(var(--muted));
    border-color: rgb(var(--border-strong));
}

/* Payment method radio */
.pos-pay-label {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 0.625rem 0.5rem;
    border-radius: 0.75rem;
    border: 1.5px solid rgb(var(--border));
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
    background: rgb(var(--card));
    color: rgb(var(--muted-foreground));
}
.pos-pay-label:hover {
    border-color: rgb(var(--border-strong));
    background: rgb(var(--muted));
}
</style>

<div class="flex gap-4 h-[calc(100vh-4rem-env(safe-area-inset-bottom))]">

    <!-- ── Left: Products Panel ──────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 rounded-2xl overflow-hidden"
         style="background: rgb(var(--card)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-card);">

        <!-- Search / Filters -->
        <div class="p-4 shrink-0" style="border-bottom: 1px solid rgb(var(--border));">
            <div class="flex items-center gap-2.5">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                       style="color: rgb(var(--muted-foreground));"></i>
                    <input id="pos-search" type="search"
                           placeholder="البحث بالاسم أو الرمز (SKU)…"
                           autocomplete="off"
                           class="w-full rounded-xl border px-4 py-2.5 pe-10 text-sm outline-none transition-all font-medium"
                           style="background: rgb(var(--muted)); border-color: rgb(var(--border)); color: rgb(var(--foreground));"
                           onfocus="this.style.borderColor='rgb(var(--primary))'; this.style.boxShadow='0 0 0 4px rgb(var(--primary)/.09)'; this.style.background='rgb(var(--card))'"
                           onblur="this.style.borderColor=''; this.style.boxShadow=''; this.style.background='rgb(var(--muted))'">
                </div>
                <button type="button" id="pos-barcode-btn"
                        class="w-11 h-11 flex items-center justify-center rounded-xl border-2 transition-all shrink-0"
                        style="border-color: rgb(var(--border)); color: rgb(var(--muted-foreground)); background: rgb(var(--muted));"
                        onmouseover="this.style.borderColor='rgb(var(--primary))'; this.style.color='rgb(var(--primary))'"
                        onmouseout="this.style.borderColor='rgb(var(--border))'; this.style.color='rgb(var(--muted-foreground))'"
                        title="مسح الباركود" aria-label="مسح الباركود">
                    <i class="fa-solid fa-barcode text-base" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="pos-products"
             class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 content-start">
            <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="rounded-2xl animate-pulse h-24 skeleton"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ── Right: Cart / Checkout Panel ────────────────────────────── -->
    <div class="w-[340px] xl:w-[375px] flex flex-col rounded-2xl overflow-hidden shrink-0"
         style="background: rgb(var(--card)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-card);">

        <!-- Cart Header -->
        <div class="px-5 py-4 shrink-0" style="border-bottom: 1px solid rgb(var(--border));">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-sm" style="color: rgb(var(--foreground));">سلة المشتريات</h2>
                    <p id="pos-count-label" class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">لا يوجد منتجات بعد</p>
                </div>
                <button type="button" id="pos-clear"
                        class="h-8 px-3 rounded-xl text-xs font-bold transition-all hidden"
                        style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));"
                        onmouseover="this.style.background='rgb(254 205 205)'"
                        onmouseout="this.style.background='rgb(var(--color-danger-light))'">
                    <i class="fa-solid fa-trash-can me-1.5" aria-hidden="true"></i>تفريغ
                </button>
            </div>
        </div>

        <!-- Customer & Payment -->
        <div class="px-4 pt-3.5 pb-3 shrink-0" style="border-bottom: 1px solid rgb(var(--border));">
            <input id="pos-customer" type="text" placeholder="اسم العميل (اختياري)"
                   class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none mb-3 transition-all font-medium"
                   style="background: rgb(var(--muted)); border-color: rgb(var(--border)); color: rgb(var(--foreground));"
                   onfocus="this.style.borderColor='rgb(var(--primary))'; this.style.boxShadow='0 0 0 4px rgb(var(--primary)/.09)'; this.style.background='rgb(var(--card))'"
                   onblur="this.style.borderColor=''; this.style.boxShadow=''; this.style.background='rgb(var(--muted))'">

            <!-- Payment Method -->
            <div class="flex gap-2">
                <?php foreach (['cash' => ['fa-money-bill-wave', 'نقدي'], 'card' => ['fa-credit-card', 'بطاقة'], 'mixed' => ['fa-circle-half-stroke', 'مختلط']] as $val => [$icon, $label]): ?>
                <label class="pos-pay-label" data-value="<?= $val ?>">
                    <input type="radio" name="pos-payment" value="<?= $val ?>" class="sr-only" <?= $val === 'cash' ? 'checked' : '' ?>>
                    <i class="fa-solid <?= $icon ?> text-base" aria-hidden="true"></i>
                    <span class="text-[10.5px] font-bold"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2 min-h-0">
            <!-- Empty state -->
            <div id="pos-cart-empty" class="flex flex-col items-center justify-center h-full py-10 text-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3"
                     style="background: rgb(var(--muted)); border: 1.5px solid rgb(var(--border));">
                    <i class="fa-solid fa-cart-shopping text-xl" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-semibold" style="color: rgb(var(--foreground));">السلة فارغة</p>
                <p class="text-xs mt-1" style="color: rgb(var(--muted-foreground));">أضف منتجات من القائمة</p>
            </div>
            <ul id="pos-cart" class="space-y-2"></ul>
        </div>

        <!-- Footer: Total + Checkout -->
        <div class="p-4 shrink-0" style="border-top: 1px solid rgb(var(--border)); background: rgb(var(--muted) / 0.4);">
            <div class="flex justify-between items-baseline mb-4">
                <span class="text-sm font-semibold" style="color: rgb(var(--muted-foreground));">الإجمالي الكلي</span>
                <span id="pos-total" class="text-2xl font-extrabold stat-value" style="color: rgb(var(--foreground));">
                    <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0
                </span>
            </div>
            <button type="button" id="pos-complete" disabled
                    class="w-full min-h-[50px] rounded-xl text-sm font-bold text-white transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-400 disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background: rgb(var(--color-success)); box-shadow: var(--shadow-success);"
                    onmouseover="if(!this.disabled){this.style.background='rgb(5 150 105)'; this.style.transform='translateY(-1px)'}"
                    onmouseout="if(!this.disabled){this.style.background='rgb(var(--color-success))'; this.style.transform=''}">
                <i class="fa-solid fa-check-circle me-2" aria-hidden="true"></i>
                إتمام البيع
            </button>
        </div>
    </div>
</div>

<!-- ─── Success Modal ─────────────────────────────────────────────────── -->
<div id="pos-success-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 backdrop-blur-md hidden"
     role="dialog" aria-modal="true" aria-label="نجاح العملية">
    <div class="rounded-3xl w-full max-w-sm mx-4 text-center p-8 animate-scale-in"
         style="background: rgb(var(--card)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-xl);">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5"
             style="background: rgb(var(--color-success-light));">
            <i class="fa-solid fa-check text-3xl" style="color: rgb(var(--color-success));" aria-hidden="true"></i>
        </div>
        <h3 class="text-lg font-extrabold mb-1" style="color: rgb(var(--foreground));">تم البيع بنجاح</h3>
        <p id="pos-success-inv" class="text-sm font-medium mb-6" style="color: rgb(var(--muted-foreground));">رقم الفاتورة: —</p>
        <div class="flex gap-3">
            <button type="button" id="pos-new-sale"
                    class="flex-1 min-h-[44px] rounded-xl text-sm font-bold transition-all btn-secondary focus:outline-none">
                <i class="fa-solid fa-plus me-1.5" aria-hidden="true"></i>
                بيع جديد
            </button>
            <a id="pos-print-btn" href="#" target="_blank"
               class="flex-1 min-h-[44px] flex items-center justify-center rounded-xl text-sm font-bold text-white transition-all btn-primary focus:outline-none">
                <i class="fa-solid fa-print me-1.5" aria-hidden="true"></i>طباعة
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = '<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>';
    const currency  = '<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>';
    const BASE      = (document.querySelector('meta[name="app-base"]') || {}).content || '';
    let products = [];
    let cart     = [];
    let lastInvoiceId = null;

    /* ── Payment labels ─────────────────────────────────────── */
    function updatePayLabels() {
        document.querySelectorAll('.pos-pay-label').forEach(lbl => {
            const radio  = lbl.querySelector('input[type=radio]');
            const active = radio.checked;
            lbl.style.borderColor  = active ? 'rgb(var(--primary))' : '';
            lbl.style.color        = active ? 'rgb(var(--primary))' : '';
            lbl.style.background   = active ? 'rgb(var(--primary-subtle))' : '';
            lbl.style.fontWeight   = active ? '700' : '';
        });
    }
    document.querySelectorAll('.pos-pay-label').forEach(lbl => {
        lbl.addEventListener('click', () => {
            lbl.querySelector('input[type=radio]').checked = true;
            updatePayLabels();
        });
    });
    updatePayLabels();

    /* ── Load products ──────────────────────────────────────── */
    function loadProducts(q) {
        const url = q ? BASE + '/api/pos/products?q=' + encodeURIComponent(q) : BASE + '/api/pos/products';
        fetch(url)
            .then(r => r.json())
            .then(data => { products = data.products || []; renderProducts(); })
            .catch(() => {
                document.getElementById('pos-products').innerHTML =
                    '<p class="col-span-full text-center py-12 text-sm" style="color:rgb(var(--muted-foreground))">خطأ في تحميل المنتجات</p>';
            });
    }

    function renderProducts() {
        const el = document.getElementById('pos-products');
        if (!products.length) {
            el.innerHTML = `<div class="col-span-full flex flex-col items-center justify-center py-14 text-center">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3" style="background:rgb(var(--muted));border:1px solid rgb(var(--border));">
                    <i class="fa-solid fa-box-open text-xl" style="color:rgb(var(--muted-foreground));"></i>
                </div>
                <p class="text-sm font-semibold" style="color:rgb(var(--muted-foreground))">لا توجد منتجات مطابقة</p>
            </div>`;
            return;
        }
        el.innerHTML = products.map(p => {
            const out  = p.quantity <= 0;
            const low  = !out && p.low_stock_threshold > 0 && p.quantity <= p.low_stock_threshold;
            const cartItem = cart.find(c => c.product_id === p.id);
            const inCart = cartItem ? cartItem.quantity : 0;
            const stockBadge = out
                ? `<span class="badge badge-danger mt-auto">نفد</span>`
                : low
                    ? `<span class="badge badge-warning mt-auto">منخفض · ${p.quantity}</span>`
                    : `<span class="badge badge-neutral mt-auto">${p.quantity}</span>`;
            const cartBadge = inCart > 0
                ? `<span class="absolute top-2 left-2 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white" style="background:rgb(var(--primary))">${inCart}</span>`
                : '';
            return `<button type="button" class="pos-product-card" ${out ? 'disabled' : ''}
                data-id="${p.id}"
                data-name="${(p.name || '').replace(/"/g, '&quot;')}"
                data-price="${parseFloat(p.price)}"
                data-qty="${parseInt(p.quantity, 10)}"
                data-threshold="${parseInt(p.low_stock_threshold || 0, 10)}">
                ${cartBadge}
                <p class="text-xs font-bold mb-1.5 leading-snug" style="color:rgb(var(--foreground))">${p.name || ''}</p>
                <p class="text-sm font-extrabold mb-2 stat-value" style="color:rgb(var(--primary))">${currency} ${Number(p.price).toLocaleString()}</p>
                ${stockBadge}
            </button>`;
        }).join('');
        el.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                addToCart(+btn.dataset.id, btn.dataset.name, +btn.dataset.price, +btn.dataset.qty);
            });
        });
    }

    /* ── Cart logic ─────────────────────────────────────────── */
    function addToCart(id, name, price, maxQty) {
        const ex = cart.find(x => x.product_id === id);
        if (ex) {
            if (ex.quantity >= maxQty) { showToast('وصلت للحد الأقصى من المخزون', 'warning'); return; }
            ex.quantity++;
            ex.total = ex.quantity * ex.unit_price;
        } else {
            cart.push({ product_id: id, name, quantity: 1, unit_price: price, total: price, maxQty });
        }
        renderCart();
        renderProducts();
    }

    function renderCart() {
        const list     = document.getElementById('pos-cart');
        const empty    = document.getElementById('pos-cart-empty');
        const totalEl  = document.getElementById('pos-total');
        const btn      = document.getElementById('pos-complete');
        const clearBtn = document.getElementById('pos-clear');
        const countLbl = document.getElementById('pos-count-label');

        if (!cart.length) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            totalEl.textContent = `${currency} 0`;
            btn.disabled = true;
            clearBtn.classList.add('hidden');
            countLbl.textContent = 'لا يوجد منتجات بعد';
            return;
        }
        empty.classList.add('hidden');
        clearBtn.classList.remove('hidden');
        const total     = cart.reduce((s, i) => s + i.total, 0);
        const itemCount = cart.reduce((s, i) => s + i.quantity, 0);
        countLbl.textContent = `${itemCount} قطعة · ${cart.length} منتج`;
        totalEl.textContent  = `${currency} ${Number(total).toLocaleString()}`;
        btn.disabled = false;

        list.innerHTML = cart.map((item, i) => `
        <li class="flex items-center gap-2 p-3 rounded-xl" style="background:rgb(var(--muted)); border:1px solid rgb(var(--border))">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate" style="color:rgb(var(--foreground))">${item.name}</p>
                <p class="text-xs font-semibold mt-0.5 stat-value" style="color:rgb(var(--primary))">${currency} ${Number(item.unit_price).toLocaleString()}</p>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button class="pos-qty-btn" data-action="dec" data-index="${i}" aria-label="تقليل">−</button>
                <span class="w-6 text-center text-xs font-extrabold" style="color:rgb(var(--foreground))">${item.quantity}</span>
                <button class="pos-qty-btn" data-action="inc" data-index="${i}" aria-label="زيادة">+</button>
            </div>
            <span class="text-xs font-extrabold w-16 text-end stat-value shrink-0" style="color:rgb(var(--foreground))">${currency} ${Number(item.total).toLocaleString()}</span>
            <button class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors shrink-0"
                    style="background:rgb(var(--color-danger-light)); color:rgb(var(--color-danger))"
                    onmouseover="this.style.background='rgb(254 205 205)'"
                    onmouseout="this.style.background='rgb(var(--color-danger-light))'"
                    data-action="remove" data-index="${i}" aria-label="إزالة">
                <i class="fa-solid fa-xmark text-[10px]" aria-hidden="true"></i>
            </button>
        </li>`).join('');

        list.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx    = +btn.dataset.index;
                const action = btn.dataset.action;
                if (action === 'remove') {
                    cart.splice(idx, 1);
                } else if (action === 'dec') {
                    if (cart[idx].quantity > 1) { cart[idx].quantity--; cart[idx].total = cart[idx].quantity * cart[idx].unit_price; }
                    else cart.splice(idx, 1);
                } else if (action === 'inc') {
                    if (cart[idx].quantity < (cart[idx].maxQty || 9999)) {
                        cart[idx].quantity++;
                        cart[idx].total = cart[idx].quantity * cart[idx].unit_price;
                    } else { showToast('وصلت للحد الأقصى من المخزون', 'warning'); }
                }
                renderCart();
                renderProducts();
            });
        });
    }

    /* ── Clear ──────────────────────────────────────────────── */
    document.getElementById('pos-clear').addEventListener('click', () => {
        cart = [];
        renderCart();
        renderProducts();
    });

    /* ── Search ─────────────────────────────────────────────── */
    document.getElementById('pos-search').addEventListener('input', function () {
        clearTimeout(this._t);
        this._t = setTimeout(() => loadProducts(this.value.trim()), 220);
    });

    /* ── Complete sale ──────────────────────────────────────── */
    document.getElementById('pos-complete').addEventListener('click', function () {
        if (!cart.length) return;
        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>جاري المعالجة…';
        fetch(BASE + '/api/pos/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: csrfToken,
                customer_name: document.getElementById('pos-customer').value.trim() || 'Walk-in Customer',
                payment_method: document.querySelector('input[name="pos-payment"]:checked')?.value || 'cash',
                items: cart.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                lastInvoiceId = data.sale_id || null;
                document.getElementById('pos-success-inv').textContent = 'رقم الفاتورة: ' + (data.invoice_number || '—');
                const printBtn = document.getElementById('pos-print-btn');
                if (lastInvoiceId) printBtn.href = BASE + '/sales/invoice?id=' + lastInvoiceId;
                else printBtn.classList.add('hidden');
                document.getElementById('pos-success-modal').classList.remove('hidden');
                cart = [];
                renderCart();
                renderProducts();
            } else {
                showToast(data.error || 'فشل إتمام البيع', 'error');
            }
        })
        .catch(() => showToast('خطأ في الاتصال بالخادم', 'error'))
        .finally(() => {
            const b = document.getElementById('pos-complete');
            if (b) {
                b.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i>إتمام البيع';
                b.disabled = !cart.length;
            }
        });
    });

    /* ── Success modal ──────────────────────────────────────── */
    document.getElementById('pos-new-sale').addEventListener('click', () => {
        document.getElementById('pos-success-modal').classList.add('hidden');
        document.getElementById('pos-customer').value = '';
        loadProducts('');
    });

    /* ── Toast ──────────────────────────────────────────────── */
    function showToast(msg, type = 'info') {
        const t = document.createElement('div');
        t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-2xl shadow-lg text-white text-sm font-bold';
        const colors = {
            success: 'rgb(16 185 129)',
            error:   'rgb(239 68 68)',
            warning: 'rgb(245 158 11)',
            info:    'rgb(79 70 229)'
        };
        t.style.background = colors[type] || colors.info;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => {
            t.style.transition = 'opacity .25s';
            t.style.opacity = '0';
            setTimeout(() => t.remove(), 260);
        }, 2800);
    }

    /* ── Barcode ────────────────────────────────────────────── */
    document.getElementById('pos-barcode-btn').addEventListener('click', () => {
        showToast('وجّه قارئ الباركود نحو المنتج', 'info');
    });

    /* ── Boot ───────────────────────────────────────────────── */
    loadProducts();
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
