<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
?>

<style>
/* POS-specific overrides (inside main wrapper) */
.pos-product-card {
    display: flex; flex-direction: column;
    background: rgb(var(--card)); border: 1.5px solid rgb(var(--border));
    border-radius: 14px; padding: 14px 12px 12px;
    cursor: pointer; user-select: none;
    transition: border-color 0.18s, box-shadow 0.18s, transform 0.14s;
    position: relative; overflow: hidden;
}
.pos-product-card:hover:not(:disabled) {
    border-color: rgb(var(--primary));
    box-shadow: 0 0 0 3px rgb(var(--primary) / 0.08), 0 4px 16px rgb(0 0 0 / 0.07);
    transform: translateY(-2px);
}
.pos-product-card:active:not(:disabled) { transform: translateY(0); }
.pos-product-card:disabled { opacity: 0.45; cursor: not-allowed; }
.pos-product-card .add-ripple {
    position: absolute; inset: 0; border-radius: 14px;
    background: rgb(var(--primary) / 0.08);
    opacity: 0; pointer-events: none; transition: opacity 0.25s;
}
.pos-product-card:active:not(:disabled) .add-ripple { opacity: 1; }
.pos-qty-btn {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid rgb(var(--border));
    background: rgb(var(--muted)); color: rgb(var(--foreground));
    cursor: pointer; transition: background 0.15s;
    font-size: 15px; line-height: 1;
}
.pos-qty-btn:hover { background: rgb(var(--border)); }
</style>

<div class="flex gap-5 h-[calc(100vh-4rem-env(safe-area-inset-bottom))]">

    <!-- ── Left: Products panel ───────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 bg-white rounded-2xl border overflow-hidden"
         style="border-color: rgb(var(--border)); box-shadow: 0 2px 12px rgb(0 0 0 / 0.04);">

        <!-- Search / filters bar -->
        <div class="p-4 border-b flex items-center gap-3 shrink-0" style="border-color: rgb(var(--border));">
            <div class="relative flex-1">
                <i class="fa-solid fa-search absolute right-3 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                   style="color: rgb(var(--muted-foreground));"></i>
                <input id="pos-search" type="search" placeholder="البحث بالاسم أو الرمز (SKU)…" autocomplete="off"
                       class="w-full rounded-xl border px-4 py-2.5 pe-10 text-sm outline-none transition-all"
                       style="background: rgb(var(--muted)); border-color: rgb(var(--border)); color: rgb(var(--foreground));"
                       onfocus="this.style.borderColor='rgb(var(--primary))'; this.style.boxShadow='0 0 0 3px rgb(var(--primary)/.12)'"
                       onblur="this.style.borderColor=''; this.style.boxShadow=''">
            </div>
            <button type="button" id="pos-barcode-btn"
                    class="w-11 h-11 flex items-center justify-center rounded-xl border transition-colors cursor-pointer"
                    style="border-color: rgb(var(--border)); color: rgb(var(--muted-foreground));"
                    title="مسح الباركود بالكاميرا">
                <i class="fa-solid fa-barcode" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Products grid -->
        <div id="pos-products"
             class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 content-start">
            <!-- Skeleton placeholders -->
            <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="rounded-2xl border animate-pulse h-24" style="background: rgb(var(--muted)); border-color: rgb(var(--border));"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ── Right: Cart / Checkout panel ──────────────────────────────── -->
    <div class="w-[340px] xl:w-[380px] flex flex-col rounded-2xl border overflow-hidden shrink-0"
         style="background: rgb(var(--card)); border-color: rgb(var(--border)); box-shadow: 0 2px 12px rgb(0 0 0 / 0.04);">

        <!-- Cart header -->
        <div class="p-4 border-b flex items-center justify-between shrink-0"
             style="border-color: rgb(var(--border));">
            <div>
                <h2 class="font-bold text-base" style="color: rgb(var(--foreground));">سلة المشتريات</h2>
                <p id="pos-count-label" class="text-xs font-medium mt-0.5" style="color: rgb(var(--muted-foreground));">لا يوجد منتجات بعد</p>
            </div>
            <button type="button" id="pos-clear"
                    class="h-8 px-3 rounded-xl text-xs font-bold transition-colors cursor-pointer hidden"
                    style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                <i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>تفريغ
            </button>
        </div>

        <!-- Customer & payment -->
        <div class="px-4 pt-3 pb-2 border-b shrink-0" style="border-color: rgb(var(--border));">
            <input id="pos-customer" type="text" placeholder="اسم العميل (اختياري)"
                   class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none mb-2.5 transition-all"
                   style="background: rgb(var(--muted)); border-color: rgb(var(--border)); color: rgb(var(--foreground));"
                   onfocus="this.style.borderColor='rgb(var(--primary))'; this.style.boxShadow='0 0 0 3px rgb(var(--primary)/.12)'"
                   onblur="this.style.borderColor=''; this.style.boxShadow=''">
            <div class="flex gap-2">
                <?php foreach (['cash' => ['fa-money-bill-wave','نقدي'], 'card' => ['fa-credit-card','بطاقة'], 'mixed' => ['fa-circle-half-stroke','مختلط']] as $val => [$icon, $label]): ?>
                <label class="flex-1 flex flex-col items-center gap-1 p-2 rounded-xl border-2 cursor-pointer transition-all text-center pos-pay-label"
                       data-value="<?= $val ?>"
                       style="border-color: rgb(var(--border)); color: rgb(var(--muted-foreground));">
                    <input type="radio" name="pos-payment" value="<?= $val ?>" class="sr-only" <?= $val === 'cash' ? 'checked' : '' ?>>
                    <i class="fa-solid <?= $icon ?> text-base" aria-hidden="true"></i>
                    <span class="text-[11px] font-bold"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart items -->
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2 min-h-0">
            <!-- Empty state -->
            <div id="pos-cart-empty" class="flex flex-col items-center justify-center h-full py-10 text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                     style="background: rgb(var(--muted));">
                    <i class="fa-solid fa-cart-shopping text-2xl" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-semibold" style="color: rgb(var(--muted-foreground));">السلة فارغة</p>
                <p class="text-xs mt-1" style="color: rgb(var(--muted-foreground));">أضف منتجات من القائمة على اليسار</p>
            </div>
            <ul id="pos-cart" class="space-y-2"></ul>
        </div>

        <!-- Footer: total + checkout -->
        <div class="p-4 border-t shrink-0" style="border-color: rgb(var(--border)); background: rgb(var(--muted) / 0.5);">
            <div class="flex justify-between items-baseline mb-4">
                <span class="text-sm font-semibold" style="color: rgb(var(--muted-foreground));">الإجمالي</span>
                <span id="pos-total" class="text-2xl font-extrabold" style="color: rgb(var(--foreground));">
                    <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> 0
                </span>
            </div>
            <button type="button" id="pos-complete" disabled
                    class="w-full min-h-[52px] rounded-xl text-sm font-bold text-white transition-all duration-200 focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                    style="background: linear-gradient(135deg, rgb(5 150 105), rgb(16 185 129)); box-shadow: 0 4px 14px rgb(5 150 105 / 0.3);">
                <i class="fa-solid fa-check me-2" aria-hidden="true"></i>إتمام البيع
            </button>
        </div>
    </div>
