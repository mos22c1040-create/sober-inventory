# حفظ صور المنتجات على Railway (عدم فقدانها بعد التحديث)

على Railway القرص **مؤقت**: عند كل إعادة نشر يُمسح القرص وتُفقد الملفات المحفوظة محلياً (مثل صور المنتجات).

## الحل: استخدام Volume (تخزين دائم)

1. في [Railway Dashboard](https://railway.app) افتح مشروعك.
2. اختر الخدمة (Service) التي تشغّل التطبيق.
3. من تبويب **Variables** أو **Settings** ابحث عن **Volumes** (أو من القائمة الجانبية).
4. اضغط **Add Volume** أو **New Volume**.
5. عيّن **Mount Path** كالتالي (مهم أن يكون مطابقاً):
   ```
   /app/public/uploads
   ```
   (يفترض أن جذر المشروع على Railway هو `/app`؛ إذا كان مختلفاً عدّل المسار وفقاً لذلك.)
6. احفظ ثم أعد نشر التطبيق مرة واحدة.

بعد ذلك سيُحفظ كل ما يُرفع داخل `public/uploads` (بما فيه `uploads/products/`) على الـ Volume ولن يُحذف عند التحديثات القادمة.

## التحقق من أن المجلد شغّال (بعد إضافة Volume)

1. سجّل الدخول كأدمن من: `https://your-app.up.railway.app/login`
2. افتح في المتصفح (بنفس الجلسة):
   ```
   https://your-app.up.railway.app/api/settings/check-uploads
   ```
3. إذا كان كل شيء مضبوطاً سترى JSON مثل:
   ```json
   { "success": true, "exists": true, "writable": true, "path": "/app/public/uploads/products", "message": "مجلد الصور موجود والكتابة تعمل — Volume يعمل بشكل صحيح." }
   ```
4. إذا ظهر `"writable": false` أو `"success": false` راجع Mount Path أو صلاحيات الـ Volume.

(صفحة `diag.php` معطّلة في الإنتاج لأسباب أمنية؛ استخدم الرابط أعلاه بدلاً منها.)

## إذا لم يتوفر Volumes في خطتك

- استخدم تخزين سحابي (مثل AWS S3 أو Cloudinary) لصور المنتجات وعدّل الكود ليحفظ ويربط الروابط من هناك بدلاً من القرص المحلي.
