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

// Define Auth Routes (public - no auth required)
$router->get('/login', 'AuthController@showLogin');
$router->post('/api/login', 'AuthController@login');
$router->post('/api/logout', 'AuthController@logout');

// Define middleware helpers
$requireAuth = function () { \App\Middleware\AuthMiddleware::requireAuth(); };
$requireAdmin = function () { \App\Middleware\AuthMiddleware::requireAdmin(); };
$requireAdminOrManager = function () { \App\Middleware\AuthMiddleware::requireAdminOrManager(); };
$requireAnyAuth = function () { \App\Middleware\AuthMiddleware::requireAuth(); }; // for cashier+ access

// Public/Auth endpoints
$router->get('/api/me', 'AuthController@me', $requireAuth);

// Basic accessible routes (any authenticated user)
$router->get('/', 'HomeController@index', $requireAuth);
$router->get('/dashboard', 'DashboardController@index', $requireAuth);
$router->get('/api/dashboard', 'DashboardController@indexApi', $requireAuth);
$router->get('/pos', 'PosController@index', $requireAuth);
$router->get('/api/pos/products', 'PosController@products', $requireAuth);
$router->post('/api/pos/complete', 'PosController@complete', $requireAuth);

// Products - viewing/searching allowed for all authenticated, CRUD only admin/manager
$router->get('/products', 'ProductController@index', $requireAuth);
$router->get('/api/products', 'ProductController@indexApi', $requireAuth);
$router->get('/api/products/low-stock', 'ProductController@lowStockApi', $requireAuth);
$router->get('/api/products/search', 'ProductController@search', $requireAuth);
$router->get('/api/products/barcode', 'ProductController@barcode', $requireAuth);
$router->get('/barcode-scan', 'ProductController@barcodeScanPage', $requireAuth);
$router->post('/api/barcode-push', 'ProductController@barcodePush', $requireAuth);
$router->get('/api/barcode-last', 'ProductController@barcodeLast', $requireAuth);

// Product CRUD - admin/manager only
$router->get('/products/create', 'ProductController@create', $requireAdminOrManager);
$router->post('/api/products', 'ProductController@store', $requireAdminOrManager);
$router->get('/products/edit', 'ProductController@edit', $requireAdminOrManager);
$router->post('/api/products/update', 'ProductController@update', $requireAdminOrManager);
$router->post('/api/products/delete', 'ProductController@delete', $requireAdminOrManager);

// Categories - viewing allowed, CRUD admin/manager
$router->get('/categories', 'CategoryController@index', $requireAuth);
$router->get('/categories/create', 'CategoryController@create', $requireAdminOrManager);
$router->post('/api/categories', 'CategoryController@store', $requireAdminOrManager);
$router->get('/categories/edit', 'CategoryController@edit', $requireAdminOrManager);
$router->post('/api/categories/update', 'CategoryController@update', $requireAdminOrManager);
$router->post('/api/categories/delete', 'CategoryController@delete', $requireAdminOrManager);

// Types - viewing allowed, CRUD admin/manager
$router->get('/types', 'TypeController@index', $requireAuth);
$router->get('/types/create', 'TypeController@create', $requireAdminOrManager);
$router->post('/api/types', 'TypeController@store', $requireAdminOrManager);
$router->get('/types/edit', 'TypeController@edit', $requireAdminOrManager);
$router->post('/api/types/update', 'TypeController@update', $requireAdminOrManager);
$router->post('/api/types/delete', 'TypeController@delete', $requireAdminOrManager);

// Sales - allowed for admin, manager, cashier
$router->get('/sales', 'SaleController@index', $requireAuth);
$router->get('/api/sales', 'SaleController@indexApi', $requireAuth);
$router->get('/api/sales/details', 'SaleController@showApi', $requireAuth);
$router->get('/sales/create', 'SaleController@create', $requireAuth);
$router->get('/sales/receipt', 'SaleController@receipt', $requireAuth);
$router->post('/api/sales', 'SaleController@store', $requireAuth);
$router->post('/api/sales/cancel', 'SaleController@cancel', $requireAdminOrManager);

