<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$isAdmin        = ($_SESSION['role'] ?? '') === 'admin';
$bp             = $basePathSafe ?? '';
?>

<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-xs font-medium mb-5" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard"
       class="transition-colors hover:opacity-80"
       style="color: rgb(var(--primary));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-[9px]" aria-hidden="true"></i>
    <span style="color: rgb(var(--foreground));">المنتجات</span>
</nav>

<!-- Page Header -->
<div class="flex flex-wrap justify-between items-start gap-4 mb-5">
    <div class="page-header mb-0">
        <div class="flex items-center gap-2.5 mb-1">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgb(var(--primary-subtle)); color: rgb(var(--primary));">
                <i class="fa-solid fa-tags text-sm" aria-hidden="true"></i>
            </div>
            <h1 class="page-title">المنتجات</h1>
        </div>
        <p class="page-subtitle">إدارة كتالوج المنتجات والمخزون</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <!-- Barcode Search -->
        <div class="flex items-center gap-2 rounded-xl px-3.5 py-2.5 border transition-all focus-within:border-primary"
             style="background: rgb(var(--card)); border-color: rgb(var(--border)); box-shadow: var(--shadow-xs);">
            <i class="fa-solid fa-barcode text-base shrink-0" style="color: rgb(var(--muted-foreground));" aria-hidden="true"></i>
            <input type="text" id="barcode-input" placeholder="امسح الباركود أو اكتب الرمز..." autocomplete="off"
                   class="w-48 border-0 bg-transparent text-sm outline-none font-medium"
                   style="color: rgb(var(--foreground));">
            <button type="button" id="barcode-btn"
                    class="w-7 h-7 flex items-center justify-center rounded-lg transition-all shrink-0"
                    style="color: rgb(var(--primary));"
                    title="بحث بالباركود" aria-label="بحث بالباركود">
                <i class="fa-solid fa-magnifying-glass text-sm" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Camera Scan Button -->
        <button type="button" id="camera-scan-btn"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all btn-secondary focus:outline-none"
                title="مسح الباركود من كاميرا الجوال">
            <i class="fa-solid fa-camera text-xs" aria-hidden="true"></i>
            <span>مسح بالكاميرا</span>
        </button>

        <!-- Mobile Link -->
        <a href="<?= $bp ?>/barcode-scan" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all btn-secondary focus:outline-none"
           title="ربط الجوال بالحاسوب">
            <i class="fa-solid fa-mobile-screen text-xs" aria-hidden="true"></i>
            <span class="hidden sm:inline">جوال → حاسوب</span>
        </a>

        <?php if ($isAdmin): ?>
        <a href="<?= $bp ?>/products/create"
           class="inline-flex items-center min-h-[42px] px-5 py-2.5 rounded-xl text-sm font-bold btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2">
            <i class="fa-solid fa-plus me-2 text-xs" aria-hidden="true"></i>
            إضافة منتج
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Barcode Result -->
<div id="barcode-result" class="hidden mb-4 rounded-2xl p-4 border text-sm font-medium" role="alert"></div>

<!-- Stock Filter Tabs -->
<?php
$stockFilter = $stockFilter ?? 'all';
$stockTabs = [
    'all'      => ['label' => 'الكل',       'icon' => 'fa-list-ul'],
    'in_stock' => ['label' => 'في المخزون', 'icon' => 'fa-circle-check'],
    'low'      => ['label' => 'منخفض',      'icon' => 'fa-triangle-exclamation'],
    'out'      => ['label' => 'نفد',        'icon' => 'fa-circle-xmark'],
];
?>
<div class="flex flex-wrap gap-2 mb-5">
    <?php foreach ($stockTabs as $key => $tab): ?>
    <a href="<?= $bp ?>/products?stock=<?= $key ?>"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all focus:outline-none"
       style="<?= $stockFilter === $key
            ? 'background: rgb(var(--primary)); color: white; box-shadow: var(--shadow-primary);'
            : 'background: rgb(var(--card)); color: rgb(var(--muted-foreground)); border: 1.5px solid rgb(var(--border));' ?>">
        <i class="fa-solid <?= $tab['icon'] ?> text-xs" aria-hidden="true"></i>
        <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Products Table -->
