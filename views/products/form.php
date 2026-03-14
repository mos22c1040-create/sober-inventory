<?php require BASE_PATH . '/views/layouts/header.php'; ?>
<?php require BASE_PATH . '/views/layouts/sidebar.php'; ?>
<?php
$appSettings    = file_exists(BASE_PATH . '/config/app_settings.php') ? (array) include BASE_PATH . '/config/app_settings.php' : [];
$currencySymbol = $appSettings['currency_symbol'] ?? 'د.ع';
$bp             = $basePathSafe ?? '';
?>
<nav class="flex items-center gap-2 text-sm mb-4" style="color: rgb(var(--muted-foreground));" aria-label="مسار التنقل">
    <a href="<?= $bp ?>/dashboard" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">لوحة التحكم</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <a href="<?= $bp ?>/products" class="hover:opacity-80 transition-colors" style="color: rgb(var(--accent));">المنتجات</a>
    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
    <span class="font-medium" style="color: rgb(var(--foreground));"><?= $product ? 'تعديل منتج' : 'إضافة منتج' ?></span>
</nav>

<div class="max-w-2xl">
    <header class="page-header mb-6">
        <h1 class="page-title"><?= $product ? 'تعديل المنتج' : 'إضافة منتج' ?></h1>
        <p class="page-subtitle"><?= $product ? 'تحديث بيانات المنتج' : 'إضافة منتج جديد إلى الكتالوج' ?></p>
    </header>
    <form id="product-form" class="app-card-flat p-6 space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($product): ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><?php endif; ?>

        <!-- صورة المنتج -->
        <div class="rounded-xl p-4 border" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            <label class="block text-sm font-semibold mb-2" style="color: rgb(var(--foreground));">صورة المنتج</label>
            <div class="flex flex-wrap gap-4 items-start">
                <div id="product-image-preview" class="w-36 h-36 rounded-xl border-2 flex items-center justify-center overflow-hidden shrink-0 bg-white" style="border-color: rgb(var(--border));">
                    <?php if (!empty($product['image'])): ?>
                        <img id="product-image-img" src="<?= $bp ?>/<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full h-full object-cover">
                        <span id="product-image-placeholder" class="hidden text-sm font-medium" style="color: rgb(var(--muted-foreground));">لا توجد صورة</span>
                    <?php else: ?>
                        <img id="product-image-img" src="" alt="" class="w-full h-full object-cover hidden">
                        <span id="product-image-placeholder" class="text-sm font-medium" style="color: rgb(var(--muted-foreground));">لا توجد صورة</span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col gap-2">
                    <input type="file" id="product-image-input" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="text-sm file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer" style="color: rgb(var(--foreground));">
                    <input type="hidden" name="image_base64" id="product-image-base64" value="">
                    <div class="flex flex-wrap gap-2 items-center">
                        <button type="button" id="btn-remove-bg" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-colors disabled:opacity-50" style="border-color: rgb(var(--primary)); color: rgb(var(--primary));">
                            <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
                            <span>إزالة الخلفية (خلفية بيضاء) — مجاناً</span>
                        </button>
                        <span id="remove-bg-status" class="text-xs hidden" style="color: rgb(var(--muted-foreground));"></span>
                    </div>
                    <p class="text-xs" style="color: rgb(var(--muted-foreground));">JPEG، PNG، WebP أو GIF. الحد 5 ميجابايت. اختر صورة ثم اضغط الزر أعلاه. إن لم تعمل الأداة، ارفع الصورة كما هي أو استخدم تطبيق الجوال.</p>
                </div>
            </div>
        </div>
        <div id="remove-bg-overlay" class="fixed inset-0 z-[300] flex items-center justify-center bg-black/70" style="display: none;">
            <div class="flex flex-col items-center gap-4 p-6 rounded-2xl max-w-sm shadow-xl" style="background: rgb(var(--card)); border: 1px solid rgb(var(--border));">
                <div class="w-12 h-12 rounded-full border-4 border-t-transparent animate-spin" style="border-color: rgb(var(--primary));"></div>
                <p class="text-sm font-semibold" style="color: rgb(var(--foreground));">جاري إزالة الخلفية...</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">الاسم *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">التصنيف</label>
            <select name="category_id" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                <option value="">— لا يوجد —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($product['category_id']) && (int)$product['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">النوع</label>
            <select name="type_id" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                <option value="">— لا يوجد —</option>
                <?php foreach (($types ?? []) as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (isset($product['type_id']) && (int)$product['type_id'] === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- الباركود: مسح مباشر بكاميرا الجوال أو قارئ USB أو يدوي -->
        <div class="rounded-xl p-4" style="border: 1px solid rgb(var(--border)); background: rgb(var(--muted));">
            <label class="block text-sm font-semibold mb-2" style="color: rgb(var(--foreground));">الباركود (SKU)</label>
            <div class="flex gap-2">
                <input type="text" id="product-sku" name="sku"
                    placeholder="سيُملأ تلقائياً عند المسح أو اكتب يدوياً"
                    value="<?= htmlspecialchars($product['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="app-input flex-1 rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--color-surface-elevated));">
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
                <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">السعر (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</label>
                <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($product['price'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">التكلفة (<?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?>)</label>
                <input type="number" name="cost" step="0.01" min="0" value="<?= htmlspecialchars($product['cost'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">الكمية</label>
                <input type="number" name="quantity" min="0" value="<?= htmlspecialchars($product['quantity'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">تنبيه نقص المخزون عند</label>
                <input type="number" name="low_stock_threshold" min="0" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? '5', ENT_QUOTES, 'UTF-8') ?>" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">وحدة القياس</label>
            <select name="unit" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm" style="border-color: rgb(var(--border)); background: rgb(var(--muted));">
                <?php foreach (['قطعة','كيلو','جرام','لتر','مل','علبة','كرتون','صندوق','زجاجة','كيس','متر','طن'] as $u): ?>
                <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= ($product['unit'] ?? 'قطعة') === $u ? 'selected' : '' ?>><?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5" style="color: rgb(var(--foreground));">وصف المنتج (اختياري)</label>
            <textarea name="description" rows="3" class="app-input w-full rounded-lg px-4 py-2.5 border text-sm resize-none" style="border-color: rgb(var(--border)); background: rgb(var(--muted));" placeholder="تفاصيل إضافية عن المنتج..."><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="flex gap-3 pt-4">
            <button type="submit" id="submit-btn" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-semibold btn-primary focus:ring-2 focus:ring-offset-2 transition-colors duration-200 cursor-pointer" style="background: rgb(var(--primary)); color: rgb(var(--primary-foreground));"><?= $product ? 'حفظ التعديلات' : 'إضافة المنتج' ?></button>
            <a href="<?= $bp ?>/products" class="min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-medium border flex items-center transition-colors duration-200 cursor-pointer" style="border-color: rgb(var(--border)); color: rgb(var(--foreground));">إلغاء</a>
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

    /* ---- معاينة صورة المنتج ---- */
    var imageInput = document.getElementById('product-image-input');
    var imageImg = document.getElementById('product-image-img');
    var imagePlaceholder = document.getElementById('product-image-placeholder');
    var imageBase64Input = document.getElementById('product-image-base64');
    if (imageInput && imageImg && imagePlaceholder) {
        imageInput.addEventListener('change', function() {
            if (imageBase64Input) imageBase64Input.value = '';
            var file = this.files && this.files[0];
            if (file && file.type.startsWith('image/')) {
                var r = new FileReader();
                r.onload = function() {
                    imageImg.src = r.result;
                    imageImg.classList.remove('hidden');
                    imagePlaceholder.classList.add('hidden');
                };
                r.readAsDataURL(file);
            } else {
                imageImg.src = '';
                imageImg.classList.add('hidden');
                imagePlaceholder.classList.remove('hidden');
            }
        });
    }

    /* ---- إزالة الخلفية مجاناً في المتصفح (مكتبة من esm.sh) ---- */
    var btnRemoveBg = document.getElementById('btn-remove-bg');
    var overlayBg = document.getElementById('remove-bg-overlay');
    var statusBg = document.getElementById('remove-bg-status');
    function showOverlay(show) {
        if (overlayBg) overlayBg.style.display = show ? 'flex' : 'none';
    }
    if (btnRemoveBg && imageInput && imageBase64Input && imageImg) {
        btnRemoveBg.addEventListener('click', function() {
            var file = imageInput.files && imageInput.files[0];
            if (!file || !file.type.startsWith('image/')) {
                alert('اختر صورة أولاً');
                return;
            }
            btnRemoveBg.disabled = true;
            showOverlay(true);
            if (statusBg) { statusBg.textContent = ''; statusBg.classList.add('hidden'); }
            var imgUrl = URL.createObjectURL(file);
            function done(err, dataUrl) {
                URL.revokeObjectURL(imgUrl);
                showOverlay(false);
                btnRemoveBg.disabled = false;
                if (err) {
                    if (statusBg) {
                        statusBg.textContent = 'الأداة غير متاحة في هذا المتصفح. ارفع الصورة كما هي أو استخدم تطبيق الجوال.';
                        statusBg.classList.remove('hidden');
                    }
                    return;
                }
                if (statusBg) statusBg.classList.add('hidden');
                if (dataUrl && imageBase64Input) {
                    var b64 = dataUrl.indexOf(',') >= 0 ? dataUrl.split(',')[1] : dataUrl;
                    imageBase64Input.value = b64;
                    imageImg.src = dataUrl;
                    imageImg.classList.remove('hidden');
                    if (imagePlaceholder) imagePlaceholder.classList.add('hidden');
                    imageInput.value = '';
                }
            }
            import('https://esm.sh/@imgly/background-removal@1').then(function(mod) {
                var fn = mod.default || mod;
                return fn(imgUrl);
            }).then(function(blob) {
                var img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function() {
                    var c = document.createElement('canvas');
                    c.width = img.width;
                    c.height = img.height;
                    var ctx = c.getContext('2d');
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, c.width, c.height);
                    ctx.drawImage(img, 0, 0);
                    done(null, c.toDataURL('image/jpeg', 0.92));
                };
                img.onerror = function() { done('تعذر تحميل الصورة'); };
                img.src = URL.createObjectURL(blob);
            }).catch(function() {
                done('فشل التحميل');
            });
        });
    }

    /* ---- منع قارئ USB من إرسال النموذج بـ Enter ---- */
    if (skuInput) {
        skuInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); }
        });
    }

    /* ---- حفظ النموذج (multipart لدعم صورة المنتج) ---- */
    form.onsubmit = async function(e) {
        e.preventDefault();
        var base = (window.APP_BASE || '').replace(/\/$/, '');
        var url = form.querySelector('input[name="id"]') ? base + '/api/products/update' : base + '/api/products';
        var formData = new FormData(form);
        var submitBtn = document.getElementById('submit-btn');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'جاري الحفظ...'; }
        var res = await fetch(url, { method: 'POST', body: formData });
        var json = await res.json();
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = form.querySelector('input[name="id"]') ? 'حفظ التعديلات' : 'إضافة المنتج'; }
        var redirect = json.redirect || (json.data && json.data.redirect);
        if (json.success && redirect) {
            window.location.href = base + (redirect.startsWith('/') ? redirect : '/' + redirect);
        } else {
            var errMsg = json.error || (json.data && json.data.error) || 'حدث خطأ أثناء الحفظ';
            alert(errMsg);
        }
    };
})();
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
