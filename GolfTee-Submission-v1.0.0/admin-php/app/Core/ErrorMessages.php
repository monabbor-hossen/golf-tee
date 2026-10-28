<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Standardized API and authentication error messages.
 */
class ErrorMessages
{
    public const AUTH_REQUIRED         = 'Authentication required.';
    public const INVALID_CREDENTIALS   = 'Invalid email or password.';
    public const FORBIDDEN             = 'Access denied.';
    public const NOT_FOUND             = 'Requested resource not found.';
    public const TOKEN_EXPIRED         = 'Token has expired.';
    public const TOKEN_INVALID         = 'Invalid or malformed token.';
    public const RATE_LIMIT_EXCEEDED   = 'Rate limit exceeded. Please try again later.';
    public const PASSWORD_TOO_SHORT    = 'Password too short. Must be at least 8 characters.';
    public const CSRF_INVALID          = 'Invalid or missing CSRF token.';
    public const ACCOUNT_SUSPENDED     = 'Your membership account is currently suspended.';
}
