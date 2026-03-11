-- Patch: Add expenses table + product unit/description fields
-- Run ONCE against your database

-- Expenses table (MySQL)
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'عام',
  `description` varchar(500) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `expense_date` (`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add unit + description to products (MySQL)
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `unit`        varchar(50)  NOT NULL DEFAULT 'قطعة' AFTER `low_stock_threshold`,
  ADD COLUMN IF NOT EXISTS `description` text         DEFAULT NULL             AFTER `unit`;