// Purchases - admin/manager only
$router->get('/purchases', 'PurchaseController@index', $requireAdminOrManager);
$router->get('/api/purchases/list', 'PurchaseController@indexApi', $requireAdminOrManager);
$router->get('/purchases/create', 'PurchaseController@create', $requireAdminOrManager);
$router->post('/api/purchases', 'PurchaseController@store', $requireAdminOrManager);
$router->post('/api/purchases/delete', 'PurchaseController@delete', $requireAdminOrManager);

// Returns - allowed for admin, manager, cashier
$router->get('/returns', 'ReturnController@index', $requireAuth);
$router->get('/api/returns', 'ReturnController@indexApi', $requireAuth);
$router->get('/returns/create', 'ReturnController@create', $requireAuth);
$router->post('/api/returns', 'ReturnController@store', $requireAuth);

// Expenses - admin/manager only
$router->get('/expenses', 'ExpenseController@index', $requireAdminOrManager);
$router->get('/api/expenses/list', 'ExpenseController@indexApi', $requireAdminOrManager);
$router->get('/expenses/create', 'ExpenseController@create', $requireAdminOrManager);
$router->get('/expenses/edit', 'ExpenseController@edit', $requireAdminOrManager);
$router->post('/api/expenses', 'ExpenseController@store', $requireAdminOrManager);
$router->post('/api/expenses/update', 'ExpenseController@update', $requireAdminOrManager);
$router->post('/api/expenses/delete', 'ExpenseController@delete', $requireAdminOrManager);

// Reports - admin/manager only
$router->get('/reports', 'ReportController@index', $requireAdminOrManager);
$router->get('/api/reports/data', 'ReportController@indexApi', $requireAdminOrManager);
$router->get('/api/reports/pnl', 'ReportController@profitAndLoss', $requireAdminOrManager);
$router->get('/reports/export/sales', 'ReportController@exportSalesCsv', $requireAdminOrManager);
$router->get('/reports/export/products', 'ReportController@exportTopProductsCsv', $requireAdminOrManager);

// Activity log - admin only
$router->get('/activity-log', 'ActivityLogController@index', $requireAdmin);
$router->get('/api/activity-log/list', 'ActivityLogController@indexApi', $requireAdmin);

// Settings - admin only
$router->get('/settings', 'SettingsController@index', $requireAdmin);
$router->get('/api/settings/data', 'SettingsController@getApi', $requireAdmin);
$router->get('/api/settings/check-uploads', 'SettingsController@checkUploads', $requireAdmin);
$router->post('/api/settings', 'SettingsController@store', $requireAdmin);

// Users - admin only
$router->get('/users', 'UserController@index', $requireAdmin);
$router->get('/api/users/list', 'UserController@indexApi', $requireAdmin);
$router->get('/users/create', 'UserController@create', $requireAdmin);
$router->post('/api/users', 'UserController@store', $requireAdmin);
$router->get('/users/edit', 'UserController@edit', $requireAdmin);
$router->post('/api/users/update', 'UserController@update', $requireAdmin);
$router->post('/api/users/password', 'UserController@changePassword', $requireAdmin);
$router->post('/api/users/delete', 'UserController@delete', $requireAdmin);

// Categories API - all authenticated
$router->get('/api/categories', 'CategoryController@indexApi', $requireAuth);
// Types API - all authenticated
$router->get('/api/types', 'TypeController@indexApi', $requireAuth);

// Profile - authenticated users (view own profile)
$router->get('/profile', 'ProfileController@index', $requireAuth);
$router->post('/api/profile/password', 'ProfileController@updatePassword', $requireAuth);

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

// CORS: Web يحتاج Origin محدد + Credentials حتى يُرسل كوكي الجلسة بعد /api/login
$isApi = (strpos($uri, '/api/') === 0);
if ($isApi && !headers_sent()) {
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $allowCredentials = false;
    if ($origin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i', $origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        $allowCredentials = true;
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: ' . (86400 * 7));
    if ($allowCredentials) {
        header('Access-Control-Allow-Credentials: true');
    }
}
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Let the router handle the current request
$router->route($uri, $method);