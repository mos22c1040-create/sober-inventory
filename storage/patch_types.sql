-- أنواع المنتجات (Types) — جدول جديد + عمود اختياري في المنتجات
-- شغّل مرة واحدة: mysql -u user -p database < storage/patch_types.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة عمود النوع للمنتجات (شغّل مرة واحدة؛ إن ظهر خطأ أن العمود موجود فتجاهله)
ALTER TABLE `products` ADD COLUMN `type_id` int unsigned DEFAULT NULL AFTER `category_id`;
ALTER TABLE `products` ADD KEY `type_id` (`type_id`);
ALTER TABLE `products` ADD CONSTRAINT `products_type_fk` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE SET NULL;
