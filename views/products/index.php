<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <h3 class="text-lg font-bold text-slate-800">جميع المنتجات</h3>
    <div class="flex flex-wrap items-center gap-3">
        <!-- بحث بالباركود (قارئ USB أو إدخال يدوي) -->
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all">
            <i class="fa-solid fa-barcode text-gray-400 text-lg"></i>
            <input type="text" id="barcode-input" placeholder="امسح الباركود أو اكتب الرمز..." autocomplete="off"
                   class="w-56 border-0 bg-transparent py-1 text-sm outline-none placeholder-gray-400">
            <button type="button" id="barcode-btn" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors" title="بحث بالباركود">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
        <button type="button" id="camera-scan-btn" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-medium text-slate-700 transition-colors touch-manipulation" title="مسح الباركود من كاميرا الجوال">
            <i class="fa-solid fa-camera"></i> مسح بالكاميرا <span class="text-xs text-gray-500 hidden sm:inline">(من الجوال)</span>
        </button>
        <a href="/barcode-scan" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-100 hover:bg-blue-200 rounded-xl text-sm font-medium text-blue-800 transition-colors" title="افتح على الجوال (ربط بالكابل/شبكة) وامسح — الرمز يظهر هنا">
            <i class="fa-solid fa-mobile-screen"></i> جوال → حاسوب
        </a>
        <a href="/products/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium shadow-md">
            <i class="fa-solid fa-plus ms-2"></i> إضافة منتج
        </a>
    </div>
</div>

<div id="barcode-result" class="hidden mb-4 rounded-xl p-4 border text-sm font-medium" role="alert"></div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الاسم</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الرمز</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التصنيف</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">السعر</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">المخزون</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($products)): ?>
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد منتجات بعد. <a href="/products/create" class="text-blue-600 hover:underline">أضف منتجاً</a>.</td></tr>
                <?php else: ?>
                <?php foreach ($products as $p): 
                    $lowStock = isset($p['low_stock_threshold']) && $p['quantity'] <= $p['low_stock_threshold'] && $p['low_stock_threshold'] > 0;
                ?>
                <tr class="hover:bg-gray-50 transition-colors" data-product-id="<?= (int)$p['id'] ?>" data-sku="<?= htmlspecialchars($p['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <td class="px-6 py-4 text-sm font-medium text-slate-800"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($p['sku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($p['category_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-800">د.ع <?= number_format((float)$p['price'], 0) ?></td>
                    <td class="px-6 py-4 text-right">
                        <span class="text-sm font-medium <?= $lowStock ? 'text-red-600' : 'text-slate-800' ?>"><?= (int)$p['quantity'] ?></span>
                        <?php if ($lowStock): ?><span class="ms-1 text-xs text-red-500">(منخفض)</span><?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="/products/edit?id=<?= (int)$p['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium ms-3">تعديل</a>
                        <button type="button" onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES, 'UTF-8') ?>')" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="camera-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-2 sm:p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[95vh] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
            <h3 class="text-base font-bold text-slate-800">مسح الباركود من الجوال</h3>
            <button type="button" id="camera-close" class="min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 active:bg-gray-200 touch-manipulation" aria-label="إغلاق"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div class="p-3 sm:p-4 flex-1 min-h-0 flex flex-col">
            <div class="relative w-full flex-1 min-h-[200px] sm:min-h-[280px] bg-slate-900 rounded-xl overflow-hidden">
                <video id="camera-video" class="w-full h-full object-cover" playsinline muted autoplay></video>
                <div id="camera-unsupported" class="hidden absolute inset-0 flex items-center justify-center bg-slate-900/95 text-white text-center p-4 text-sm">المسح بالكاميرا يعمل على أندرويد (Chrome). أو اكتب الرمز في مربع البحث.</div>
            </div>
            <p id="camera-status" class="mt-2 text-sm text-gray-500 text-center shrink-0">جاري تشغيل الكاميرا...</p>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
