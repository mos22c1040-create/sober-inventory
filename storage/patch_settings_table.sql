-- Patch: Add settings table for persistent system configuration (MySQL)
-- Run ONCE against your database. Replaces storage/settings.json on Railway.

CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default values
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('app_name',        'نظام المخزون'),
  ('currency_symbol', 'د.ع');
