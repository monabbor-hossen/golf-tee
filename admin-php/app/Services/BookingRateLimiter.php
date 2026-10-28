<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Enforces per-member tee time booking rate limits to prevent automated booking spam.
 */
class BookingRateLimiter
{
    /**
     * Check if member has exceeded allowed bookings within the current time window.
     */
    public static function isLimited(int $memberId, int $maxPerHour = 10): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        try {
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$memberId]);
            return (int)$stmt->fetchColumn() >= $maxPerHour;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
