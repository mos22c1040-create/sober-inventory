<?php
/**
 * Run once to create types table and add type_id to products.
 * Execute from project root: php storage/run_patch_types.php
 */
define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/db.php';
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['port'] ?? 3306,
    $config['dbname']
);

try {
    $pdo = new PDO($dsn, $config['user'], $config['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "خطأ اتصال بقاعدة البيانات: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "جدول types جاهز.\n";

$stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'type_id'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE products ADD COLUMN type_id int unsigned DEFAULT NULL AFTER category_id");
    $pdo->exec("ALTER TABLE products ADD KEY type_id (type_id)");
    try {
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT products_type_fk FOREIGN KEY (type_id) REFERENCES types (id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // FK might already exist or DB doesn't support
    }
    echo "تم إضافة عمود type_id إلى products.\n";
} else {
    echo "عمود type_id موجود مسبقاً.\n";
}
echo "انتهى.\n";
