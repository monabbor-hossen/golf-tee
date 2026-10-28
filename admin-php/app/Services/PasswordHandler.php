<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\PasswordPolicy;
use InvalidArgumentException;

/**
 * Centralized password processing service: extraction, validation, and secure hashing.
 * Delegates validation directly to PasswordPolicy per Finding 5.6.
 * Supports custom field names per Finding 5.14.
 */
class PasswordHandler
{
    public const MIN_LENGTH = PasswordPolicy::MIN_LENGTH;

    /**
     * Finding 5.14: Extract password by field name (password, current_password, new_password).
     */
    public static function extract(array $body = [], string $field = 'password'): string
    {
        if (isset($body[$field]) && is_string($body[$field])) {
            return trim($body[$field]);
        }

        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            return trim($_POST[$field]);
        }

        return '';
    }

    /**
     * Finding 5.6: Delegate validation directly to PasswordPolicy as single source of truth.
     */
    public static function validate(string $password, int $minLength = self::MIN_LENGTH): bool
    {
        return PasswordPolicy::validate($password, $minLength);
    }

    /**
     * Generate secure bcrypt hash for password.
     */
    public static function hash(string $password): string
    {
        self::validate($password);
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify a plaintext password against a stored hash.
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
