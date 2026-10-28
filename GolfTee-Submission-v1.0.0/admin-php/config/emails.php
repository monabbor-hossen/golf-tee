<?php
declare(strict_types=1);

/**
 * Centralized email subject lines and template configuration.
 */
return [
    'password_reset' => [
        'subject' => 'Reset Your Pine Valley Golf Club Password',
        'template' => __DIR__ . '/../views/emails/password_reset.php',
    ],
    'invitation' => [
        'subject' => 'Welcome to Pine Valley Golf Club — Set Up Your Account',
        'template' => __DIR__ . '/../views/emails/invitation.php',
    ],
];
