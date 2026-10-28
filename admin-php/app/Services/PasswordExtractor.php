<?php
declare(strict_types=1);

namespace App\Services;

/**
 * PasswordExtractor
 *
 * Centralized password extraction and validation utility.
 * Enforces strict security rules: passwords must only be received via POST/JSON body,
 * never in query string parameters.
 */
class PasswordExtractor
{
    /**
     * Extract password strictly from JSON body or POST form data.
     */
    public static function extractPassword(array $body = []): string
    {
        if (isset($body['password']) && is_string($body['password'])) {
            return (string)$body['password'];
        }

        if (isset($_POST['password']) && is_string($_POST['password'])) {
            return (string)$_POST['password'];
        }

        return '';
    }

    /**
     * Validate password complexity requirements (minimum 6 characters).
     */
    public static function validatePassword(string $password): bool
    {
        return strlen($password) >= 6;
    }
}
