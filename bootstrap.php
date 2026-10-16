<?php
/**
 * Application Bootstrap File
 * 
 * This file initializes the application environment and sets up
 * error handling, autoloading, and configuration loading.
 */

// Define application paths
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Set error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');

// Require Composer autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Load application configuration
$config = require CONFIG_PATH . '/app.php';

// Set timezone
date_default_timezone_set($config['timezone']);

// Set display errors if in debug mode
if ($config['debug']) {
    ini_set('display_errors', '1');
}

// Start session with secure settings
$sessionConfig = require CONFIG_PATH . '/session.php';
session_start([
    'cookie_lifetime' => $sessionConfig['lifetime'] * 60,
    'cookie_path' => $sessionConfig['path'],
    'cookie_domain' => $sessionConfig['domain'],
    'cookie_secure' => $sessionConfig['secure'],
    'cookie_httponly' => $sessionConfig['http_only'],
    'cookie_samesite' => $sessionConfig['same_site'],
]);

// Register error handlers
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $logFile = STORAGE_PATH . '/logs/app.log';
    $message = sprintf(
        "[%s] Error %d: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    error_log($message, 3, $logFile);
    
    if ($GLOBALS['config']['debug']) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    return true;
});

set_exception_handler(function($exception) {
    $logFile = STORAGE_PATH . '/logs/app.log';
    $message = sprintf(
        "[%s] Exception: %s in %s on line %d\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    error_log($message, 3, $logFile);
    
    if ($GLOBALS['config']['debug']) {
        echo "<pre>$message</pre>";
    } else {
        http_response_code(500);
        echo "An error occurred. Please try again later.";
    }
    exit(1);
});

// Make config globally available
$GLOBALS['config'] = $config;

return $config;
