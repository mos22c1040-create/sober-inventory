<?php

// public/index.php - The main entry point (can also be used as PHP built-in server router)
// On Vercel, api/index.php sets BASE_PATH and includes this file.

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// When used as router: php -S localhost:8000 -t public public/index.php
// Serve existing static files (favicon, css, js, images) and let the server handle them
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($requestPath !== '/' && $requestPath !== '') {
    $staticFile = __DIR__ . $requestPath;
    if (is_file($staticFile) && strpos(realpath($staticFile), realpath(__DIR__)) === 0) {
        return false; // let PHP built-in server serve the file
    }
    // Emergency password reset — handle via router when static serve fails (e.g. Railway)
    if ($requestPath === '/reset_pass.php') {
        require __DIR__ . '/reset_pass.php';
        return true;
    }
}

// Load env early for APP_ENV
if (file_exists(BASE_PATH . '/config/db.php')) {
    require BASE_PATH . '/config/db.php';
}
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Set timezone from app settings (db + env already loaded above)
$appSettings = file_exists(BASE_PATH . '/config/app_settings.php')
    ? (array) include BASE_PATH . '/config/app_settings.php'
    : [];
date_default_timezone_set($appSettings['timezone'] ?? 'Asia/Baghdad');

// Basic autoloader for our custom framework
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    // e.g., App\Controllers\AuthController -> app/Controllers/AuthController.php
    $prefix = 'App\\';
    $base_dir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Use database sessions on Vercel/serverless (SESSION_DRIVER=database in .env)
if (($_ENV['SESSION_DRIVER'] ?? '') === 'database') {
    $handler = new \App\Helpers\DatabaseSessionHandler();
    session_set_save_handler($handler, true);
}

\App\Helpers\AuthHelper::startSession();

// Initialize Router
$router = new App\Core\Router();

// Define Auth Routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/api/login', 'AuthController@login');
$router->post('/api/logout', 'AuthController@logout');

// Define some basic routes
$router->get('/', 'HomeController@index');
$router->get('/dashboard', 'DashboardController@index');

// Products
$router->get('/products', 'ProductController@index');
$router->get('/api/products/barcode', 'ProductController@barcode');
$router->get('/barcode-scan', 'ProductController@barcodeScanPage');
$router->post('/api/barcode-push', 'ProductController@barcodePush');
$router->get('/api/barcode-last', 'ProductController@barcodeLast');
$router->get('/products/create', 'ProductController@create');
$router->post('/api/products', 'ProductController@store');
$router->get('/products/edit', 'ProductController@edit');
$router->post('/api/products/update', 'ProductController@update');
$router->post('/api/products/delete', 'ProductController@delete');

// Categories
$router->get('/categories', 'CategoryController@index');
$router->get('/categories/create', 'CategoryController@create');
$router->post('/api/categories', 'CategoryController@store');
$router->get('/categories/edit', 'CategoryController@edit');
$router->post('/api/categories/update', 'CategoryController@update');
$router->post('/api/categories/delete', 'CategoryController@delete');

// Sales
$router->get('/sales', 'SaleController@index');
$router->get('/sales/create', 'SaleController@create');
$router->post('/api/sales', 'SaleController@store');

// Purchases
$router->get('/purchases', 'PurchaseController@index');
$router->get('/purchases/create', 'PurchaseController@create');
$router->post('/api/purchases', 'PurchaseController@store');

// Reports
$router->get('/reports', 'ReportController@index');
$router->get('/reports/export/sales', 'ReportController@exportSalesCsv');
$router->get('/reports/export/products', 'ReportController@exportTopProductsCsv');

// Activity log (Admin only)
$router->get('/activity-log', 'ActivityLogController@index');

// Settings (Admin only)
$router->get('/settings', 'SettingsController@index');
$router->post('/api/settings', 'SettingsController@store');

// Users (Admin only)
$router->get('/users',              'UserController@index');
$router->get('/users/create',       'UserController@create');
$router->post('/api/users',         'UserController@store');
$router->get('/users/edit',         'UserController@edit');
$router->post('/api/users/update',  'UserController@update');
$router->post('/api/users/password','UserController@changePassword');
$router->post('/api/users/delete',  'UserController@delete');

// حسابي (للمستخدم الحالي)
$router->get('/profile', 'ProfileController@index');
$router->post('/api/profile/password', 'ProfileController@updatePassword');

// Parse URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// If app is in a subfolder like 'sober', you'd need to extract just the path after it
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if ($baseDir === '\\' || $baseDir === '.') {
    $baseDir = '/';
}
if (strpos($uri, $baseDir) === 0 && $baseDir !== '/') {
    $uri = substr($uri, strlen($baseDir));
}
// Normalize: /index.php or /index.php/ → /
$uri = preg_replace('#^/index\.php(?=/|$)#', '/', $uri);
if ($uri === '' || $uri === false) {
    $uri = '/';
}

// Let the router handle the current request
$method = $_SERVER['REQUEST_METHOD'];
$router->route($uri, $method);

