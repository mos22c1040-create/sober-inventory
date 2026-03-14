-- Add purchase_number column to purchases table for PurchaseService
-- Run this migration to enable PO-YYYY-NNN purchase order numbering

ALTER TABLE purchases ADD COLUMN IF NOT EXISTS purchase_number VARCHAR(20) UNIQUE;

-- Update existing records with generated purchase numbers (optional - for existing data)
-- This will generate PO-YYYY-NNN for any existing purchases that don't have a number
-- Note: Only run this if you have existing purchase records you want to preserve

-- UPDATE purchases 
-- SET purchase_number = 'PO-' || TO_CHAR(created_at, 'YYYY') || '-' || LPAD(id::TEXT, 3, '0')
-- WHERE purchase_number IS NULL;