<?php

// config/db.php - Database configuration (MySQL or PostgreSQL via DATABASE_URL)

if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            $parts = explode('=', $line, 2);
            $key   = trim($parts[0]);
            $value = trim($parts[1]);
            // إزالة علامات الاقتباس من بداية ونهاية القيمة
            if (
                (strlen($value) >= 2 && $value[0] === '"'  && substr($value, -1) === '"')
                || (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'")
            ) {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(BASE_PATH . '/.env');

// Railway/Heroku تضع متغيرات البيئة في getenv()
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';

if (
    $databaseUrl !== ''
    && (strpos($databaseUrl, 'postgres://') === 0 || strpos($databaseUrl, 'postgresql://') === 0)
) {
    $url = parse_url($databaseUrl);

    $originalHost = $url['host']                           ?? '127.0.0.1';
    $port         = (int) ($url['port']                    ?? 5432);
    $user         = $url['user']                           ?? 'postgres';
    $password     = isset($url['pass']) ? rawurldecode($url['pass']) : '';
    $dbname       = ltrim($url['path'] ?? '/postgres', '/');

    // ─────────────────────────────────────────────────────────────────────────
    // IPv6 fix for Railway → Supabase connections.
    //
    // When DATABASE_URL points to the Direct Connection host
    // (db.PROJECT_REF.supabase.co:5432) Railway may attempt IPv6 and fail.
    //
    // Resolution strategy (fastest first):
    //   1. POOLER_HOST env var — set once on Railway to skip all DNS work.
    //   2. Supabase direct hostname → auto-switch to Transaction Pooler (IPv4).
    //   3. Other hosts — plain gethostbyname() to force IPv4.
    // ─────────────────────────────────────────────────────────────────────────

    // Fast path: operator pre-set the pooler host — zero DNS calls needed.
    $envPoolerHost = $_ENV['POOLER_HOST'] ?? getenv('POOLER_HOST') ?: '';

    $isSupabaseDirect = (bool) preg_match(
        '/^db\.([a-z0-9]+)\.supabase\.co$/i',
        $originalHost,
        $matches
    );

    if ($isSupabaseDirect) {
        $projectRef = $matches[1];

        if ($envPoolerHost !== '') {
            // Use the pre-configured pooler host — no DNS lookup required.
            $poolerHost = $envPoolerHost;
        } else {
            // Probe common Supabase regions to find a resolvable pooler host.
            $regions = [
                'us-east-1',
                'us-west-1',
                'eu-west-2',
                'eu-central-1',
                'ap-southeast-1',
                'ap-northeast-1',
            ];

            $poolerHost = null;
            foreach ($regions as $region) {
                $candidate = "aws-0-{$region}.pooler.supabase.com";
                $resolved  = gethostbyname($candidate);
                if ($resolved !== $candidate) {
                    $poolerHost = $candidate;
                    break;
                }
            }

            if ($poolerHost === null) {
                $poolerHost = 'aws-0-us-east-1.pooler.supabase.com';
            }
        }

        $connectHost = $poolerHost;
        $connectPort = '6543';
        $connectUser = "postgres.{$projectRef}";
    } else {
        // Already a pooler URL or non-Supabase host — force IPv4 resolution.
        $resolved    = gethostbyname($originalHost);
        $connectHost = ($resolved !== $originalHost) ? $resolved : $originalHost;
        $connectPort = (string) $port;
        $connectUser = $user;
    }

    return [
        'driver'   => 'pgsql',
        'host'     => $connectHost,
        'port'     => $connectPort,
        'dbname'   => $dbname,
        'user'     => $connectUser,
        'password' => $password,
        'charset'  => 'utf8',
    ];
}

// ── MySQL fallback (للتطوير المحلي) ──────────────────────────────────────────
return [
    'driver'   => 'mysql',
    'host'     => $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: '127.0.0.1',
    'port'     => $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '3306',
    'dbname'   => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'inventory_pos',
    'user'     => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];
