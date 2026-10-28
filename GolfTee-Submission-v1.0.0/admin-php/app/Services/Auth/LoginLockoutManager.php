<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Service for managing brute force login lockout, delegating to unified RateLimiter (Findings 5.14 & 5.15).
 */
class LoginLockoutManager
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 900; // 15 minutes

    public static function isLockedOut(?string $ip = null): bool
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return RateLimiter::isLimited('login', $clientIp, self::MAX_ATTEMPTS, self::LOCKOUT_SECONDS);
    }

    public function getRemainingMinutes(?string $ip = null): int
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return RateLimiter::getRemainingMinutes('login', $clientIp);
    }

    public static function recordFailedAttempt(?string $ip = null): void
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        RateLimiter::recordAttempt('login', $clientIp);
    }

    public static function clearAttempts(?string $ip = null): void
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        RateLimiter::clear('login', $clientIp);
    }
}
