<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\PasswordPolicy;

/**
 * Standardized password validation utility across all authentication layers.
 */
class PasswordValidator
{
    /**
     * Validate password complexity and minimum length.
     */
    public static function validate(string $password): bool
    {
        return strlen($password) >= PasswordPolicy::MIN_LENGTH;
    }

    /**
     * Assert password meets policy or throw InvalidArgumentException.
     *
     * @throws \InvalidArgumentException
     */
    public static function assertValid(string $password): void
    {
        if (!self::validate($password)) {
            throw new \InvalidArgumentException('Password must be at least ' . PasswordPolicy::MIN_LENGTH . ' characters long.');
        }
    }
}
