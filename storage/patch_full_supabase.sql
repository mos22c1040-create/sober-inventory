-- ==========================================================
-- Sober POS — Full Supabase Patch (Run this in SQL Editor)
-- ==========================================================
-- This patch is SAFE to run multiple times (idempotent).
-- It adds all columns and tables that may be missing.
-- ==========================================================

-- 1. Create types table
CREATE TABLE IF NOT EXISTS types (
  id        SERIAL PRIMARY KEY,
  name      VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add missing columns to products table
ALTER TABLE products ADD COLUMN IF NOT EXISTS type_id     INTEGER DEFAULT NULL REFERENCES types(id) ON DELETE SET NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS unit        VARCHAR(50) DEFAULT 'قطعة';
ALTER TABLE products ADD COLUMN IF NOT EXISTS description TEXT;

-- 3. Add index for type_id
CREATE INDEX IF NOT EXISTS idx_products_type_id ON products(type_id);

-- 4. Add missing columns to sales table (if any)
ALTER TABLE sales ADD COLUMN IF NOT EXISTS discount  DECIMAL(12,2) DEFAULT 0.00;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS notes     TEXT;

-- 5. Add missing columns to expenses table (if it doesn't exist, create it)
CREATE TABLE IF NOT EXISTS expenses (
  id          SERIAL PRIMARY KEY,
  user_id     INT REFERENCES users(id) ON DELETE SET NULL,
  title       VARCHAR(255) NOT NULL,
  amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  category    VARCHAR(100),
  notes       TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_expenses_user    ON expenses(user_id);
CREATE INDEX IF NOT EXISTS idx_expenses_created ON expenses(created_at);

-- Done!
SELECT 'Patch applied successfully.' AS result;
