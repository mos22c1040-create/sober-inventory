<?php
/**
 * Emergency admin password reset via PHP (bypasses SQL editor issues).
 * Access: /reset_pass.php?token=sober2026&newpass=YOUR_NEW_PASSWORD
 * DELETE this file after use!
 */

define('RESET_TOKEN', 'sober2026');

if (($_GET['token'] ?? '') !== RESET_TOKEN) {
    http_response_code(403);
    exit('403 Forbidden. Usage: /reset_pass.php?token=' . RESET_TOKEN . '&newpass=YourNewPassword');
}

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// Load env manually
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        if (!isset($_ENV[$k])) $_ENV[$k] = $v;
    }
}

// Build DB config
$dbUrl = $_ENV['DATABASE_URL'] ?? '';
if ($dbUrl !== '' && (str_starts_with($dbUrl, 'postgres') || str_starts_with($dbUrl, 'postgresql'))) {
    $url = parse_url($dbUrl);
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $url['host'] ?? 'localhost',
        $url['port'] ?? 5432,
        ltrim($url['path'] ?? '/postgres', '/')
    );
    $user = $url['user'] ?? 'postgres';
    $pass = isset($url['pass']) ? rawurldecode($url['pass']) : '';
} else {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? '127.0.0.1',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_DATABASE'] ?? 'inventory_pos'
    );
    $user = $_ENV['DB_USERNAME'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';
}

$newpass = $_GET['newpass'] ?? 'Admin@2026';

try {
    $pdo  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmt = $pdo->prepare('UPDATE users SET password = :hash WHERE email = :email');
    $stmt->execute([':hash' => $hash, ':email' => 'admin@example.com']);
    $rows = $stmt->rowCount();

    // Verify immediately
    $row   = $pdo->query("SELECT length(password) as l, password FROM users WHERE email='admin@example.com' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valid = password_verify($newpass, (string)($row['password'] ?? ''));

    echo '<pre style="background:#0f172a;color:#4ade80;padding:2rem;font-size:1.1rem;direction:ltr;">';
    echo "✅ Updated rows: $rows\n";
    echo "✅ New hash length: " . ($row['l'] ?? '?') . " chars\n";
    echo "✅ password_verify('$newpass') = " . ($valid ? 'TRUE ✔' : 'FALSE ✘') . "\n\n";
    if ($valid) {
        echo "🎉 SUCCESS!\n";
        echo "Email:    admin\@example.com\n";
        echo "Password: $newpass\n\n";
        echo "Now login at: /login\n";
        echo "Then DELETE this file: public/reset_pass.php\n";
    } else {
        echo "❌ FAILED — password_verify returned false. Check DB manually.\n";
    }
    echo '</pre>';
} catch (Throwable $e) {
    echo '<pre style="background:#0f172a;color:#f87171;padding:2rem;">';
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo '</pre>';
}
