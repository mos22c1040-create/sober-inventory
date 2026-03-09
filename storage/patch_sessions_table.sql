-- Sessions table for serverless (Vercel) — run if you use SESSION_DRIVER=database
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `payload` text NOT NULL,
  `last_activity` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
