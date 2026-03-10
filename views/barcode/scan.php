<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title ?? 'مسح الباركود') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Tajawal', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col antialiased">
    <header class="flex items-center justify-between px-4 py-3 bg-slate-800 shrink-0">
        <a href="/products" class="flex items-center gap-2 text-blue-400 hover:text-blue-300 text-sm font-medium">
            <i class="fa-solid fa-arrow-right"></i> العودة للمنتجات
        </a>
        <h1 class="text-base font-bold">مسح وإرسال للحاسوب</h1>
    </header>

    <main class="flex-1 flex flex-col p-4">
        <p class="text-sm text-slate-400 text-center mb-4">سجّل الدخول بنفس الحساب على الجوال والحاسوب، ثم امسح الباركود — سيظهر على الحاسوب تلقائياً.</p>

        <div class="relative flex-1 min-h-[240px] bg-black rounded-2xl overflow-hidden">
            <video id="cam-video" class="w-full h-full object-cover" playsinline muted autoplay></video>
            <div id="unsupported" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-slate-900/95 text-center p-4 text-sm gap-3">
                <i class="fa-solid fa-video-slash text-4xl text-slate-500"></i>
                <span>المسح بالكاميرا يعمل على أندرويد (Chrome). تأكد من السماح للكاميرا.</span>
            </div>
        </div>
        <p id="status" class="mt-3 text-sm text-slate-400 text-center">جاري تشغيل الكاميرا...</p>

        <div id="result-box" class="hidden mt-4 rounded-xl p-4 text-center text-sm font-medium"></div>

        <!-- إدخال يدوي عندما الكاميرا غير مدعومة (مثل iOS) أو كبديل -->
        <div id="manual-box" class="hidden mt-4 p-4 rounded-xl bg-slate-800 border border-slate-600">
            <p class="text-sm text-slate-300 mb-2">أدخل الرمز يدوياً أو الصق من مسح سابق:</p>
            <div class="flex gap-2">
                <input type="text" id="manual-barcode" placeholder="الرمز / الباركود" class="flex-1 rounded-lg bg-slate-700 border border-slate-600 text-white px-4 py-2.5 text-base" inputmode="numeric" autocomplete="off">
                <button type="button" id="btn-send-manual" class="px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium shrink-0">إرسال للحاسوب</button>
            </div>
        </div>
    </main>

    <script>
    (function() {
        var video = document.getElementById('cam-video');
        var status = document.getElementById('status');
        var unsupported = document.getElementById('unsupported');
        var resultBox = document.getElementById('result-box');
        var manualBox = document.getElementById('manual-box');
        var manualInput = document.getElementById('manual-barcode');
        var btnSendManual = document.getElementById('btn-send-manual');
        var stream = null;

        function stopCam() {
            if (stream) { stream.getTracks().forEach(function(t) { t.stop(); }); stream = null; }
        }

        function showResult(success, msg) {
            resultBox.className = 'mt-4 rounded-xl p-4 text-center text-sm font-medium ' + (success ? 'bg-green-900/80 text-green-200' : 'bg-red-900/80 text-red-200');
            resultBox.textContent = msg;
            resultBox.classList.remove('hidden');
            setTimeout(function() { resultBox.classList.add('hidden'); }, 4000);
        }

        function sendToPc(barcode) {
            if (!barcode || String(barcode).trim() === '') return;
            barcode = String(barcode).trim();
            status.textContent = 'جاري الإرسال...';
            fetch('/api/barcode-push', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ barcode: barcode })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    showResult(true, 'تم إرسال الرمز إلى الحاسوب: ' + barcode);
                    status.textContent = 'وجّه الكاميرا نحو باركود آخر';
                    if (manualInput) manualInput.value = '';
                } else {
                    showResult(false, data.error || 'فشل الإرسال');
                    status.textContent = 'وجّه الكاميرا نحو الباركود';
                }
            }).catch(function() {
                showResult(false, 'تحقق من الاتصال (نفس الشبكة) وتسجيل الدخول بنفس الحساب على الجوال والحاسوب.');
                status.textContent = 'وجّه الكاميرا نحو الباركود';
            });
        }

        if (btnSendManual && manualInput) {
            btnSendManual.addEventListener('click', function() { sendToPc(manualInput.value); });
            manualInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); sendToPc(manualInput.value); } });
        }

        if (!('BarcodeDetector' in window)) {
            unsupported.classList.remove('hidden');
            unsupported.querySelector('span').textContent = 'المسح بالكاميرا يعمل على أندرويد (Chrome). أدخل الرمز يدوياً أدناه أو استخدم قارئ USB على الحاسوب.';
            if (manualBox) { manualBox.classList.remove('hidden'); manualInput && manualInput.focus(); }
            status.textContent = '';
            return;
        }
        if (manualBox) manualBox.classList.remove('hidden');

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(s) {
                stream = s;
                video.srcObject = s;
                video.play();
                status.textContent = 'وجّه الكاميرا نحو الباركود';
                var detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code'] });
                function scan() {
                    if (!stream || video.readyState < 2) { requestAnimationFrame(scan); return; }
                    detector.detect(video).then(function(codes) {
                        if (codes.length > 0 && codes[0].rawValue) {
                            sendToPc(codes[0].rawValue);
                            requestAnimationFrame(scan);
                        } else {
                            requestAnimationFrame(scan);
                        }
                    }).catch(function() { requestAnimationFrame(scan); });
                }
                requestAnimationFrame(scan);
            })
            .catch(function() {
                unsupported.classList.remove('hidden');
                unsupported.querySelector('span').textContent = 'لم يتم الوصول إلى الكاميرا. اسمح للموقع باستخدام الكاميرا في إعدادات المتصفح.';
                status.textContent = '';
            });

        window.addEventListener('beforeunload', stopCam);
    })();
    </script>
</body>
</html>
