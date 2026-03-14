<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$isAdmin        = ($_SESSION['role'] ?? '') === 'admin';
?>

<?php $bp = $basePathSafe ?? ''; ?>
<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));">المنتجات</span>
</nav>

<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <header class="page-header mb-0">
        <h1 class="page-title">المنتجات</h1>
        <p class="page-subtitle">جميع المنتجات في الكتالوج — بحث بالباركود أو الكاميرا</p>
    </header>
    <div class="flex flex-wrap items-center gap-3">
        <!-- بحث بالباركود (قارئ USB أو إدخال يدوي) -->
        <div class="flex items-center gap-2 rounded-lg px-3 py-2 app-input border transition-all" style="background: rgb(var(--color-surface-elevated)); border-color: rgb(var(--border));">
            <i class="fa-solid fa-barcode text-lg" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
            <input type="text" id="barcode-input" placeholder="امسح الباركود أو اكتب الرمز..." autocomplete="off"
                   class="w-56 border-0 bg-transparent py-1 text-sm outline-none flex-1 min-w-0" style="color: rgb(var(--foreground));">
            <button type="button" id="barcode-btn" class="min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg transition-colors duration-200 cursor-pointer focus:ring-2 focus:ring-offset-2" style="color: rgb(var(--primary));" onfocus="this.style.boxShadow='0 0 0 3px rgb(var(--ring) / 0.2)'" onblur="this.style.boxShadow='none'" title="بحث بالباركود" aria-label="بحث بالباركود">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </button>
        </div>
        <button type="button" id="camera-scan-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 touch-manipulation cursor-pointer border" style="background: rgb(var(--muted)); color: rgb(var(--foreground)); border-color: rgb(var(--border));" title="مسح الباركود من كاميرا الجوال">
            <i class="fa-solid fa-camera" aria-hidden="true"></i> مسح بالكاميرا <span class="text-xs hidden sm:inline" style="color: rgb(var(--muted-foreground));">(من الجوال)</span>
        </button>
        <a href="<?= $bp ?>/barcode-scan" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 border cursor-pointer" style="background: rgb(var(--muted)); color: rgb(var(--primary)); border-color: rgb(var(--border));" title="افتح على الجوال (ربط بالكابل/شبكة) وامسح — الرمز يظهر هنا">
            <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i> جوال → حاسوب
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?= $bp ?>/products/create" class="inline-flex items-center min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium btn-primary focus:ring-2 focus:ring-offset-2 cursor-pointer transition-colors duration-200" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));">
            <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i> إضافة منتج
        </a>
        <?php endif; ?>
    </div>
</div>

<div id="barcode-result" class="hidden mb-4 rounded-xl p-4 border text-sm font-medium" role="alert"></div>

