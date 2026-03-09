<?php
/**
 * Run once to create activity_log table. Execute from project root:
 *   php storage/run_patch_activity_log.php
 */
define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/db.php';
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['port'],
    $config['dbname']
);

try {
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "خطأ اتصال بقاعدة البيانات: " . $e->getMessage() . "\n");
    exit(1);
}

$sql = "CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `details` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    echo "تم إنشاء جدول activity_log بنجاح.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "خطأ: " . $e->getMessage() . "\n");
    exit(1);
}
