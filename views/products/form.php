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
        <!-- الباركود: حقل واحد + مسح بالكاميرا في نفس الصفحة أو قارئ USB -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">الباركود (SKU)</label>
            <div class="flex gap-2">
                <input type="text" id="product-sku" name="sku" placeholder="سيُملأ تلقائياً عند المسح أو اكتب يدوياً"
                    value="<?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="flex-1 rounded-lg border-gray-300 px-4 py-2.5 border focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" id="btn-camera-scan" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-colors shrink-0">
                    <i class="fa-solid fa-camera"></i>
                    <span>مسح بالكاميرا</span>
                </button>
                <button type="button" id="btn-usb-ready" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-medium hover:bg-slate-300 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-colors shrink-0" title="ثم امسح بقارئ USB">
                    <i class="fa-solid fa-barcode"></i>
                    <span>قارئ USB</span>
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500">«مسح بالكاميرا»: يفتح الكاميرا هنا ويُدخل الرمز فوراً. «قارئ USB»: يجهّز الحقل ثم امسح.</p>
        </div>
        <!-- طبقة المسح بالكاميرا (نفس الصفحة) -->
        <div id="barcode-cam-overlay" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/80 p-4">
            <div class="bg-slate-800 rounded-2xl overflow-hidden max-w-lg w-full shadow-2xl">
                <div class="relative aspect-[4/3] bg-black">
                    <video id="barcode-cam-video" class="w-full h-full object-cover" playsinline muted autoplay></video>
                    <div id="barcode-cam-unsupported" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-slate-900/95 text-white text-center p-4 text-sm gap-2">
                        <i class="fa-solid fa-video-slash text-4xl text-slate-400"></i>
                        <span>المسح بالكاميرا متاح على أندرويد (Chrome). استخدم «قارئ USB» أو اكتب الرمز يدوياً.</span>
                    </div>
                </div>
                <p id="barcode-cam-status" class="text-center text-slate-300 text-sm py-3">جاري تشغيل الكاميرا...</p>
                <div class="flex gap-2 p-4 border-t border-slate-700">
                    <button type="button" id="barcode-cam-close" class="flex-1 py-2.5 rounded-xl bg-slate-600 text-white font-medium hover:bg-slate-500">إلغاء</button>
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
    var overlay = document.getElementById('barcode-cam-overlay');
    var video = document.getElementById('barcode-cam-video');
    var statusEl = document.getElementById('barcode-cam-status');
    var unsupportedEl = document.getElementById('barcode-cam-unsupported');
    var btnCamera = document.getElementById('btn-camera-scan');
    var btnUsb = document.getElementById('btn-usb-ready');
    var btnClose = document.getElementById('barcode-cam-close');
    var stream = null;

    function closeCam() {
        if (stream) { stream.getTracks().forEach(function(t) { t.stop(); }); stream = null; }
        if (video && video.srcObject) { video.srcObject = null; }
        if (overlay) overlay.classList.add('hidden');
        if (overlay) overlay.classList.remove('flex');
    }

    function setSkuAndClose(value) {
        if (skuInput && value) {
            skuInput.value = value;
            skuInput.classList.add('ring-2', 'ring-green-500');
            setTimeout(function() { skuInput.classList.remove('ring-2', 'ring-green-500'); }, 1200);
            skuInput.focus();
        }
        closeCam();
    }

    if (btnUsb && skuInput) {
        btnUsb.addEventListener('click', function() {
            skuInput.focus();
            skuInput.select();
        });
    }
    if (skuInput) {
        skuInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); }
        });
    }

    if (btnCamera && overlay) {
        btnCamera.addEventListener('click', function() {
            if (!('BarcodeDetector' in window)) {
                unsupportedEl && unsupportedEl.classList.remove('hidden');
                statusEl && (statusEl.textContent = '');
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                return;
            }
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            unsupportedEl && unsupportedEl.classList.add('hidden');
            statusEl && (statusEl.textContent = 'جاري تشغيل الكاميرا...');
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function(s) {
                stream = s;
                video.srcObject = s;
                video.play();
                statusEl && (statusEl.textContent = 'وجّه الكاميرا نحو الباركود');
                var detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code'] });
                function scan() {
                    if (!stream || video.readyState < 2) { requestAnimationFrame(scan); return; }
                    detector.detect(video).then(function(codes) {
                        if (codes.length > 0 && codes[0].rawValue) {
                            setSkuAndClose(codes[0].rawValue);
                            return;
                        }
                        requestAnimationFrame(scan);
                    }).catch(function() { requestAnimationFrame(scan); });
                }
                requestAnimationFrame(scan);
            }).catch(function() {
                statusEl && (statusEl.textContent = 'لم يتم الوصول للكاميرا. اسمح بالصلاحية في المتصفح.');
            });
        });
    }
    if (btnClose) btnClose.addEventListener('click', closeCam);
    if (overlay) overlay.addEventListener('click', function(e) { if (e.target === overlay) closeCam(); });

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
