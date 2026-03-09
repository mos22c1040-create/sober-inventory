<?php

// config/db.php - Database configuration (MySQL or PostgreSQL via DATABASE_URL)

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // إزالة علامات الاقتباس من بداية ونهاية القيمة
        if ((strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"')
            || (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
    }
}

loadEnv(BASE_PATH . '/.env');

$databaseUrl = $_ENV['DATABASE_URL'] ?? '';

if ($databaseUrl !== '' && (strpos($databaseUrl, 'postgres://') === 0 || strpos($databaseUrl, 'postgresql://') === 0)) {
    // Supabase / PostgreSQL: postgresql://user:pass@host:5432/postgres
    $url = parse_url($databaseUrl);
    $scheme = $url['scheme'] ?? 'postgresql';
    return [
        'driver'   => 'pgsql',
        'host'     => $url['host'] ?? '127.0.0.1',
        'port'     => (string) ($url['port'] ?? 5432),
        'dbname'   => ltrim($url['path'] ?? '/postgres', '/'),
        'user'     => $url['user'] ?? 'postgres',
        'password' => isset($url['pass']) ? rawurldecode($url['pass']) : '',
        'charset'  => 'utf8',
    ];
}

return [
    'driver'   => 'mysql',
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT'] ?? '3306',
    'dbname'   => $_ENV['DB_DATABASE'] ?? 'inventory_pos',
    'user'     => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => 'utf8mb4',
];
