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
        <!-- الباركود: مسح مباشر بكاميرا الجوال أو قارئ USB أو يدوي -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">الباركود (SKU)</label>
            <div class="flex gap-2">
                <input type="text" id="product-sku" name="sku"
                    placeholder="سيُملأ تلقائياً عند المسح أو اكتب يدوياً"
                    value="<?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="flex-1 rounded-lg border-gray-300 px-4 py-2.5 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base">
                <button type="button" id="btn-camera-scan"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 active:scale-95 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all shrink-0">
                    <i class="fa-solid fa-camera text-base"></i>
                    <span>مسح</span>
                </button>
            </div>
        </div>

        <!-- طبقة المسح بالكاميرا — تعمل على iOS + Android + سطح المكتب -->
        <div id="barcode-cam-overlay"
             class="fixed inset-0 z-[200] flex items-center justify-center bg-black/85 p-4"
             style="display:none!important">
            <div class="bg-slate-900 rounded-2xl w-full max-w-sm shadow-2xl border border-slate-700 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-700">
                    <span class="text-white font-semibold text-base">مسح الباركود</span>
                    <button type="button" id="barcode-cam-close"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <!-- منطقة الكاميرا — يُدير html5-qrcode هذا العنصر -->
                <div id="qr-reader" class="w-full bg-black" style="min-height:260px"></div>
                <div class="px-4 py-3">
                    <p id="barcode-cam-status" class="text-slate-400 text-sm text-center">جاري تشغيل الكاميرا...</p>
                </div>
            </div>
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
        <!-- وحدة القياس -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">وحدة القياس</label>
            <select name="unit" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500">
                <?php foreach (['قطعة','كيلو','جرام','لتر','مل','علبة','كرتون','صندوق','زجاجة','كيس','متر','طن'] as $u): ?>
                <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= ($product['unit'] ?? 'قطعة') === $u ? 'selected' : '' ?>><?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- وصف المنتج -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">وصف المنتج (اختياري)</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 px-4 py-2 border focus:ring-2 focus:ring-blue-500 resize-none" placeholder="تفاصيل إضافية عن المنتج..."><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="flex gap-3 pt-4">
            <button type="submit" id="submit-btn" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));"><?= $product ? 'حفظ التعديلات' : 'إضافة المنتج' ?></button>
            <a href="/products" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium border flex items-center transition-colors duration-200 cursor-pointer" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">إلغاء</a>
        </div>
    </form>
</div>

<!-- مكتبة المسح المتوافقة مع iOS و Android و سطح المكتب -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    var form      = document.getElementById('product-form');
    var skuInput  = document.getElementById('product-sku');
    var overlay   = document.getElementById('barcode-cam-overlay');
    var statusEl  = document.getElementById('barcode-cam-status');
    var btnCamera = document.getElementById('btn-camera-scan');
    var btnClose  = document.getElementById('barcode-cam-close');
    var scanner   = null;

    /* ---- مساعدات الـ overlay ---- */
    function showOverlay() {
        overlay.style.removeProperty('display');
        overlay.style.display = 'flex';
    }
    function hideOverlay() {
        overlay.style.display = 'none';
    }

    /* ---- تعيين الباركود وإغلاق الكاميرا ---- */
    function setSkuAndClose(value) {
        if (scanner) {
            scanner.stop().catch(function(){});
            scanner = null;
        }
        hideOverlay();
        if (skuInput && value) {
            skuInput.value = value;
            skuInput.classList.add('ring-2', 'ring-green-500');
            setTimeout(function() { skuInput.classList.remove('ring-2', 'ring-green-500'); }, 1500);
            skuInput.focus();
        }
    }

    /* ---- إغلاق بدون نتيجة ---- */
    function closeCam() {
        if (scanner) {
            scanner.stop().catch(function(){});
            scanner = null;
        }
        hideOverlay();
    }

    /* ---- فتح الكاميرا عند الضغط على زر المسح ---- */
    if (btnCamera) {
        btnCamera.addEventListener('click', function() {
            showOverlay();
            statusEl && (statusEl.textContent = 'جاري تشغيل الكاميرا...');

            /* إعادة تهيئة حاوية الكاميرا لتجنب أخطاء إعادة التشغيل */
            var container = document.getElementById('qr-reader');
            container.innerHTML = '';

            scanner = new Html5Qrcode('qr-reader', { verbose: false });

            var config = {
                fps: 15,
                qrbox: { width: 260, height: 160 },
                aspectRatio: 1.5,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            };

            scanner.start(
                { facingMode: 'environment' },
                config,
                function(decodedText) {           /* نجاح */
                    setSkuAndClose(decodedText);
                },
                function() {}                      /* تجاهل أخطاء الإطارات العادية */
            ).then(function() {
                statusEl && (statusEl.textContent = 'وجّه الكاميرا نحو الباركود');
            }).catch(function(err) {
                statusEl && (statusEl.textContent = 'تعذّر فتح الكاميرا. تأكد من منح الإذن في المتصفح.');
                console.warn('[barcode]', err);
            });
        });
    }

    if (btnClose)  btnClose.addEventListener('click', closeCam);
    if (overlay)   overlay.addEventListener('click', function(e) { if (e.target === overlay) closeCam(); });

    /* ---- منع قارئ USB من إرسال النموذج بـ Enter ---- */
    if (skuInput) {
        skuInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); }
        });
    }

    /* ---- حفظ النموذج ---- */
    form.onsubmit = async function(e) {
        e.preventDefault();
        var data = new FormData(form);
        var body = {};
        data.forEach(function(v, k) { body[k] = v; });
        if (body.id) body.id = parseInt(body.id, 10);
        body.price = parseFloat(body.price) || 0;
        body.cost  = parseFloat(body.cost)  || 0;
        body.quantity = parseInt(body.quantity, 10) || 0;
        body.low_stock_threshold = parseInt(body.low_stock_threshold, 10) || 5;
        body.category_id = body.category_id || null;
        body.unit = body.unit || 'قطعة';
        body.description = body.description || null;
        var url = body.id ? '/api/products/update' : '/api/products';
        var res  = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        var json = await res.json();
        if (json.success && json.redirect) window.location.href = json.redirect;
        else alert(json.error || 'حدث خطأ أثناء الحفظ');
    };
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
