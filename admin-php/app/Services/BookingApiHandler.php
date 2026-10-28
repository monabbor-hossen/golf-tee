<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Domain\SlotFormatter;
use PDO;
use Throwable;

/**
 * Dedicated handler for mobile booking and waitlist operations.
 * Enforces ownership checks and delegates to domain services.
 */
class BookingApiHandler
{
    private PDO $pdo;
    private ?BookingService $bookingService;

    public function __construct(?PDO $pdo = null, ?BookingService $bookingService = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
        $this->bookingService = $bookingService;
    }

    private function getBookingService(): BookingService
    {
        if ($this->bookingService === null) {
            $this->bookingService = new BookingService();
        }
        return $this->bookingService;
    }

    /**
     * Finding 3.14 & 4.6: Strict ownership validation for booking read and mutation operations.
     */
    private function requireBookingOwnership(int $bookingId, int $memberId): void
    {
        $stmt = $this->pdo->prepare("SELECT member_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false || ((int)$ownerId !== $memberId && !Session::isStaff())) {
            throw new UnauthorizedException('Unauthorized: You do not own this booking resource', 403);
        }
    }

    public function getSlots(string $date, int $holes): array
    {
        $stmt = $this->pdo->prepare("SELECT tee_slot, tee FROM bookings WHERE tee_date = ?");
        $stmt->execute([$date]);
        $booked = [];
        while ($row = $stmt->fetch()) {
            $booked[(int)$row['tee_slot']] = true;
        }

        $stmt = $this->pdo->prepare("SELECT tee_slot FROM blocked_slots WHERE tee_date = ?");
        $stmt->execute([$date]);
        $blocked = [];
        while ($row = $stmt->fetch()) {
            $blocked[(int)$row['tee_slot']] = true;
        }

        $stmt = $this->pdo->prepare("SELECT tee_slot FROM waitlist WHERE tee_date = ?");
        $stmt->execute([$date]);
        $waitlist = [];
        while ($row = $stmt->fetch()) {
            $waitlist[(int)$row['tee_slot']] = true;
        }

        $isToday = ($date === date('Y-m-d'));
        $nowMinutes = (int)date('H') * 60 + (int)date('i');
        $isWeekend = in_array(date('N', strtotime($date)), ['6', '7'], true);

        $slots = [];
        for ($m = 420; $m <= 1070; $m += 10) {
            $price = $this->calculatePrice($holes, $isWeekend, $m);

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

    public function createBooking(int $memberId, array $body): array
    {
        $val = BookingValidator::validate($body);
        if (!$val['valid']) {
            return ['error' => implode(' ', $val['errors']), 'code' => 400];
        }

        $date = $body['date'] ?? date('Y-m-d');
        $slot = (int)($body['min'] ?? 480);
        $holes = (int)($body['holes'] ?? 18);
        $players = (int)($body['players'] ?? 2);
        $tee = (int)($body['tee'] ?? ($slot % 20 === 0 ? 0 : 1));
        $name = trim((string)($body['name'] ?? 'Member'));
        $partners = $body['partners'] ?? [];
        $addons = $body['addons'] ?? ['cart'];
        $total = (float)($body['total'] ?? 170.00);
        $card = $body['card'] ?? 'v';

        $notes = json_encode([
            'partners' => $partners,
            'addons' => $addons,
            'card' => $card,
            'total' => $total
        ]);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE tee_date = ? AND tee_slot = ? AND tee = ?");
            $stmt->bindValue(1, $date, PDO::PARAM_STR);
            $stmt->bindValue(2, $slot, PDO::PARAM_INT);
            $stmt->bindValue(3, $tee, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                $slot += 10;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (tee_date, tee_slot, tee, name, party, holes, status, notes, member_id)
                VALUES (?, ?, ?, ?, ?, ?, 'Booked', ?, ?)
            ");
            $stmt->bindValue(1, $date, PDO::PARAM_STR);
            $stmt->bindValue(2, $slot, PDO::PARAM_INT);
            $stmt->bindValue(3, $tee, PDO::PARAM_INT);
            $stmt->bindValue(4, $name, PDO::PARAM_STR);
            $stmt->bindValue(5, $players, PDO::PARAM_INT);
            $stmt->bindValue(6, $holes, PDO::PARAM_INT);
            $stmt->bindValue(7, $notes, PDO::PARAM_STR);
            $stmt->bindValue(8, $memberId, PDO::PARAM_INT);
            $stmt->execute();
            $bookingId = (int)$this->pdo->lastInsertId();

            $paymentMethod = 'Credit Card';
            if (!empty($card)) {
                $cStmt = $this->pdo->prepare("SELECT brand, last4 FROM member_cards WHERE id = ? AND member_id = ?");
                $cStmt->execute([(int)$card, $memberId]);
                $foundCard = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($foundCard) {
                    $paymentMethod = $foundCard['brand'] . ' *' . $foundCard['last4'];
                }
            }

            $txnId = 'TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $nowMin = (int)date('H') * 60 + (int)date('i');
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref, member_id, booking_id)
                VALUES (?, ?, ?, ?, ?, 'Green fee', ?, ?, 'Paid', ?, ?, ?)
            ");
            $stmt->execute([
                $txnId,
                $date,
                $nowMin,
                $name,
                "Tee time reservation ($holes holes, $players players)",
                $paymentMethod,
                $total,
                (string)$bookingId,
                $memberId,
                $bookingId
            ]);

            $stmt = $this->pdo->prepare("DELETE FROM waitlist WHERE member_id = ? AND tee_date = ? AND tee_slot = ?");
            $stmt->execute([$memberId, $date, $slot]);

            $stmt = $this->pdo->prepare("UPDATE members SET rounds = rounds + 1, spend = spend + ? WHERE id = ?");
            $stmt->execute([$total, $memberId]);

            $this->pdo->commit();

            return [
                'status' => 'ok',
                'message' => 'Booking confirmed successfully',
                'booking' => [
                    'id' => $bookingId,
                    'date' => $date,
                    'min' => $slot,
                    'time' => SlotFormatter::toDisplayTime($slot),
                    'holes' => $holes,
                    'players' => $players,
                    'partners' => $partners,
                    'addons' => $addons,
                    'total' => $total,
                    'card' => $card,
                    'tee' => $tee === 0 ? 'Hole 1' : 'Hole 10',
                    'status' => 'upcoming'
                ]
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['error' => 'Booking failed: ' . $e->getMessage(), 'code' => 500];
        }
    }

    public function getBooking(int $bookingId, ?int $memberId = null): ?array
    {
        if ($memberId !== null) {
            $this->requireBookingOwnership($bookingId, $memberId);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function cancelBooking(int $bookingId, int $memberId): array
    {
        // Finding 4.6: Verify SELECT member_id FROM bookings WHERE id = ? matches auth token
        $this->requireBookingOwnership($bookingId, $memberId);

        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $b = $stmt->fetch();
        if (!$b) {
            return ['error' => 'Booking not found', 'code' => 404];
        }
        if ((int)$b['member_id'] !== $memberId && !Session::isStaff()) {
            throw new UnauthorizedException('Forbidden: Not your booking', 403);
        }

        $extra = !empty($b['notes']) ? json_decode($b['notes'], true) : [];
        $total = (float)($extra['total'] ?? 170.00);
        $daysDiff = (strtotime($b['tee_date']) - strtotime(date('Y-m-d'))) / 86400;
        $refundAmount = ($daysDiff >= 1) ? $total : round($total / 2, 2);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);

            $txnId = 'REF-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $nowMin = (int)date('H') * 60 + (int)date('i');
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref, member_id, booking_id)
                VALUES (?, CURDATE(), ?, ?, 'Refund: Cancelled Tee Time', 'Green fee', 'Credit Card', ?, 'Refunded', ?, ?, ?)
            ");
            $stmt->execute([
                $txnId,
                $nowMin,
                $b['name'] ?? 'Member',
                -$refundAmount,
                (string)$bookingId,
                $memberId,
                $bookingId
            ]);

            $this->pdo->commit();

            return [
                'status' => 'ok',
                'message' => 'Booking cancelled and refund processed',
                'refund_amount' => $refundAmount
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['error' => 'Cancel failed: ' . $e->getMessage(), 'code' => 500];
        }
    }

    private function calculatePrice(int $holes, bool $isWeekend, int $min): int
    {
        if ($min >= 900) {
            return ($holes === 18) ? 55 : 35;
        }
        if ($isWeekend) {
            return ($holes === 18) ? 110 : 62;
        }
        return ($holes === 18) ? 85 : 48;
    }
}
