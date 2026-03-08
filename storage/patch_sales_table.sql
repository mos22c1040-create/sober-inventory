-- Add missing columns to sales table if you get "Unknown column 'total'" error.
-- Run once: mysql -u root -p inventory_pos < storage/patch_sales_table.sql
-- If you get "Duplicate column" error, the column already exists; skip or run the lines you need.

ALTER TABLE `sales`
  ADD COLUMN `total` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `customer_name`;
ALTER TABLE `sales`
  ADD COLUMN `payment_method` enum('cash','card','mixed') DEFAULT 'cash' AFTER `total`;
ALTER TABLE `sales`
  ADD COLUMN `status` enum('paid','pending','cancelled') DEFAULT 'paid' AFTER `payment_method`;
