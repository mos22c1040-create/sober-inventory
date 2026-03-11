-- Patch: Add expenses table + product unit/description fields
-- Run ONCE against your PostgreSQL database

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
  id          SERIAL PRIMARY KEY,
  user_id     INTEGER NOT NULL,
  amount      NUMERIC(12,2) NOT NULL,
  category    VARCHAR(100)  NOT NULL DEFAULT 'عام',
  description VARCHAR(500)  DEFAULT NULL,
  expense_date DATE         NOT NULL,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS expenses_user_id_idx   ON expenses(user_id);
CREATE INDEX IF NOT EXISTS expenses_date_idx       ON expenses(expense_date);

-- Add unit + description to products (PostgreSQL)
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS unit        VARCHAR(50) NOT NULL DEFAULT 'قطعة',
  ADD COLUMN IF NOT EXISTS description TEXT        DEFAULT NULL;
