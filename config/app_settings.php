<?php

// config/app_settings.php - System Wide Configuration Parameters

return [
    'app_name'     => 'Modern Inventory POS',
    'app_version'  => '1.0.0',
    'environment'  => $_ENV['APP_ENV'] ?? 'development',
    
    // Core formatting rules
    'currency_symbol' => '$', // Configurable (You mentioned using Iraqi Dinar in older projects, e.g., 'ألف')
    'tax_rate'        => 0.15, // 15% Tax Default
    'timezone'        => 'Asia/Baghdad',
    
    // System Features limiters
    'low_stock_threshold' => 10,
    
    // Auth settings
    'session_lifetime' => 3600 // 1 Hour
];
