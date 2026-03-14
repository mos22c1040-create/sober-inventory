-- Add product image column (path relative to web root, e.g. uploads/products/xxx.jpg)
-- Run once. Use the block that matches your database.

-- PostgreSQL / Supabase:
ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(512) DEFAULT NULL;

-- MySQL (if you use MySQL instead, run this and comment out the line above):
-- ALTER TABLE `products` ADD COLUMN `image` VARCHAR(512) DEFAULT NULL;
