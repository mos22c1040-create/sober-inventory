<?php
/**
 * Vercel serverless entry point.
 * All requests are routed here; BASE_PATH is set so the app runs from project root.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../public/index.php';
