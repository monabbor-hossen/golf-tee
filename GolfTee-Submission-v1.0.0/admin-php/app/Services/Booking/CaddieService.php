<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\SlotFormatter;
use App\Repositories\ActivityRepository;
use App\Repositories\CaddieRepository;
use App\Repositories\MemberRepository;

class CaddieService
{
    private CaddieRepository $caddieRepo;
    private MemberRepository $memberRepo;
    private ActivityRepository $activityRepo;

    public function __construct(
        ?CaddieRepository $caddieRepo = null,
        ?MemberRepository $memberRepo = null,
        ?ActivityRepository $activityRepo = null
    ) {
        $this->caddieRepo  = $caddieRepo ?? new CaddieRepository();
        $this->memberRepo  = $memberRepo ?? new MemberRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
    }

    // ──────────────────────────────────────────────
    // Caddie Management
    // ──────────────────────────────────────────────

    public function listPage(string $tab, string $query, string $sort, string $dir, int $selectedId = 0): array
    {
        $status = match($tab) {
            'available'   => 'Available',
            'unavailable' => 'Unavailable',
            'on_leave'    => 'On Leave',
            default       => 'All',
        };

        $caddies      = $this->caddieRepo->getAll($status, $query, $sort, $dir);
        $statusCounts = $this->caddieRepo->getStatusCounts();
        $total        = $this->caddieRepo->getTotalCount();
        $members      = $this->memberRepo->getAllNames();

        $todayBookings = $this->caddieRepo->getTodayBookings();
        $upcomingBookings = $this->caddieRepo->getUpcomingBookings(10);
        $revenue       = $this->caddieRepo->getRevenueTotal();
        $bookingCounts = $this->caddieRepo->getBookingCountsByStatus();

        $selected = $selectedId > 0 ? $this->caddieRepo->findById($selectedId) : null;
        $selectedBookings = $selectedId > 0
            ? $this->caddieRepo->getBookings('All', '', '', $selectedId)
            : [];

        return compact(
            'caddies', 'statusCounts', 'total', 'members',
            'todayBookings', 'upcomingBookings', 'revenue', 'bookingCounts',
            'selected', 'selectedBookings', 'tab', 'query', 'sort', 'dir'
        );
    }

    public function addCaddie(array $data): int
    {
        $id = $this->caddieRepo->create([
            'name'             => trim($data['name'] ?? ''),
            'phone'            => trim($data['phone'] ?? ''),
            'experience_years' => (int)($data['experience_years'] ?? 0),
            'specialty'        => trim($data['specialty'] ?? ''),
            'rate_per_round'   => (float)($data['rate_per_round'] ?? 0),
            'rating'           => min(5.0, max(0.0, (float)($data['rating'] ?? 0))),
            'status'           => in_array($data['status'] ?? '', ['Available','Unavailable','On Leave']) ? $data['status'] : 'Available',
            'notes'            => trim($data['notes'] ?? ''),
        ]);

        $this->activityRepo->log('Caddie added', trim($data['name'] ?? 'Unknown') . ' registered as caddie', 'forest');
        return $id;
    }

    public function updateCaddie(int $id, array $data): bool
    {
        $result = $this->caddieRepo->update($id, [
            'name'             => trim($data['name'] ?? ''),
            'phone'            => trim($data['phone'] ?? ''),
            'experience_years' => (int)($data['experience_years'] ?? 0),
            'specialty'        => trim($data['specialty'] ?? ''),
            'rate_per_round'   => (float)($data['rate_per_round'] ?? 0),
            'rating'           => min(5.0, max(0.0, (float)($data['rating'] ?? 0))),
            'status'           => in_array($data['status'] ?? '', ['Available','Unavailable','On Leave']) ? $data['status'] : 'Available',
            'notes'            => trim($data['notes'] ?? ''),
        ]);

        if ($result) {
            $this->activityRepo->log('Caddie updated', trim($data['name'] ?? '') . ' profile updated', 'gray');
        }
        return $result;
    }

    public function deleteCaddie(int $id): bool
    {
        $caddie = $this->caddieRepo->findById($id);
        $result = $this->caddieRepo->delete($id);
        if ($result && $caddie) {
            $this->activityRepo->log('Caddie removed', ($caddie['name'] ?? '') . ' removed from roster', 'danger');
        }
        return $result;
    }

    // ──────────────────────────────────────────────
    // Booking Management
    // ──────────────────────────────────────────────

    public function listBookingsPage(string $status, string $dateFrom, string $dateTo, int $caddieId = 0): array
    {
        $bookings = $this->caddieRepo->getBookings($status, $dateFrom, $dateTo, $caddieId);
        $caddies  = $this->caddieRepo->getAll('Available');
        $allCaddies = $this->caddieRepo->getAll();
        $counts   = $this->caddieRepo->getBookingCountsByStatus();
        $members  = $this->memberRepo->getAllNames();
        $revenue  = $this->caddieRepo->getRevenueTotal();
        return compact('bookings', 'caddies', 'allCaddies', 'counts', 'members', 'revenue', 'status', 'dateFrom', 'dateTo', 'caddieId');
    }

