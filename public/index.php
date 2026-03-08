<?php

// public/index.php - The main entry point

// Display all errors in development mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define base path
define('BASE_PATH', dirname(__DIR__));

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

// Purchases
$router->get('/purchases', 'PurchaseController@index');
$router->get('/purchases/create', 'PurchaseController@create');
$router->post('/api/purchases', 'PurchaseController@store');

// Reports
$router->get('/reports', 'ReportController@index');

// Users (Admin only)
$router->get('/users',              'UserController@index');
$router->get('/users/create',       'UserController@create');
$router->post('/api/users',         'UserController@store');
$router->get('/users/edit',         'UserController@edit');
$router->post('/api/users/update',  'UserController@update');
$router->post('/api/users/password','UserController@changePassword');
$router->post('/api/users/delete',  'UserController@delete');

// Parse URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// If app is in a subfolder like 'sober', you'd need to extract just the path after it
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($uri, $baseDir) === 0 && $baseDir !== '/') {
    $uri = substr($uri, strlen($baseDir));
}
if ($uri == '') $uri = '/';

// Let the router handle the current request
$method = $_SERVER['REQUEST_METHOD'];
$router->route($uri, $method);

