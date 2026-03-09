# نشر المشروع على Vercel + ربط MySQL + GitHub

دليل ربط **GitHub** و **MySQL** ونشر **نظام المخزون** على **Vercel**.

---

## 1. قاعدة البيانات MySQL (سحابية)

على Vercel لا يوجد MySQL مدمج؛ تحتاج قاعدة بيانات MySQL سحابية. خيارات مناسبة:

### أ) PlanetScale (مجاني للتجربة، متوافق مع MySQL)

1. ادخل إلى [planetscale.com](https://planetscale.com) وسجّل دخولاً بحساب GitHub.
2. أنشئ قاعدة بيانات جديدة (مثلاً `sober-inventory`).
3. من **Connect** اختر **Connect with** → عرض بيانات الاتصال.
4. سجّل:
   - **Host**
   - **Username**
   - **Password**
   - **Database** (اسم القاعدة)

> ملاحظة: PlanetScale يستخدم منفذ **3306** واتصال **SSL** أحياناً. إن طلب منك تفعيل SSL أضف في `.env`:  
> `DB_SSL=1` أو راجع وثائق PlanetScale لسلسلة الاتصال.

### ب) Railway

1. ادخل إلى [railway.app](https://railway.app) واربط حساب GitHub.
2. **New Project** → **Provision MySQL**.
3. من تبويب MySQL اضغط **Variables** وانسخ:  
   `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_PORT`.

### ج) أي استضافة MySQL أخرى

مثلاً: **Aiven**, **FreeSQLDatabase**, أو MySQL على **استضافة مشتركة**.  
استخدم نفس المتغيرات: `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_PORT`.

---

## 2. تنفيذ السكما وجدول الجلسات

بعد إنشاء القاعدة:

1. استورد الملف **`storage/schema.sql`** (من المشروع) إلى قاعدة البيانات (phpMyAdmin أو أي عميل MySQL).
2. لتفعيل **جلسات قاعدة البيانات** (مطلوبة على Vercel لتسجيل الدخول)، نفّذ أيضاً:
   - **`storage/patch_sessions_table.sql`**
   أو تأكد أن جدول **`sessions`** موجود (مضمَن إذا استوردت `schema.sql` الحالي).

3. أضف مستخدماً مديراً إذا لم يكن موجوداً:

```sql
INSERT INTO users (username, email, password, role, status)
VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  'active'
);
-- كلمة المرور الافتراضية: password
```

---

## 3. ربط المستودع (GitHub) بـ Vercel

1. ادخل إلى [vercel.com](https://vercel.com) وسجّل دخولاً بحساب **GitHub**.
2. **Add New** → **Project**.
3. اختر المستودع الذي يحتوي المشروع (مثلاً `sober-inventory`).
4. **Framework Preset**: يمكن ترك **Other** أو اختيار **PHP** إن وُجد.
5. **Root Directory**: اتركه فارغاً إذا المشروع في جذر المستودع.
6. **Build and Output**: لا تحتاج أوامر بناء؛ Vercel سيستخدم `vercel.json`.

---

## 4. إضافة متغيرات البيئة (Environment Variables) على Vercel

من إعدادات المشروع على Vercel: **Settings** → **Environment Variables**، أضف:

| الاسم | القيمة | ملاحظة |
|------|--------|--------|
| `APP_ENV` | `production` | إخفاء رسائل الأخطاء |
| `SESSION_DRIVER` | `database` | **مهم** لتسجيل الدخول على Vercel |
| `DB_HOST` | قيمة Host من PlanetScale/Railway | |
| `DB_PORT` | `3306` | أو المنفذ الذي يعطيك إياه الخدمة |
| `DB_DATABASE` | اسم القاعدة | |
| `DB_USERNAME` | اسم المستخدم | |
| `DB_PASSWORD` | كلمة مرور القاعدة | |

لا ترفع ملف **`.env`** إلى Git؛ استخدم فقط متغيرات Vercel.

---

## 5. النشر (Deploy)

1. بعد ربط GitHub وإضافة المتغيرات، اضغط **Deploy**.
2. انتظر انتهاء النشر ثم افتح الرابط الذي يعطيك إياه Vercel (مثلاً `https://your-project.vercel.app`).
3. يجب أن تظهر صفحة **تسجيل الدخول**.
4. سجّل الدخول بـ:
   - البريد: `admin@example.com`
   - كلمة المرور: `password`  
   ثم غيّر كلمة المرور من الإعدادات أو من حساب المدير.

---

## 6. استكشاف الأخطاء

| المشكلة | ما تفعله |
|---------|----------|
| صفحة بيضاء أو 500 | راجع **Deploy** → **Functions** أو **Logs** على Vercel. تأكد أن كل متغيرات `DB_*` و `SESSION_DRIVER=database` مضبوطة. |
| خطأ اتصال بقاعدة البيانات | تحقق من `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` وقواعد الجدار الناري إن وُجدت (السماح لـ Vercel بالاتصال). |
| تسجيل الدخول لا يعمل / الجلسة تضيع | تأكد أن `SESSION_DRIVER=database` وأن جدول `sessions` موجود ونفّذت `patch_sessions_table.sql` أو `schema.sql`. |
| أصول ثابتة (CSS/JS) لا تظهر | تأكد أن `vercel.json` يوجّه الملفات الثابتة من مجلد `public` كما في المشروع. |

---

## ملخص الملفات المهمة للنشر

- **`vercel.json`** — توجيه الطلبات وبناء PHP.
- **`api/index.php`** — نقطة الدخول على Vercel (Serverless).
- **`SESSION_DRIVER=database`** — ضروري لاستخدام جلسات MySQL على Vercel.
- **جدول `sessions`** — مطلوب عند استخدام `SESSION_DRIVER=database`.

بعد ذلك يكون المشروع مربوطاً بـ **GitHub**، و**MySQL** السحابية، ومنشوراً على **Vercel**.
