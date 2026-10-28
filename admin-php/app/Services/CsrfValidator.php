<?php
declare(strict_types=1);

namespace App\Services;

/**
 * CsrfValidator
 *
 * Centralized CSRF token verification using timing-attack-safe hash_equals().
 */
class CsrfValidator
{
    /**
     * Constant-time comparison between stored session token and submitted token.
     */
    public static function validateToken(?string $storedToken, ?string $submittedToken): bool
    {
        if (empty($storedToken) || empty($submittedToken)) {
            return false;
        }

        return hash_equals($storedToken, $submittedToken);
    }
}