</div>

<!-- Success modal -->
<div id="pos-success-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden"
     role="dialog" aria-modal="true" aria-label="نجاح العملية">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 text-center p-8 animate-slide-up">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5"
             style="background: rgb(var(--color-success-light));">
            <i class="fa-solid fa-check text-4xl" style="color: rgb(var(--color-success));" aria-hidden="true"></i>
        </div>
        <h3 class="text-xl font-extrabold mb-2" style="color: rgb(var(--foreground));">تم البيع بنجاح!</h3>
        <p id="pos-success-inv" class="text-sm font-medium mb-6" style="color: rgb(var(--muted-foreground));">رقم الفاتورة: —</p>
        <div class="flex gap-3">
            <button type="button" id="pos-new-sale"
                    class="flex-1 min-h-[44px] rounded-xl text-sm font-bold transition-colors cursor-pointer"
                    style="background: rgb(var(--muted)); color: rgb(var(--foreground));">
                بيع جديد
            </button>
            <a id="pos-print-btn" href="#" target="_blank"
               class="flex-1 min-h-[44px] flex items-center justify-center rounded-xl text-sm font-bold text-white transition-colors cursor-pointer"
               style="background: rgb(var(--primary));">
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

    /* ── Payment method radio styling ─────────────────────────── */
    function updatePayLabels() {
        document.querySelectorAll('.pos-pay-label').forEach(lbl => {
            const radio  = lbl.querySelector('input[type=radio]');
            const active = radio.checked;
            lbl.style.borderColor = active ? 'rgb(var(--primary))' : 'rgb(var(--border))';
            lbl.style.color       = active ? 'rgb(var(--primary))' : 'rgb(var(--muted-foreground))';
            lbl.style.background  = active ? 'rgb(var(--primary) / 0.07)' : '';
        });
    }
    document.querySelectorAll('.pos-pay-label').forEach(lbl => {
        lbl.addEventListener('click', () => {
            lbl.querySelector('input[type=radio]').checked = true;
            updatePayLabels();
        });
    });
    updatePayLabels();

    /* ── Load products ─────────────────────────────────────────── */
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
            el.innerHTML = '<p class="col-span-full text-center py-12 text-sm" style="color:rgb(var(--muted-foreground))">لا توجد منتجات مطابقة</p>';
            return;
        }
        el.innerHTML = products.map(p => {
            const out  = p.quantity <= 0;
            const low  = !out && p.low_stock_threshold > 0 && p.quantity <= p.low_stock_threshold;
            const cartItem = cart.find(c => c.product_id === p.id);
            const inCart = cartItem ? cartItem.quantity : 0;
            const stockBadge = out
                ? `<span style="background:rgb(var(--color-danger-light));color:rgb(var(--color-danger));" class="badge mt-auto">نفد</span>`
                : low
                    ? `<span class="badge badge-warning mt-auto">منخفض: ${p.quantity}</span>`
                    : `<span class="badge badge-neutral mt-auto">${p.quantity}</span>`;
            const inCartBadge = inCart > 0
                ? `<span class="absolute top-2 left-2 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white" style="background:rgb(var(--primary))">${inCart}</span>`
                : '';
            return `<button type="button" class="pos-product-card" ${out ? 'disabled' : ''} data-id="${p.id}" data-name="${(p.name||'').replace(/"/g,'&quot;')}" data-price="${parseFloat(p.price)}" data-qty="${parseInt(p.quantity,10)}" data-threshold="${parseInt(p.low_stock_threshold||0,10)}">
                <div class="add-ripple"></div>
                ${inCartBadge}
                <p class="text-sm font-bold mb-1 leading-tight" style="color:rgb(var(--foreground))">${p.name||''}</p>
                <p class="text-sm font-extrabold mb-2" style="color:rgb(var(--primary))">${currency} ${Number(p.price).toLocaleString()}</p>
                ${stockBadge}
            </button>`;
        }).join('');
        el.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                addToCart(+btn.dataset.id, btn.dataset.name, +btn.dataset.price, +btn.dataset.qty);
            });
        });
    }

    /* ── Cart logic ────────────────────────────────────────────── */
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
        const list      = document.getElementById('pos-cart');
        const empty     = document.getElementById('pos-cart-empty');
        const totalEl   = document.getElementById('pos-total');
        const btn       = document.getElementById('pos-complete');
        const clearBtn  = document.getElementById('pos-clear');
        const countLbl  = document.getElementById('pos-count-label');

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
        const total = cart.reduce((s, i) => s + i.total, 0);
        const itemCount = cart.reduce((s, i) => s + i.quantity, 0);
        countLbl.textContent = `${itemCount} قطعة · ${cart.length} منتج`;
        totalEl.textContent  = `${currency} ${Number(total).toLocaleString()}`;
        btn.disabled = false;

        list.innerHTML = cart.map((item, i) => `
        <li class="flex items-center gap-2 p-2.5 rounded-xl" style="background:rgb(var(--muted));border:1.5px solid rgb(var(--border))">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate" style="color:rgb(var(--foreground))">${item.name}</p>
                <p class="text-xs font-semibold mt-0.5" style="color:rgb(var(--primary))">${currency} ${Number(item.unit_price).toLocaleString()}</p>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button class="pos-qty-btn" data-action="dec" data-index="${i}" aria-label="تقليل">−</button>
                <span class="w-7 text-center text-sm font-bold" style="color:rgb(var(--foreground))">${item.quantity}</span>
                <button class="pos-qty-btn" data-action="inc" data-index="${i}" aria-label="زيادة">+</button>
            </div>
            <span class="text-xs font-extrabold w-20 text-end" style="color:rgb(var(--foreground))">${currency} ${Number(item.total).toLocaleString()}</span>
            <button class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors cursor-pointer"
                    style="background:rgb(var(--color-danger-light));color:rgb(var(--color-danger))"
                    data-action="remove" data-index="${i}" aria-label="إزالة">
                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
            </button>
        </li>`).join('');

        list.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx  = +btn.dataset.index;
                const action = btn.dataset.action;
                if (action === 'remove') { cart.splice(idx, 1); }
                else if (action === 'dec') {
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

    /* ── Clear ─────────────────────────────────────────────────── */
    document.getElementById('pos-clear').addEventListener('click', () => { cart = []; renderCart(); renderProducts(); });

    /* ── Search ────────────────────────────────────────────────── */
    document.getElementById('pos-search').addEventListener('input', function () {
        clearTimeout(this._t);
        this._t = setTimeout(() => loadProducts(this.value.trim()), 220);
    });

    /* ── Complete sale ─────────────────────────────────────────── */
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
            const btn = document.getElementById('pos-complete');
            btn.innerHTML = '<i class="fa-solid fa-check me-2"></i>إتمام البيع';
            btn.disabled = !cart.length;
        });
    });

    /* ── Success modal ─────────────────────────────────────────── */
    document.getElementById('pos-new-sale').addEventListener('click', () => {
        document.getElementById('pos-success-modal').classList.add('hidden');
        document.getElementById('pos-customer').value = '';
        loadProducts('');
    });

    /* ── Toast ─────────────────────────────────────────────────── */
    function showToast(msg, type = 'info') {
        const t = document.createElement('div');
        t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-bold transition-all';
        const colors = { success: 'rgb(16 185 129)', error: 'rgb(239 68 68)', warning: 'rgb(245 158 11)', info: 'rgb(37 99 235)' };
        t.style.background = colors[type] || colors.info;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2800);
    }

    /* ── Barcode camera (placeholder) ──────────────────────────── */
    document.getElementById('pos-barcode-btn').addEventListener('click', () => {
        showToast('وجّه قارئ الباركود نحو المنتج', 'info');
    });

    /* ── Boot ──────────────────────────────────────────────────── */
    loadProducts();
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
