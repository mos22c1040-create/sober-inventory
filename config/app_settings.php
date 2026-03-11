<?php

// config/app_settings.php — System-wide configuration parameters.
//
// Resolution priority (highest wins):
//   1. Database `settings` table  — persistent across Railway container restarts.
//   2. storage/settings.json      — local / offline fallback.
//   3. Hard-coded defaults below  — safe baseline.

$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

$defaults = [
    'app_name'            => 'نظام المخزون',
    'app_version'         => '1.0.0',
    'environment'         => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development',
    'currency_symbol'     => 'د.ع',
    'tax_rate'            => 0.15,
    'timezone'            => 'Asia/Baghdad',
    'low_stock_threshold' => 10,
    'session_lifetime'    => 3600,
];

// Layer 1: JSON file (local / offline environments).
$customFile = $basePath . '/storage/settings.json';
if (is_file($customFile)) {
    $json = @file_get_contents($customFile);
    if ($json !== false) {
        $overrides = json_decode($json, true);
        if (is_array($overrides)) {
            $defaults = array_merge($defaults, $overrides);
        }
    }
}

// Layer 2: Database (Railway / production — survives container restarts).
// class_exists() triggers the autoloader when it is already registered;
// it returns false silently when called before the autoloader is set up
// (e.g. during early boot from AuthHelper::checkAuth for session timeout).
if (class_exists('\App\Models\AppSetting')) {
    try {
        $dbOverrides = \App\Models\AppSetting::all();
        if (!empty($dbOverrides)) {
            $defaults = array_merge($defaults, $dbOverrides);
        }
    } catch (\Throwable) {
        // Database unavailable at config-load time — use JSON / defaults silently.
    }
}

return $defaults;
