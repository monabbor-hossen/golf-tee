<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Centralized conflict checker for tee-time slot availability.
 * Used by web admin (ApiController / BookingService) and mobile API (BookingApiHandler / MobileApiController).
 */
class BookingConflictChecker
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Check if a tee slot is already booked or blocked.
     */
    public function hasConflict(string $date, int $slot, int $tee = 0): bool
    {
        // 1. Check existing active bookings
        $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE tee_date = ? AND tee_slot = ? AND tee = ? AND status != 'Cancelled'");
        $stmt->execute([$date, $slot, $tee]);
        if ($stmt->fetch()) {
            return true;
        }

        // 2. Check blocked slots (maintenance or private events)
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM blocked_slots WHERE tee_date = ? AND tee_slot = ? AND (tee = ? OR tee = 0)");
            $stmt->execute([$date, $slot, $tee]);
            if ($stmt->fetch()) {
                return true;
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Find next available alternative slot if conflict exists.
     */
    public function findNextAvailable(string $date, int $preferredSlot, int $tee = 0): ?int
    {
        for ($s = $preferredSlot; $s <= 1080; $s += 10) {
            if (!$this->hasConflict($date, $s, $tee)) {
                return $s;
            }
        }
        return null;
    }
}
