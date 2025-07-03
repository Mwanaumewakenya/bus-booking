<?php

return [
    // SMTP Configuration
    'smtp' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // tls, ssl, or null
    ],

    // Default sender information
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@busbooking.com',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Bus Booking System',
    ],

    // Company information for templates
    'company' => [
        'name' => $_ENV['COMPANY_NAME'] ?? 'Swift Bus Services',
        'logo_url' => $_ENV['COMPANY_LOGO_URL'] ?? url('assets/images/logo.png'),
        'website' => $_ENV['COMPANY_WEBSITE'] ?? 'https://busbooking.com',
        'support_email' => $_ENV['SUPPORT_EMAIL'] ?? 'support@busbooking.com',
        'support_phone' => $_ENV['SUPPORT_PHONE'] ?? '+254 700 123 456',
        'address' => $_ENV['COMPANY_ADDRESS'] ?? 'Nairobi, Kenya',
    ],

    // Admin email addresses for notifications
    'admin' => [
        'emails' => explode(',', $_ENV['ADMIN_EMAILS'] ?? 'admin@busbooking.com'),
    ],

    // Test email configuration
    'test' => [
        'email' => $_ENV['TEST_EMAIL'] ?? 'test@busbooking.com',
    ],

    // Email queue settings
    'queue' => [
        'enabled' => $_ENV['EMAIL_QUEUE_ENABLED'] ?? true,
        'batch_size' => $_ENV['EMAIL_QUEUE_BATCH_SIZE'] ?? 10,
        'retry_limit' => $_ENV['EMAIL_QUEUE_RETRY_LIMIT'] ?? 3,
    ],

    // Email notification settings
    'notifications' => [
        'booking_confirmation' => $_ENV['EMAIL_BOOKING_CONFIRMATION'] ?? true,
        'payment_confirmation' => $_ENV['EMAIL_PAYMENT_CONFIRMATION'] ?? true,
        'payment_reminder' => $_ENV['EMAIL_PAYMENT_REMINDER'] ?? true,
        'booking_cancellation' => $_ENV['EMAIL_BOOKING_CANCELLATION'] ?? true,
        'admin_notifications' => $_ENV['EMAIL_ADMIN_NOTIFICATIONS'] ?? true,
    ],

    // Email template settings
    'templates' => [
        'theme_color' => $_ENV['EMAIL_THEME_COLOR'] ?? '#007bff',
        'footer_text' => $_ENV['EMAIL_FOOTER_TEXT'] ?? 'Thank you for choosing our bus service!',
    ],
];