<?php
$stockFilter = $stockFilter ?? 'all';
$stockTabs = [
    'all'       => ['label' => 'الكل', 'icon' => 'fa-list'],
    'in_stock'  => ['label' => 'في المخزون', 'icon' => 'fa-check-circle'],
    'low'       => ['label' => 'منخفض', 'icon' => 'fa-triangle-exclamation'],
    'out'       => ['label' => 'نفد', 'icon' => 'fa-circle-xmark'],
];
?>
<div class="flex flex-wrap gap-2 mb-4">
    <?php foreach ($stockTabs as $key => $tab): ?>
    <a href="<?= $bp ?>/products?stock=<?= $key ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all <?= $stockFilter === $key ? 'text-white shadow-md' : '' ?>"
       style="<?= $stockFilter === $key ? 'background: rgb(var(--primary)); color: rgb(var(--primary-foreground));' : 'background: rgb(var(--muted)); color: rgb(var(--muted-foreground)); border: 1px solid rgb(var(--border));' ?>">
        <i class="fa-solid <?= $tab['icon'] ?>" aria-hidden="true"></i>
        <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="rounded-2xl border overflow-hidden" style="border-color: rgb(var(--border)); background: rgb(var(--card)); box-shadow: 0 2px 12px rgb(0 0 0 / 0.04);">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y" style="border-color: rgb(var(--border));">
            <thead>
                <tr style="background: rgb(var(--muted));">
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المنتج</th>
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الرمز (SKU)</th>
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التصنيف</th>
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">النوع</th>
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">السعر</th>
                    <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المخزون</th>
                    <?php if ($isAdmin): ?><th class="px-6 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th><?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y" style="border-color: rgb(var(--border));">
                <?php if (empty($products)): ?>
                <tr><td colspan="<?= $isAdmin ? 6 : 5 ?>" class="px-6 py-16">
                    <div class="empty-state">
                        <div class="empty-state-icon mx-auto"><i class="fa-solid fa-box-open"></i></div>
                        <p class="font-semibold" style="color: rgb(var(--muted-foreground));">لا توجد منتجات بعد</p>
                        <?php if ($isAdmin): ?><a href="<?= $bp ?>/products/create" class="inline-flex items-center gap-2 mt-3 text-sm font-bold" style="color: rgb(var(--primary));"><i class="fa-solid fa-plus"></i> إضافة منتج</a><?php endif; ?>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php foreach ($products as $p):
                    $qty      = (int)$p['quantity'];
                    $thresh   = (int)($p['low_stock_threshold'] ?? 0);
                    $outOfStock = $qty <= 0;
                    $lowStock   = !$outOfStock && $thresh > 0 && $qty <= $thresh;
                ?>
                <tr class="group transition-colors duration-150 hover:bg-blue-50/30" data-product-id="<?= (int)$p['id'] ?>" data-sku="<?= htmlspecialchars($p['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-semibold" style="color: rgb(var(--foreground));"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="px-6 py-3.5">
                        <?php if (!empty($p['sku'])): ?>
                        <code class="text-xs px-2 py-0.5 rounded-lg font-mono" style="background: rgb(var(--muted)); color: rgb(var(--foreground));"><?= htmlspecialchars($p['sku'], ENT_QUOTES, 'UTF-8') ?></code>
                        <?php else: ?><span class="text-xs" style="color: rgb(var(--muted-foreground));">—</span><?php endif; ?>
                    </td>
                    <td class="px-6 py-3.5">
                        <?php if (!empty($p['category_name'])): ?>
                        <span class="badge badge-neutral"><?= htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?><span class="text-xs" style="color: rgb(var(--muted-foreground));">—</span><?php endif; ?>
                    </td>
                    <td class="px-6 py-3.5">
                        <?php if (!empty($p['type_name'])): ?>
                        <span class="text-xs font-medium" style="color: rgb(var(--muted-foreground));"><?= htmlspecialchars($p['type_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?><span class="text-xs" style="color: rgb(var(--muted-foreground));">—</span><?php endif; ?>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-extrabold" style="color: rgb(var(--primary));"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$p['price'], 0) ?></span>
                    </td>
                    <td class="px-6 py-3.5">
                        <?php if ($outOfStock): ?>
                        <span class="badge" style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">نفد · 0</span>
                        <?php elseif ($lowStock): ?>
                        <span class="badge badge-warning">منخفض · <?= $qty ?> <?= htmlspecialchars($p['unit'] ?? 'قطعة', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                        <span class="badge badge-success"><?= $qty ?> <?= htmlspecialchars($p['unit'] ?? 'قطعة', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <?php if ($isAdmin): ?>
                    <td class="px-6 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="<?= $bp ?>/products/edit?id=<?= (int)$p['id'] ?>"
                               class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors"
                               style="background: rgb(var(--primary) / 0.08); color: rgb(var(--primary));">
                                <i class="fa-solid fa-pen-to-square text-[10px]" aria-hidden="true"></i>تعديل
                            </a>
                            <button type="button"
                                    onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES, 'UTF-8') ?>')"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                                    style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));">
                                <i class="fa-solid fa-trash text-[10px]" aria-hidden="true"></i>حذف
                            </button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pageQs = ($stockFilter !== 'all') ? 'stock=' . htmlspecialchars($stockFilter, ENT_QUOTES, 'UTF-8') . '&' : '';
?>
<?php if (($pagination['pages'] ?? 1) > 1): ?>
<div class="flex items-center justify-between mt-4 px-1">
    <p class="text-sm text-slate-500">
        عرض <?= number_format(count($products)) ?> من إجمالي <?= number_format($pagination['total']) ?> منتج
    </p>
    <div class="flex items-center gap-1">
        <?php if ($pagination['page'] > 1): ?>
        <a href="?<?= $pageQs ?>page=<?= $pagination['page'] - 1 ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-slate-600">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </a>
        <?php endif; ?>
        <?php for ($pg = max(1, $pagination['page'] - 2); $pg <= min($pagination['pages'], $pagination['page'] + 2); $pg++): ?>
        <a href="?<?= $pageQs ?>page=<?= $pg ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors <?= $pg === $pagination['page'] ? 'bg-blue-600 text-white shadow-sm' : 'border border-slate-200 hover:bg-slate-50 text-slate-600' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a href="?<?= $pageQs ?>page=<?= $pagination['page'] + 1 ?>"
           class="px-3 py-1.5 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-slate-600">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

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
var APP_BASE = window.APP_BASE || '';
var csrfToken = '<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
function deleteProduct(id, name) {
    if (!confirm('هل أنت متأكد من حذف المنتج "' + name + '"?\nلا يمكن التراجع عن هذه العملية.')) return;
    fetch(APP_BASE + '/api/products/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, csrf_token: csrfToken })
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'حدث خطأ أثناء الحذف');
    }).catch(function() { alert('خطأ في الاتصال بالخادم'); });
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
        fetch(APP_BASE + '/api/products/barcode?sku=' + encodeURIComponent(sku)).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success && data.product) {
                var p = data.product;
                showBarcodeResult(true, 'تم العثور على: ' + (p.name || '') + ' — الرمز: ' + (p.sku || '') + (skuFromBridge ? ' (من الجوال)' : ''), p.id);
                if (barcodeInput) barcodeInput.value = '';
            } else { showBarcodeResult(false, data.error || 'لم يُعثر على منتج بهذا الرمز.'); }
        }).catch(function() { showBarcodeResult(false, 'تعذّر الاتصال بالخادم.'); });
    }
    if (barcodeInput) barcodeInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doBarcodeSearch(); } });
    if (barcodeBtn) barcodeBtn.addEventListener('click', function() { doBarcodeSearch(); });
    var _lastBridgeSku = null;
    setInterval(function() {
        fetch(APP_BASE + '/api/barcode-last').then(function(r) { return r.json(); }).then(function(data) {
            if (data && data.barcode && data.barcode !== _lastBridgeSku) {
                _lastBridgeSku = data.barcode;
                doBarcodeSearch(data.barcode);
            }
        }).catch(function() {});
    }, 3000);
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
