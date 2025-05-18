<?php
/**
 * Configuration settings for Gastos Guard
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gastos_guard');

// Application settings
define('APP_NAME', 'Gastos Guard');
define('APP_URL', 'http://localhost/gastos_guard');
define('APP_VERSION', '1.0.0');

// Path settings
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDE_PATH', ROOT_PATH . '/includes');
define('ASSET_PATH', ROOT_PATH . '/assets');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Session settings (set only if no session is active to avoid warnings)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default timezone
date_default_timezone_set('Asia/Manila');
?>