-- Patch: Add settings table for persistent system configuration (PostgreSQL / Supabase)
-- Run ONCE in the Supabase SQL Editor. Replaces storage/settings.json on Railway.

CREATE TABLE IF NOT EXISTS settings (
  id         SERIAL       PRIMARY KEY,
  key        VARCHAR(100) NOT NULL,
  value      TEXT         DEFAULT NULL,
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS settings_key_unique ON settings(key);

-- Seed default values (safe to run multiple times)
INSERT INTO settings (key, value)
VALUES
  ('app_name',        'نظام المخزون'),
  ('currency_symbol', 'د.ع')
ON CONFLICT (key) DO NOTHING;
