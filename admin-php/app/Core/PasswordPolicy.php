<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Single source of truth for password validation policy and constraints.
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 128;

    public static function isValid(string $password, int $minLength = self::MIN_LENGTH): bool
    {
        $len = strlen($password);
        return $len >= $minLength && $len <= self::MAX_LENGTH;
    }

    public static function validate(string $password, int $minLength = self::MIN_LENGTH): bool
    {
        if (!self::isValid($password, $minLength)) {
            throw new InvalidArgumentException("Password must be at least {$minLength} characters long.");
        }
        return true;
    }
}
