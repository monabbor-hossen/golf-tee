<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Domain\PricingCalculator;
use App\Domain\SlotFormatter;
use PDO;

/**
 * SlotAvailabilityService.
 * Focused service for computing tee-time slot availability, pricing, and blocked status.
 */
class SlotAvailabilityService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function getSlots(string $date, int $holes = 18): array
    {
        $stmt = $this->pdo->prepare("SELECT tee_slot FROM bookings WHERE tee_date = ? AND status != 'Cancelled'");
        $stmt->execute([$date]);
        $booked = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $this->pdo->prepare("SELECT tee_slot FROM blocked_slots WHERE tee_date = ?");
        $stmt->execute([$date]);
        $blocked = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $this->pdo->prepare("SELECT tee_slot FROM waitlist WHERE tee_date = ? AND status IN ('Waiting', 'Offered')");
        $stmt->execute([$date]);
        $waitlist = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        $isToday = ($date === date('Y-m-d'));
        $nowMinutes = (int)date('H') * 60 + (int)date('i');
        $isWeekend = in_array(date('N', strtotime($date)), ['6', '7'], true);

        $slots = [];
        for ($m = 420; $m <= 1070; $m += 10) {
            $price = PricingCalculator::calculate($holes, $isWeekend, $m);

            $state = 'available';
            if ($isToday && $m < $nowMinutes) {
                $state = 'locked';
            } elseif (isset($blocked[$m])) {
                $state = 'locked';
            } elseif (isset($booked[$m])) {
                $state = 'booked';
            } elseif (isset($waitlist[$m])) {
                $state = 'waitlist';
            }

            $slots[] = [
                'min' => $m,
                'time' => SlotFormatter::toDisplayTime($m),
                'price' => $price,
                'state' => $state,
                'tee' => $m % 20 === 0 ? 'Hole 1' : 'Hole 10'
            ];
        }

        return [
            'status' => 'ok',
            'date' => $date,
            'holes' => $holes,
            'is_weekend' => $isWeekend,
            'slots' => $slots
        ];
    }
}
