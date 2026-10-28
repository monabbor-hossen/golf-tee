<?php
declare(strict_types=1);

namespace App\Services;

/**
 * PasswordExtractor
 *
 * Centralized password extraction, validation, and field-name standardizer.
 * Enforces strict security rules: passwords must only be received via POST/JSON body,
 * never in query string parameters.
 */
class PasswordExtractor
{
    public const PASSWORD = 'password';
    public const CURRENT_PASSWORD = 'current_password';
    public const NEW_PASSWORD = 'new_password';

    /**
     * Extract password strictly from JSON body or POST form data.
     */
    public static function extractPassword(array $body = [], string $field = self::PASSWORD): string
    {
        if (isset($body[$field]) && is_string($body[$field])) {
            return trim((string)$body[$field]);
        }

        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            return trim((string)$_POST[$field]);
        }

        return '';
    }

    public static function extractCurrent(array $body = []): string
    {
        return PasswordHandler::extractAndValidate($body, self::CURRENT_PASSWORD);
    }

    public static function extractNew(array $body = []): string
    {
        return PasswordHandler::extractAndValidate($body, self::NEW_PASSWORD);
    }

    /**
     * Validate password complexity requirements (minimum 6 characters).
     */
    public static function validatePassword(string $password): bool
    {
        return strlen($password) >= 6;
    }
}
