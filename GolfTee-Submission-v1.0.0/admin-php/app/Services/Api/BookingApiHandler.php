<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Domain\SlotFormatter;
use App\Services\Booking\BookingService;
use App\Services\Booking\SlotAvailabilityService;
use App\Services\BookingValidator;
use App\Services\OwnershipValidator;
use PDO;
use Throwable;

/**
 * Dedicated REST API Booking Handler in App\Services\Api namespace.
 * Enforces ownership checks on booking reads and mutations.
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

    public function requireBookingOwnership(int $bookingId, int $memberId): void
    {
        $stmt = $this->pdo->prepare("SELECT member_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false) {
            throw new UnauthorizedException('Booking not found', 404);
        }
        if (!OwnershipValidator::validateOwnership((int)$ownerId, $memberId, true)) {
            throw new UnauthorizedException('Unauthorized: You do not own this booking resource', 403);
        }
    }

    public function getSlots(string $date, int $holes): array
    {
        return (new SlotAvailabilityService($this->pdo))->getSlots($date, $holes);
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

    public function getBookingDetail(int $bookingId, int $memberId): ?array
    {
        $this->requireBookingOwnership($bookingId, $memberId);
        return $this->getBooking($bookingId, $memberId);
    }

    public function cancelBooking(int $bookingId, int $memberId): array
    {
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
            $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND member_id = ?");
            $stmt->execute([$bookingId, $memberId]);

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

    public function validateAndCancel(int $bookingId, int $authMemberId): array
    {
        return $this->cancelBooking($bookingId, $authMemberId);
    }

    public function joinWaitlist(int $memberId, array $body): array
    {
        $date = (string)($body['date'] ?? date('Y-m-d'));
        $slot = (int)($body['min'] ?? 480);
        $party = (int)($body['players'] ?? $body['party'] ?? 2);
        $holes = (int)($body['holes'] ?? 18);
        $name = trim((string)($body['name'] ?? 'Member'));

        $stmt = $this->pdo->prepare("SELECT id FROM waitlist WHERE member_id = ? AND tee_date = ? AND tee_slot = ? AND status = 'Waiting'");
        $stmt->execute([$memberId, $date, $slot]);
        if ($stmt->fetch()) {
            return ['status' => 'ok', 'message' => 'Already on waitlist for this slot'];
        }

        $stmt = $this->pdo->prepare("INSERT INTO waitlist (name, tee_date, tee_slot, party, holes, status, member_id) VALUES (?, ?, ?, ?, ?, 'Waiting', ?)");
        $stmt->execute([$name, $date, $slot, $party, $holes, $memberId]);

        return ['status' => 'ok', 'message' => 'Added to waitlist', 'id' => (int)$this->pdo->lastInsertId()];
    }

    public function leaveWaitlist(int $memberId, int $waitId): array
    {
        $stmt = $this->pdo->prepare("SELECT member_id FROM waitlist WHERE id = ?");
        $stmt->execute([$waitId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false) {
            return ['error' => 'Waitlist entry not found', 'code' => 404];
        }
        if (!OwnershipValidator::validateOwnership((int)$ownerId, $memberId, true)) {
            return ['error' => 'Forbidden: You do not own this waitlist resource', 'code' => 403];
        }
        $stmt = $this->pdo->prepare("DELETE FROM waitlist WHERE id = ? AND member_id = ?");
        $stmt->execute([$waitId, $memberId]);
        return ['status' => 'ok', 'message' => 'Removed from waitlist'];
    }

    public function getWaitlistEntries(int $memberId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, tee_date, tee_slot, party, holes, status, offered_slot, added_at FROM waitlist WHERE member_id = ? ORDER BY tee_date DESC");
        $stmt->execute([$memberId]);
        return ['status' => 'ok', 'waitlist' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