function deleteProduct(id, name) {
    if (!confirm('Delete product “‘ + name + '”?')) return;
    fetch('/api/products/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, csrf_token: csrfToken })
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Failed to delete');
    });
}
</script>
<script>
(function() {
    var barcodeInput = document.getElementById('barcode-input');
    var barcodeBtn  = document.getElementById('barcode-btn');
    var resultBox   = document.getElementById('barcode-result');
    function showBarcodeResult(success, message, productId) {
        resultBox.className = 'mb-4 rounded-xl p-4 border text-sm font-medium ' + (success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700');
        resultBox.innerHTML = message;
        resultBox.classList.remove('hidden');
        if (success && productId) {
            document.querySelectorAll('tr[data-product-id]').forEach(function(tr) {
                tr.classList.remove('ring-2', 'ring-blue-500', 'ring-inset');
                if (Number(tr.dataset.productId) === productId) { tr.classList.add('ring-2', 'ring-blue-500', 'ring-inset'); tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            });
        }
        setTimeout(function() { resultBox.classList.add('hidden'); }, 5000);
    }
    function doBarcodeSearch(skuFromBridge) {
        var sku = (typeof skuFromBridge === 'string' ? skuFromBridge : (barcodeInput && barcodeInput.value || '')).trim();
        if (!sku) return;
        fetch('/api/products/barcode?sku=' + encodeURIComponent(sku)).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success && data.product) {
                var p = data.product;
                showBarcodeResult(true, 'تم العثور على: ' + (p.name || '') + ' — الرمز: ' + (p.sku || '') + (skuFromBridge ? ' (من الجوال)' : ''), p.id);
                if (barcodeInput) barcodeInput.value = '';
            } else { showBarcodeResult(false, data.error || 'لم يُعثر على منتج بهذا الرمز.'); }
        }).catch(function() { showBarcodeResult(false, 'تعذّر الاتصال بالخادم.'); });
    }
    if (barcodeInput) barcodeInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doBarcodeSearch(); } });
    if (barcodeBtn) barcodeBtn.addEventListener('click', function() { doBarcodeSearch(); });
    setInterval(function() {
        fetch('/api/barcode-last').then(function(r) { return r.json(); }).then(function(data) {
            if (data.barcode) doBarcodeSearch(data.barcode);
        }).catch(function() {});
    }, 600);
    var cameraModal = document.getElementById('camera-modal');
    var cameraClose = document.getElementById('camera-close');
    var cameraScanBtn = document.getElementById('camera-scan-btn');
    var cameraVideo = document.getElementById('camera-video');
    var cameraUnsupported = document.getElementById('camera-unsupported');
    var cameraStatus = document.getElementById('camera-status');
    var cameraStream = null;
    function stopCamera() { if (cameraStream) { cameraStream.getTracks().forEach(function(t) { t.stop(); }); cameraStream = null; } }
    function openCameraModal() {
        cameraModal.classList.remove('hidden'); cameraModal.classList.add('flex');
        cameraUnsupported.classList.add('hidden');
        cameraStatus.textContent = 'جاري تشغيل الكاميرا...';
        if (!('BarcodeDetector' in window)) { cameraUnsupported.classList.remove('hidden'); cameraStatus.textContent = ''; return; }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function(stream) {
            cameraStream = stream; cameraVideo.srcObject = stream; cameraVideo.play();
            cameraStatus.textContent = 'وجّه الكاميرا نحو الباركود';
            var detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code'] });
            function scan() {
                if (!cameraStream || cameraVideo.readyState < 2) { requestAnimationFrame(scan); return; }
                detector.detect(cameraVideo).then(function(codes) {
                    if (codes.length > 0 && codes[0].rawValue) {
                        stopCamera(); cameraModal.classList.add('hidden'); cameraModal.classList.remove('flex');
                        barcodeInput.value = codes[0].rawValue; doBarcodeSearch(); return;
                    }
                    requestAnimationFrame(scan);
                }).catch(function() { requestAnimationFrame(scan); });
            }
            requestAnimationFrame(scan);
        }).catch(function() {
            cameraUnsupported.classList.remove('hidden');
            cameraUnsupported.textContent = 'لم يتم الوصول إلى الكاميرا. تأكد من السماح للموقع باستخدام الكاميرا.';
            cameraStatus.textContent = '';
        });
    }
    function closeCameraModal() { stopCamera(); cameraModal.classList.add('hidden'); cameraModal.classList.remove('flex'); }
    if (cameraScanBtn) cameraScanBtn.addEventListener('click', openCameraModal);
    if (cameraClose) cameraClose.addEventListener('click', closeCameraModal);
    cameraModal.addEventListener('click', function(e) { if (e.target === cameraModal) closeCameraModal(); });
    window.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCameraModal(); });
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
