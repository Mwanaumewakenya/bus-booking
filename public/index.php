<?php
/**
 * Main Application Entry Point
 * 
 * This file bootstraps and runs the application
 */

// Load environment variables if available
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Bootstrap the application
require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$app->run();