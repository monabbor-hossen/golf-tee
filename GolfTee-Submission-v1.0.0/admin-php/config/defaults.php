<?php
declare(strict_types=1);

/**
 * System default configuration settings.
 */
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'golf_admin',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',
    'APP_NAME' => 'Pine Valley Golf Club',
    'APP_URL' => 'http://localhost/golf-tee/admin-php',
    'SESSION_SECRET' => '',
    'TOKEN_FIELD_NAMES' => ['google_token', 'id_token', 'token'],
    'AUTH_MAX_ATTEMPTS' => 5,
    'AUTH_LOCKOUT_SECONDS' => 900,
];
