<?php
/**
 * Razor Library - Configuration
 *
 * Copy this file to config.local.php and modify for local settings.
 * config.local.php is gitignored and will override these defaults.
 */

return [
    // Application
    'APP_NAME' => 'Razor Library',
    'APP_URL' => 'http://localhost',
    'APP_BASE_PATH' => '', // Set to '/razor-library' for subdirectory installs
    'APP_DEBUG' => false,

    // Database
    'DB_PATH' => __DIR__ . '/data/razor_library.db',

    // Uploads
    'UPLOAD_PATH' => __DIR__ . '/uploads',
    'UPLOAD_MAX_SIZE' => 10 * 1024 * 1024, // 10MB
    'IMAGE_MAX_DIMENSION' => 1200,
    'THUMBNAIL_SIZE' => 300,
    'ALLOWED_IMAGE_TYPES' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],

    // Session
    'SESSION_NAME' => 'razor_library_session',
    'SESSION_LIFETIME' => 86400, // 24 hours

    // Security
    'PASSWORD_MIN_LENGTH' => 8,
    'CSRF_TOKEN_NAME' => 'csrf_token',
    'RATE_LIMIT_LOGIN_ATTEMPTS' => 5,
    'RATE_LIMIT_LOGIN_WINDOW' => 900, // 15 minutes
    'RATE_LIMIT_RESET_REQUESTS' => 3,
    'RATE_LIMIT_RESET_WINDOW' => 3600, // 1 hour

    // Password Reset
    'RESET_TOKEN_EXPIRY' => 3600, // 1 hour

    // Email
    'MAIL_FROM' => 'noreply@example.com',
    'MAIL_FROM_NAME' => 'Razor Library',
    'SMTP_HOST' => '',
    'SMTP_PORT' => 587,
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
    'SMTP_SECURE' => 'tls', // 'tls' or 'ssl'
];
