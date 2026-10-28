<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Enforces per-member tee time booking rate limits, delegating to unified RateLimiter.
 */
class BookingRateLimiter
{
    /**
     * Check if member has exceeded allowed bookings within the current time window.
     */
    public static function isLimited(int $memberId, int $maxPerHour = 10): bool
    {
        return RateLimiter::isLimited('booking', $memberId, $maxPerHour, 3600);
    }
}
