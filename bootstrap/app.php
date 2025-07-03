<?php
/**
 * Application Bootstrap
 * 
 * This file initializes the application and sets up all necessary components
 */

// Start output buffering for better performance
ob_start();

// Set error reporting based on environment
if ($_ENV['APP_DEBUG'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('UTC');

// Load configuration
$config = require_once __DIR__ . '/../config/app.php';

// Initialize session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    $sessionConfig = $config['session'];
    
    ini_set('session.cookie_lifetime', $sessionConfig['lifetime'] * 60);
    ini_set('session.cookie_httponly', $sessionConfig['cookie']['http_only']);
    ini_set('session.cookie_secure', $sessionConfig['cookie']['secure']);
    ini_set('session.cookie_samesite', $sessionConfig['cookie']['same_site']);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', $sessionConfig['lifetime'] * 60);
    
    session_name($sessionConfig['cookie']['name']);
    session_start();
    
    // Regenerate session ID for security
    if ($config['security']['session_regenerate'] && !isset($_SESSION['_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['_regenerated'] = time();
    }
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Simple autoloader for classes
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Middleware/',
        __DIR__ . '/../app/Services/',
        __DIR__ . '/../app/Helpers/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper functions
function config($key, $default = null) {
    global $config;
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

function asset($path) {
    return config('app.url') . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return config('app.url') . '/' . ltrim($path, '/');
}

function storage_path($path = '') {
    return __DIR__ . '/../storage/' . ltrim($path, '/');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function back() {
    $referer = $_SERVER['HTTP_REFERER'] ?? url();
    redirect($referer);
}

function old($key, $default = '') {
    return $_SESSION['_old'][$key] ?? $default;
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Store old input data for form validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['_old'] = $_POST;
}

// Initialize application
require_once __DIR__ . '/../app/Core/App.php';
$app = new App();

return $app;