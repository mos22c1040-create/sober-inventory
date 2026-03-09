# ربط المشروع بـ Supabase (PostgreSQL)

المشروع يدعم **MySQL** و **PostgreSQL**. لاستخدام **Supabase** كقاعدة بيانات:

---

## 1. إنشاء مشروع Supabase

1. ادخل إلى [supabase.com](https://supabase.com) وأنشئ مشروعاً.
2. من **Project Settings** → **Database** انسخ **Connection string** (URI).
   - الصيغة: `postgresql://postgres:[YOUR-PASSWORD]@db.xxxx.supabase.co:5432/postgres`
   - استبدل `[YOUR-PASSWORD]` بكلمة مرور قاعدة البيانات التي تظهر في الإعدادات.

---

## 2. إعداد المتغيرات

### محلياً (ملف `.env`)

في جذر المشروع أنشئ أو عدّل `.env` وضَع:

```env
DATABASE_URL=postgresql://postgres:YOUR_PASSWORD@db.xxxx.supabase.co:5432/postgres
APP_ENV=development
SESSION_DRIVER=file
```

> **تحذير أمني:** لا ترفع ملف `.env` إلى Git ولا تشارك رابط الاتصال علناً.

### على Vercel

في **Settings** → **Environment Variables** أضف:

| المفتاح        | القيمة |
|----------------|--------|
| `DATABASE_URL` | `postgresql://postgres:كلمة_المرور@db.xxxx.supabase.co:5432/postgres` |
| `APP_ENV`      | `production` |
| `SESSION_DRIVER` | `database` |

---

## 3. تنفيذ السكربت (الجداول)

1. من لوحة Supabase: **SQL Editor** → **New query**.
2. انسخ **كامل** محتوى الملف **`storage/schema.pgsql`** من المشروع.
3. الصق في المحرر ثم اضغط **Run**.

بهذا يتم إنشاء كل الجداول (users, categories, products, sales, …) وإدراج مستخدم مدير افتراضي إن لم يكن موجوداً.

- **تسجيل الدخول الافتراضي:** البريد `admin@example.com` وكلمة المرور `password` (غيّرها بعد أول دخول).

---

## 4. التشغيل

- **محلياً:** شغّل السيرفر كما في الـ README (مثلاً `php -S localhost:8000 -t public public/index.php`) وتأكد أن `.env` يحتوي فقط على `DATABASE_URL` للـ Supabase (بدون إعدادات MySQL إن كنت لا تستخدمها).
- **على Vercel:** بعد ربط المستودع وإضافة `DATABASE_URL` و `SESSION_DRIVER=database`، قم بعمل Deploy.

---

## ملاحظات

- عند وجود **`DATABASE_URL`** يبدأ الرابط بـ `postgres://` أو `postgresql://`، التطبيق يتصل تلقائياً بـ **PostgreSQL** (Supabase) ولا يستخدم إعدادات MySQL.
- الجداول والاستعلامات مبنية لتعمل مع **PostgreSQL** عند استخدام `schema.pgsql` ومع **MySQL** عند استخدام `schema.sql`.
