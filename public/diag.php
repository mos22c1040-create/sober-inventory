<?php
/**
 * Diagnostic page — checks DB connection, tables, and admin user.
 * Access: /diag.php
 * DELETE this file after fixing the issue!
 */

// ── Security: single-use token to prevent public access ──────────────────────
// Change the token below, then visit: /diag.php?token=sober2026
define('DIAG_TOKEN', 'sober2026');

if (($_GET['token'] ?? '') !== DIAG_TOKEN) {
    http_response_code(403);
    exit('403 Forbidden. Add ?token=' . DIAG_TOKEN . ' to the URL.');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$ok   = '✅';
$fail = '❌';
$warn = '⚠️';

$results = [];

// ── 1. Load env & config ──────────────────────────────────────────────────────
try {
    // Load .env manually without calling loadEnv() again
    $envFile = BASE_PATH . '/.env';
    if (file_exists($envFile)) {
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ((array)$lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k); $v = trim($v);
            if (strlen($v) >= 2 && (($v[0] === '"' && substr($v,-1) === '"') || ($v[0] === "'" && substr($v,-1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            if (!isset($_ENV[$k])) $_ENV[$k] = $v;
        }
    }

    // Build config from loaded env
    $dbUrl = $_ENV['DATABASE_URL'] ?? '';
    if ($dbUrl !== '' && (str_starts_with($dbUrl, 'postgres://') || str_starts_with($dbUrl, 'postgresql://'))) {
        $url = parse_url($dbUrl);
        $config = [
            'driver'   => 'pgsql',
            'host'     => $url['host'] ?? '127.0.0.1',
            'port'     => (string)($url['port'] ?? 5432),
            'dbname'   => ltrim($url['path'] ?? '/postgres', '/'),
            'user'     => $url['user'] ?? 'postgres',
            'password' => isset($url['pass']) ? rawurldecode($url['pass']) : '',
        ];
    } else {
        $config = [
            'driver'   => 'mysql',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? '3306',
            'dbname'   => $_ENV['DB_DATABASE'] ?? 'inventory_pos',
            'user'     => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }
    $results[] = [$ok, 'تحميل الإعدادات', 'driver=' . $config['driver'] . ' host=' . $config['host']];
} catch (Throwable $e) {
    $results[] = [$fail, 'تحميل الإعدادات', $e->getMessage()];
    $config = null;
}

// ── 2. DATABASE_URL ───────────────────────────────────────────────────────────
$dbUrl = $_ENV['DATABASE_URL'] ?? '';
if ($dbUrl !== '') {
    $masked = preg_replace('/:[^:@]+@/', ':***@', $dbUrl);
    $results[] = [$ok, 'DATABASE_URL موجود', $masked];
} else {
    $results[] = [$warn, 'DATABASE_URL غير موجود', 'يستخدم DB_HOST/DB_USER بدلاً منه'];
}

// ── 3. Connect ────────────────────────────────────────────────────────────────
$pdo = null;
if ($config) {
    try {
        $driver = $config['driver'];
        if ($driver === 'pgsql') {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $pdo = new PDO($dsn, $config['user'], $config['password'], $opts);
        } else {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        }
        $results[] = [$ok, 'الاتصال بقاعدة البيانات', 'تم الاتصال بـ ' . $driver . ' (' . $config['dbname'] . ')'];
    } catch (Throwable $e) {
        $results[] = [$fail, 'الاتصال بقاعدة البيانات', $e->getMessage()];
    }
}

// ── 4. Tables ─────────────────────────────────────────────────────────────────
$required = ['users', 'categories', 'products', 'sales', 'sale_items', 'purchases', 'purchase_items', 'activity_log', 'sessions'];
if ($pdo) {
    try {
        $existing = [];
        if (($config['driver'] ?? '') === 'pgsql') {
            $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname='public'");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $existing[] = $t;
        } else {
            $stmt = $pdo->query("SHOW TABLES");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) $existing[] = $t;
        }
        foreach ($required as $table) {
            if (in_array($table, $existing)) {
                $results[] = [$ok, "جدول: $table", 'موجود'];
            } else {
                $results[] = [$fail, "جدول: $table", 'غير موجود — نفّذ storage/schema.pgsql في Supabase'];
            }
        }
    } catch (Throwable $e) {
        $results[] = [$fail, 'فحص الجداول', $e->getMessage()];
    }
}

// ── 5. Admin user ─────────────────────────────────────────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, status, length(password) AS pwd_len FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => 'admin@example.com']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $results[] = [$ok, 'مستخدم الأدمن', "موجود | id={$row['id']} status={$row['status']} pwd_len={$row['pwd_len']}"];
            // Verify password
            $hashed = $pdo->query("SELECT password FROM users WHERE email='admin@example.com' LIMIT 1")->fetchColumn();
            $hashed = trim((string) $hashed);
            $valid = password_verify('password', $hashed);
            if ($valid) {
                $results[] = [$ok, 'التحقق من كلمة المرور', 'password_verify("password") = true ✔'];
            } else {
                $results[] = [$fail, 'التحقق من كلمة المرور', 'password_verify("password") = false — الهاش لا يطابق "password"'];
            }
        } else {
            $results[] = [$fail, 'مستخدم الأدمن', 'غير موجود — نفّذ storage/reset_admin_password.pgsql في Supabase'];
        }
    } catch (Throwable $e) {
        $results[] = [$fail, 'فحص مستخدم الأدمن', $e->getMessage()];
    }
}

// ── 6. Sessions table write test ─────────────────────────────────────────────
if ($pdo && ($config['driver'] ?? '') === 'pgsql') {
    try {
        $pdo->prepare(
            "INSERT INTO sessions (id, payload, last_activity) VALUES ('diag_test', 'test', :la)
             ON CONFLICT (id) DO UPDATE SET payload='test', last_activity=EXCLUDED.last_activity"
        )->execute([':la' => time()]);
        $pdo->exec("DELETE FROM sessions WHERE id='diag_test'");
        $results[] = [$ok, 'جدول sessions', 'الكتابة والحذف يعملان'];
    } catch (Throwable $e) {
        $results[] = [$fail, 'جدول sessions', $e->getMessage()];
    }
}

// ── HTML output ───────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تشخيص النظام</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; direction: rtl; }
  h1 { color: #38bdf8; margin-bottom: 1.5rem; }
  table { border-collapse: collapse; width: 100%; }
  th, td { padding: .5rem 1rem; border: 1px solid #334155; text-align: right; }
  th { background: #1e293b; color: #94a3b8; }
  tr:hover { background: #1e293b; }
  .ok   { color: #4ade80; }
  .fail { color: #f87171; }
  .warn { color: #facc15; }
  .note { margin-top: 2rem; background: #1e293b; padding: 1rem; border-radius: .5rem; color: #fbbf24; }
</style>
</head>
<body>
<h1>🔍 تشخيص نظام المخزون</h1>
<p style="color:#94a3b8; margin-bottom:1.5rem;">الوقت: <?= date('Y-m-d H:i:s') ?> | PHP: <?= PHP_VERSION ?></p>
<table>
<tr><th>الحالة</th><th>الفحص</th><th>التفاصيل</th></tr>
<?php foreach ($results as [$status, $check, $detail]): ?>
<tr>
  <td><?= $status ?></td>
  <td><?= htmlspecialchars($check) ?></td>
  <td style="font-size:.85rem; color:<?= $status === '✅' ? '#86efac' : ($status === '❌' ? '#fca5a5' : '#fde68a') ?>"><?= htmlspecialchars($detail) ?></td>
</tr>
<?php endforeach; ?>
</table>
<div class="note">
  ⚠️ احذف هذا الملف (<code>public/diag.php</code>) بعد حل المشكلة — هو للتشخيص فقط!
</div>
</body>
</html>
