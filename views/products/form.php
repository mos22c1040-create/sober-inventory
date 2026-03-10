<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php $appSettings = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : []; $currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع'; ?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="/dashboard" class="hover:text-blue-600 transition-colors">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <a href="/products" class="hover:text-blue-600 transition-colors">المنتجات</a>
    <i class="fa-solid fa-chevron-left text-xs text-gray-400"></i>
    <span class="text-slate-700 font-medium"><?= $product ? 'تعديل منتج' : 'إضافة منتج' ?></span>
</nav>

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-800 mb-6"><?= $product ? 'تعديل المنتج' : 'إضافة منتج' ?></h1>
    <form id="product-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($product): ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الاسم *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">التصنيف</label>
            <select name="category_id" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
                <option value="">— لا يوجد —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($product['category_id']) && (int)$product['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- قسم الباركود: زر واضح + إدخال يدوي أو قارئ USB أو مسح بالجوال -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">الباركود (SKU) <span class="text-gray-400 font-normal">— اختياري</span></label>
            <div class="flex flex-wrap gap-2 items-center">
                <input type="text" id="product-sku" name="sku" placeholder="اكتب الرمز أو امسح بالقارئ"
                    value="<?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="flex-1 min-w-[180px] rounded-lg border-gray-300 px-4 py-2.5 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" id="btn-scan-barcode" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-colors" title="ضع المؤشر في الحقل ثم امسح بقارئ USB">
                    <i class="fa-solid fa-barcode"></i>
                    <span>امسح الباركود</span>
                </button>
                <a href="/barcode-scan" target="_blank" rel="noopener" id="link-mobile-scan" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-colors" title="افتح على الجوال وامسح بالكاميرا — الرمز يظهر هنا تلقائياً">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <span>مسح بالجوال</span>
                </a>
            </div>
            <p class="mt-2 text-xs text-gray-500">قارئ USB: اضغط «امسح الباركود» ثم امسح. الجوال: افتح «مسح بالجوال» على الهاتف وامسح — الرمز يظهر هنا.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">السعر (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</label>
                <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($product['price'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">التكلفة (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</label>
                <input type="number" name="cost" step="0.01" min="0" value="<?= htmlspecialchars($product['cost'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الكمية</label>
                <input type="number" name="quantity" min="0" value="<?= htmlspecialchars($product['quantity'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">تنبيه نقص المخزون عند</label>
                <input type="number" name="low_stock_threshold" min="0" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? '5', ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium"><?= $product ? 'حفظ التعديلات' : 'إضافة' ?></button>
            <a href="/products" class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">إلغاء</a>
        </div>
    </form>
</div>

<script>
(function() {
    var form = document.getElementById('product-form');
    var skuInput = document.getElementById('product-sku');
    var btnScan = document.getElementById('btn-scan-barcode');

    // زر "امسح الباركود": يضع المؤشر في حقل الباركود جاهزاً لقارئ USB
    if (btnScan && skuInput) {
        btnScan.addEventListener('click', function() {
            skuInput.focus();
            skuInput.select();
        });
    }

    // استقبال باركود من الجوال: تلقائي عند فتح /barcode-scan على الهاتف ومسح
    var lastBridge = '';
    function pollBarcodeFromMobile() {
        if (!skuInput) return;
        fetch('/api/barcode-last').then(function(r) { return r.json(); }).then(function(data) {
            if (data && data.barcode && data.barcode !== lastBridge) {
                lastBridge = data.barcode;
                skuInput.value = data.barcode;
                skuInput.focus();
            }
        }).catch(function() {});
    }
    setInterval(pollBarcodeFromMobile, 2000);

    form.onsubmit = async function(e) {
        e.preventDefault();
        var data = new FormData(form);
        var body = {};
        data.forEach(function(v, k) { body[k] = v; });
        if (body.id) body.id = parseInt(body.id, 10);
        body.price = parseFloat(body.price) || 0;
        body.cost = parseFloat(body.cost) || 0;
        body.quantity = parseInt(body.quantity, 10) || 0;
        body.low_stock_threshold = parseInt(body.low_stock_threshold, 10) || 5;
        body.category_id = body.category_id || null;
        var url = body.id ? '/api/products/update' : '/api/products';
        var res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        var json = await res.json();
        if (json.success && json.redirect) window.location.href = json.redirect;
        else alert(json.error || 'حدث خطأ أثناء الحفظ');
    };
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