<div class="rounded-2xl overflow-hidden"
     style="background: rgb(var(--card)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-card);">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr style="background: rgb(var(--muted)); border-bottom: 1px solid rgb(var(--border));">
                    <th class="px-4 py-3.5 text-center text-[10.5px] font-bold uppercase tracking-wider w-16" style="color: rgb(var(--muted-foreground));">الصورة</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المنتج</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">الرمز (SKU)</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">التصنيف</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">النوع</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">السعر</th>
                    <th class="px-6 py-3.5 text-right text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">المخزون</th>
                    <?php if ($isAdmin): ?>
                    <th class="px-6 py-3.5 text-center text-[10.5px] font-bold uppercase tracking-wider" style="color: rgb(var(--muted-foreground));">إجراءات</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 8 : 7 ?>" class="px-6 py-16">
                        <div class="empty-state">
                            <div class="empty-state-icon mx-auto"><i class="fa-solid fa-box-open"></i></div>
                            <p class="font-semibold text-sm mb-1" style="color: rgb(var(--muted-foreground));">لا توجد منتجات بعد</p>
                            <?php if ($isAdmin): ?>
                            <a href="<?= $bp ?>/products/create"
                               class="inline-flex items-center gap-2 mt-2 text-sm font-bold"
                               style="color: rgb(var(--primary));">
                                <i class="fa-solid fa-plus text-xs"></i> إضافة منتج
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($products as $p):
                    $qty        = (int)$p['quantity'];
                    $thresh     = (int)($p['low_stock_threshold'] ?? 0);
                    $outOfStock = $qty <= 0;
                    $lowStock   = !$outOfStock && $thresh > 0 && $qty <= $thresh;
                ?>
                <tr class="transition-colors duration-150"
                    style="border-bottom: 1px solid rgb(var(--border));"
                    onmouseover="this.style.background='rgb(var(--muted))'"
                    onmouseout="this.style.background=''"
                    data-product-id="<?= (int)$p['id'] ?>"
                    data-sku="<?= htmlspecialchars($p['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <td class="px-4 py-3 text-center align-middle">
                        <?php if (!empty($p['image'])): ?>
                        <img src="<?= $bp ?>/<?= htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-12 h-12 rounded-lg object-cover mx-auto inline-block" style="border: 1px solid rgb(var(--border));">
                        <?php else: ?>
                        <div class="w-12 h-12 rounded-lg mx-auto flex items-center justify-center shrink-0" style="background: rgb(var(--muted)); color: rgb(var(--muted-foreground));">
                            <i class="fa-solid fa-image text-lg"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold" style="color: rgb(var(--foreground));">
                            <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        <?php if (!empty($p['sku'])): ?>
                        <code class="text-xs px-2.5 py-1 rounded-lg font-mono font-semibold"
                              style="background: rgb(var(--muted)); color: rgb(var(--foreground)); border: 1px solid rgb(var(--border));">
                            <?= htmlspecialchars($p['sku'], ENT_QUOTES, 'UTF-8') ?>
                        </code>
                        <?php else: ?>
                        <span class="text-xs" style="color: rgb(var(--border-strong));">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4">
                        <?php if (!empty($p['category_name'])): ?>
                        <span class="badge badge-neutral">
                            <?= htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php else: ?>
                        <span class="text-xs" style="color: rgb(var(--border-strong));">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4">
                        <?php if (!empty($p['type_name'])): ?>
                        <span class="text-xs font-medium" style="color: rgb(var(--muted-foreground));">
                            <?= htmlspecialchars($p['type_name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php else: ?>
                        <span class="text-xs" style="color: rgb(var(--border-strong));">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4">
                        <span class="text-sm font-extrabold stat-value" style="color: rgb(var(--primary));">
                            <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$p['price'], 0) ?>
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        <?php if ($outOfStock): ?>
                        <span class="badge badge-danger">نفد · 0</span>
                        <?php elseif ($lowStock): ?>
                        <span class="badge badge-warning">منخفض · <?= $qty ?> <?= htmlspecialchars($p['unit'] ?? 'قطعة', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                        <span class="badge badge-success"><?= $qty ?> <?= htmlspecialchars($p['unit'] ?? 'قطعة', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>

                    <?php if ($isAdmin): ?>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="<?= $bp ?>/products/edit?id=<?= (int)$p['id'] ?>"
                               class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-xl transition-all"
                               style="background: rgb(var(--primary-subtle)); color: rgb(var(--primary));"
                               onmouseover="this.style.background='rgb(225 225 255)'"
                               onmouseout="this.style.background='rgb(var(--primary-subtle))'">
                                <i class="fa-solid fa-pen-to-square text-[10px]" aria-hidden="true"></i>تعديل
                            </a>
                            <button type="button"
                                    onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES, 'UTF-8') ?>')"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-xl transition-all"
                                    style="background: rgb(var(--color-danger-light)); color: rgb(var(--color-danger));"
                                    onmouseover="this.style.background='rgb(254 205 205)'"
                                    onmouseout="this.style.background='rgb(var(--color-danger-light))'">
                                <i class="fa-solid fa-trash-can text-[10px]" aria-hidden="true"></i>حذف
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

<!-- Pagination -->
<?php
$pageQs = ($stockFilter !== 'all') ? 'stock=' . htmlspecialchars($stockFilter, ENT_QUOTES, 'UTF-8') . '&' : '';
?>
<?php if (($pagination['pages'] ?? 1) > 1): ?>
<div class="flex items-center justify-between mt-5 px-1">
    <p class="text-xs font-medium" style="color: rgb(var(--muted-foreground));">
        عرض <?= number_format(count($products)) ?> من إجمالي <?= number_format($pagination['total']) ?> منتج
    </p>
    <div class="flex items-center gap-1.5">
        <?php if ($pagination['page'] > 1): ?>
        <a href="?<?= $pageQs ?>page=<?= $pagination['page'] - 1 ?>" class="pagination-btn" aria-label="الصفحة السابقة">
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
        </a>
        <?php endif; ?>
        <?php for ($pg = max(1, $pagination['page'] - 2); $pg <= min($pagination['pages'], $pagination['page'] + 2); $pg++): ?>
        <a href="?<?= $pageQs ?>page=<?= $pg ?>"
           class="pagination-btn <?= $pg === $pagination['page'] ? 'active' : '' ?>">
            <?= $pg ?>
        </a>
        <?php endfor; ?>
        <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a href="?<?= $pageQs ?>page=<?= $pagination['page'] + 1 ?>" class="pagination-btn" aria-label="الصفحة التالية">
            <i class="fa-solid fa-chevron-left text-[10px]"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Camera Modal -->
<div id="camera-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-3 sm:p-4 hidden">
    <div class="rounded-3xl w-full max-w-md overflow-hidden"
         style="background: rgb(var(--card)); border: 1px solid rgb(var(--border)); box-shadow: var(--shadow-xl);">
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid rgb(var(--border));">
            <h3 class="text-sm font-bold" style="color: rgb(var(--foreground));">مسح الباركود من الجوال</h3>
            <button type="button" id="camera-close"
                    class="w-9 h-9 flex items-center justify-center rounded-xl transition-all btn-secondary focus:outline-none"
                    aria-label="إغلاق">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="p-4 flex flex-col gap-3">
            <div class="relative w-full min-h-[220px] rounded-2xl overflow-hidden"
                 style="background: rgb(10 10 10);">
                <video id="camera-video" class="w-full h-full object-cover" playsinline muted autoplay></video>
                <div id="camera-unsupported"
                     class="hidden absolute inset-0 flex items-center justify-center bg-black/90 text-white text-center p-4 text-sm rounded-2xl">
                    المسح بالكاميرا يعمل على أندرويد (Chrome). أو اكتب الرمز في مربع البحث.
                </div>
            </div>
            <p id="camera-status" class="text-xs text-center font-medium" style="color: rgb(var(--muted-foreground));">
                جاري تشغيل الكاميرا...
            </p>
        </div>
    </div>
</div>

<script>
var APP_BASE   = window.APP_BASE || '';
var csrfToken  = '<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>';

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
    var barcodeInput   = document.getElementById('barcode-input');
    var barcodeBtn     = document.getElementById('barcode-btn');
    var resultBox      = document.getElementById('barcode-result');

    function showBarcodeResult(success, message, productId) {
        resultBox.className = 'mb-4 rounded-2xl p-4 border text-sm font-medium ' +
            (success
                ? 'alert-success alert'
                : 'alert-danger alert');
        resultBox.innerHTML = message;
        resultBox.classList.remove('hidden');
        if (success && productId) {
            document.querySelectorAll('tr[data-product-id]').forEach(function(tr) {
                tr.style.outline = '';
                if (Number(tr.dataset.productId) === productId) {
                    tr.style.outline = '2px solid rgb(79 70 229)';
                    tr.style.outlineOffset = '-2px';
                    tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }
        setTimeout(function() { resultBox.classList.add('hidden'); }, 5000);
    }

    function doBarcodeSearch(skuFromBridge) {
        var sku = (typeof skuFromBridge === 'string' ? skuFromBridge : (barcodeInput && barcodeInput.value || '')).trim();
        if (!sku) return;
        fetch(APP_BASE + '/api/products/barcode?sku=' + encodeURIComponent(sku))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.product) {
                    var p = data.product;
                    showBarcodeResult(true, 'تم العثور على: ' + (p.name || '') + ' — الرمز: ' + (p.sku || ''), p.id);
                    if (barcodeInput) barcodeInput.value = '';
                } else {
                    showBarcodeResult(false, data.error || 'لم يُعثر على منتج بهذا الرمز.');
                }
            })
            .catch(function() { showBarcodeResult(false, 'تعذّر الاتصال بالخادم.'); });
    }

    if (barcodeInput) barcodeInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doBarcodeSearch(); } });
    if (barcodeBtn)   barcodeBtn.addEventListener('click', function() { doBarcodeSearch(); });

    var _lastBridgeSku = null;
    setInterval(function() {
        fetch(APP_BASE + '/api/barcode-last')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.barcode && data.barcode !== _lastBridgeSku) {
                    _lastBridgeSku = data.barcode;
                    doBarcodeSearch(data.barcode);
                }
            }).catch(function() {});
    }, 3000);

    /* Camera modal */
    var cameraModal      = document.getElementById('camera-modal');
    var cameraClose      = document.getElementById('camera-close');
    var cameraScanBtn    = document.getElementById('camera-scan-btn');
    var cameraVideo      = document.getElementById('camera-video');
    var cameraUnsupported= document.getElementById('camera-unsupported');
    var cameraStatus     = document.getElementById('camera-status');
    var cameraStream     = null;

    function stopCamera() {
        if (cameraStream) { cameraStream.getTracks().forEach(function(t) { t.stop(); }); cameraStream = null; }
    }

    function openCameraModal() {
        cameraModal.classList.remove('hidden');
        cameraUnsupported.classList.add('hidden');
        cameraStatus.textContent = 'جاري تشغيل الكاميرا...';
        if (!('BarcodeDetector' in window)) {
            cameraUnsupported.classList.remove('hidden');
            cameraStatus.textContent = '';
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                cameraStream = stream;
                cameraVideo.srcObject = stream;
                cameraVideo.play();
                cameraStatus.textContent = 'وجّه الكاميرا نحو الباركود';
                var detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code'] });
                function scan() {
                    if (!cameraStream || cameraVideo.readyState < 2) { requestAnimationFrame(scan); return; }
                    detector.detect(cameraVideo).then(function(codes) {
                        if (codes.length > 0 && codes[0].rawValue) {
                            stopCamera();
                            cameraModal.classList.add('hidden');
                            barcodeInput.value = codes[0].rawValue;
                            doBarcodeSearch();
                            return;
                        }
                        requestAnimationFrame(scan);
                    }).catch(function() { requestAnimationFrame(scan); });
                }
                requestAnimationFrame(scan);
            })
            .catch(function() {
                cameraUnsupported.classList.remove('hidden');
                cameraUnsupported.textContent = 'لم يتم الوصول إلى الكاميرا.';
                cameraStatus.textContent = '';
            });
    }

    function closeCameraModal() {
        stopCamera();
        cameraModal.classList.add('hidden');
    }

    if (cameraScanBtn) cameraScanBtn.addEventListener('click', openCameraModal);
    if (cameraClose)   cameraClose.addEventListener('click', closeCameraModal);
    cameraModal.addEventListener('click', function(e) { if (e.target === cameraModal) closeCameraModal(); });
    window.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCameraModal(); });
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
