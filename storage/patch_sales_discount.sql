-- تحديث جدول sales: إضافة حقلَي discount و notes
ALTER TABLE `sales`
    ADD COLUMN IF NOT EXISTS `discount` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `total`,
    ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL AFTER `discount`;
