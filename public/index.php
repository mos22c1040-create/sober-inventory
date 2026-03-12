<?php

// public/index.php - The main entry point (can also be used as PHP built-in server router)
// On Vercel, api/index.php sets BASE_PATH and includes this file.

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load env early so we can block dev-only routes in production
if (file_exists(BASE_PATH . '/config/db.php')) {
    require BASE_PATH . '/config/db.php';
}
$isProduction = ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development') === 'production';

// When used as router: php -S localhost:8000 -t public public/index.php
// Serve existing static files (favicon, css, js, images) and let the server handle them
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($requestPath !== '/' && $requestPath !== '') {
    // In production, block diagnostic and password-reset pages (security)
    if ($isProduction && in_array($requestPath, ['/diag.php', '/reset_pass.php'], true)) {
        http_response_code(404);
        exit;
    }
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

// Security headers (per backend-security-coder: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, HSTS in production)
\App\Helpers\Security::sendSecurityHeaders();

// Use database sessions on Vercel/serverless (SESSION_DRIVER=database in .env)
if (($_ENV['SESSION_DRIVER'] ?? getenv('SESSION_DRIVER') ?: '') === 'database') {
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
$router->get('/pos', 'PosController@index');
$router->get('/api/pos/products', 'PosController@products');
$router->post('/api/pos/complete', 'PosController@complete');

// Products
$router->get('/products', 'ProductController@index');
$router->get('/api/products', 'ProductController@indexApi');
$router->get('/api/products/search', 'ProductController@search');
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
$router->get('/sales/receipt', 'SaleController@receipt');
$router->post('/api/sales', 'SaleController@store');
$router->post('/api/sales/cancel', 'SaleController@cancel');

// Purchases
$router->get('/purchases', 'PurchaseController@index');
$router->get('/purchases/create', 'PurchaseController@create');
$router->post('/api/purchases', 'PurchaseController@store');
$router->post('/api/purchases/delete', 'PurchaseController@delete');

// Expenses
$router->get('/expenses', 'ExpenseController@index');
$router->get('/expenses/create', 'ExpenseController@create');
$router->get('/expenses/edit', 'ExpenseController@edit');
$router->post('/api/expenses', 'ExpenseController@store');
$router->post('/api/expenses/update', 'ExpenseController@update');
$router->post('/api/expenses/delete', 'ExpenseController@delete');

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
// Ensure API login response is never mixed with HTML (clear any prior output)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $uri === '/api/login') {
    while (ob_get_level()) {
        ob_end_clean();
    }
}
// Strip subfolder prefix when APP_SUBDIR is set (e.g. APP_SUBDIR=/myapp).
// We deliberately avoid dirname(SCRIPT_NAME) because PHP built-in server sets
// SCRIPT_NAME to the router-script path (e.g. "public/index.php" or
// "/app/public/index.php"), which produces wrong results on Railway.
$appSubDir = rtrim((string)($_ENV['APP_SUBDIR'] ?? getenv('APP_SUBDIR') ?: ''), '/');
if ($appSubDir !== '' && strpos($uri, $appSubDir) === 0) {
    $uri = substr($uri, strlen($appSubDir));
}
// Normalize: /index.php or /index.php/ → /
$uri = preg_replace('#^/index\.php(?=/|$)#', '/', $uri);
if ($uri === '' || $uri === false) {
    $uri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

// CORS: allow Flutter web (localhost) and mobile app to call API
$isApi = (strpos($uri, '/api/') === 0);
if ($isApi && !headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: 86400');
}
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Let the router handle the current request
$router->route($uri, $method);

