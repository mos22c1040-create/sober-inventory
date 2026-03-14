-- أنواع المنتجات (Types) — لـ Supabase / PostgreSQL
-- انسخه والصقه في Supabase SQL Editor ثم Run

-- 1) إنشاء جدول الأنواع
CREATE TABLE IF NOT EXISTS types (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2) إضافة عمود النوع للمنتجات (اختياري)
ALTER TABLE products ADD COLUMN IF NOT EXISTS type_id INTEGER DEFAULT NULL REFERENCES types(id) ON DELETE SET NULL;

-- 3) فهرس لتسريع الاستعلامات
CREATE INDEX IF NOT EXISTS idx_products_type_id ON products(type_id);