    public function createBooking(array $data): int|false
    {
        $caddieId = (int)($data['caddie_id'] ?? 0);
        $date     = $data['booking_date'] ?? '';
        $slot     = (int)($data['tee_slot'] ?? 0);

        if (!$caddieId || !$date || !$slot) {
            return false;
        }

        // Conflict check
        if ($this->caddieRepo->isBookingConflict($caddieId, $date, $slot)) {
            return false;
        }

        $caddie = $this->caddieRepo->findById($caddieId);
        $fee    = $caddie ? (float)$caddie['rate_per_round'] : (float)($data['fee'] ?? 0);

        // Try to match member
        $golferName = trim($data['golfer_name'] ?? '');
        $member     = $this->memberRepo->findByName($golferName);
        $memberId   = $member ? (int)$member['id'] : null;

        $id = $this->caddieRepo->createBooking([
            'caddie_id'    => $caddieId,
            'golfer_name'  => $golferName,
            'member_id'    => $memberId,
            'booking_date' => $date,
            'tee_slot'     => $slot,
            'tee'          => (int)($data['tee'] ?? 0),
            'holes'        => (int)($data['holes'] ?? 18),
            'party'        => (int)($data['party'] ?? 1),
            'fee'          => $fee,
            'status'       => 'Confirmed',
            'notes'        => trim($data['notes'] ?? ''),
        ]);

        $this->activityRepo->log(
            'Caddie booked',
            ($caddie['name'] ?? '') . ' · ' . $golferName . ' · ' . date('M j', strtotime($date)) . ' ' . SlotFormatter::format($slot),
            'forest'
        );

        return $id;
    }

    public function updateBooking(int $id, array $data): bool
    {
        $existing = $this->caddieRepo->findBookingById($id);
        if (!$existing) return false;

        $caddieId = (int)($data['caddie_id'] ?? $existing['caddie_id']);
        $date     = $data['booking_date'] ?? $existing['booking_date'];
        $slot     = (int)($data['tee_slot'] ?? $existing['tee_slot']);

        // Conflict check (exclude self)
        if ($this->caddieRepo->isBookingConflict($caddieId, $date, $slot, $id)) {
            return false;
        }

        $golferName = trim($data['golfer_name'] ?? $existing['golfer_name']);
        $member     = $this->memberRepo->findByName($golferName);

        $result = $this->caddieRepo->updateBooking($id, [
            'caddie_id'    => $caddieId,
            'golfer_name'  => $golferName,
            'member_id'    => $member ? (int)$member['id'] : null,
            'booking_date' => $date,
            'tee_slot'     => $slot,
            'tee'          => (int)($data['tee'] ?? $existing['tee']),
            'holes'        => (int)($data['holes'] ?? $existing['holes']),
            'party'        => (int)($data['party'] ?? $existing['party']),
            'fee'          => (float)($data['fee'] ?? $existing['fee']),
            'status'       => in_array($data['status'] ?? '', ['Confirmed','Completed','Cancelled','No-show']) ? $data['status'] : $existing['status'],
            'notes'        => trim($data['notes'] ?? $existing['notes']),
        ]);

        if ($result) {
            $this->activityRepo->log('Caddie booking updated', "Booking #{$id} updated", 'gray');
        }
        return $result;
    }

    public function cancelBooking(int $id): bool
    {
        $booking = $this->caddieRepo->findBookingById($id);
        if (!$booking) return false;

        $result = $this->caddieRepo->updateBookingStatus($id, 'Cancelled');
        if ($result) {
            $this->activityRepo->log(
                'Caddie booking cancelled',
                ($booking['caddie_name'] ?? '') . ' · ' . ($booking['golfer_name'] ?? '') . ' · ' . date('M j', strtotime($booking['booking_date'] ?? date('Y-m-d'))),
                'danger'
            );
        }
        return $result;
    }

    public function completeBooking(int $id): bool
    {
        $booking = $this->caddieRepo->findBookingById($id);
        if (!$booking) return false;

        $result = $this->caddieRepo->updateBookingStatus($id, 'Completed');
        if ($result) {
            $this->activityRepo->log('Caddie booking completed', ($booking['caddie_name'] ?? '') . ' · ' . ($booking['golfer_name'] ?? ''), 'forest');
        }
        return $result;
    }

    public function deleteBooking(int $id): bool
    {
        return $this->caddieRepo->deleteBooking($id);
    }

    public function getAvailableCaddiesForDate(string $date): array
    {
        return $this->caddieRepo->getAvailableOnDate($date);
    }
}
