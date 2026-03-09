<?php

// config/app_settings.php - System Wide Configuration Parameters
// User-editable values (app_name, currency_symbol) can be overridden via storage/settings.json

$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
$defaults = [
    'app_name'          => 'نظام المخزون',
    'app_version'       => '1.0.0',
    'environment'       => $_ENV['APP_ENV'] ?? 'development',
    'currency_symbol'   => 'د.ع',
    'tax_rate'          => 0.15,
    'timezone'          => 'Asia/Baghdad',
    'low_stock_threshold' => 10,
    'session_lifetime'  => 3600,
];

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

return $defaults;
