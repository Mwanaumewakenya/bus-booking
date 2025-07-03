<?php
/**
 * Application Configuration
 * 
 * This file contains all application-wide configuration settings
 */

return [
    'app' => [
        'name' => 'Bus Booking System',
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'timezone' => 'UTC',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
    
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'bus_booking',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ],
    ],
    
    'session' => [
        'lifetime' => 120, // minutes
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => storage_path('sessions'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => [
            'name' => $_ENV['SESSION_COOKIE'] ?? 'bus_booking_session',
            'path' => '/',
            'domain' => $_ENV['SESSION_DOMAIN'] ?? null,
            'secure' => $_ENV['SESSION_SECURE_COOKIE'] ?? false,
            'http_only' => true,
            'same_site' => 'lax',
        ],
    ],
    
    'security' => [
        'csrf_protection' => true,
        'password_bcrypt_rounds' => 12,
        'session_regenerate' => true,
    ],
    
    'logging' => [
        'default' => 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => storage_path('logs/app.log'),
                'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
            ],
        ],
    ],
];