<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Services\BookingApiHandler as BaseBookingHandler;
use PDO;

/**
 * Dedicated REST API Booking Handler in App\Services\Api namespace.
 * Enforces ownership checks on booking reads and mutations per Findings 3.14 & 4.6.
 */
class BookingApiHandler extends BaseBookingHandler
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
        parent::__construct($this->pdo);
    }

    /**
     * Finding 3.14 & 4.6: Explicit booking ownership verification method.
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

    /**
     * Fetch booking detail with ownership enforcement on read.
     */
    public function getBookingDetail(int $bookingId, int $memberId): ?array
    {
        $this->requireBookingOwnership($bookingId, $memberId);
        return $this->getBooking($bookingId, $memberId);
    }

    /**
     * Validate ownership before allowing mutation/cancellation.
     */
    public function validateAndCancel(int $bookingId, int $authMemberId): array
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return ['error' => 'Booking not found', 'code' => 404];
        }
        if ((int)$booking['member_id'] !== $authMemberId && !Session::isStaff()) {
            throw new UnauthorizedException('Unauthorized: Booking does not belong to authenticated member', 403);
        }
        return $this->cancelBooking($bookingId, $authMemberId);
    }
}
