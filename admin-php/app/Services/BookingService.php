<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\PricingCalculator;
use App\Domain\SlotFormatter;
use App\Repositories\ActivityRepository;
use App\Repositories\BookingRepository;
use App\Repositories\MemberRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TransactionRepository;

class BookingService
{
    private BookingRepository $bookingRepo;
    private MemberRepository $memberRepo;
    private TransactionRepository $txnRepo;
    private SettingRepository $settingRepo;
    private ActivityRepository $activityRepo;

    public function __construct(
        ?BookingRepository $bookingRepo = null,
        ?MemberRepository $memberRepo = null,
        ?TransactionRepository $txnRepo = null,
        ?SettingRepository $settingRepo = null,
        ?ActivityRepository $activityRepo = null
    ) {
        $this->bookingRepo  = $bookingRepo ?? new BookingRepository();
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
        $this->txnRepo      = $txnRepo ?? new TransactionRepository();
        $this->settingRepo  = $settingRepo ?? new SettingRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
    }

    public function createBooking(
        string $name,
        string $date,
        int $slot,
        int $tee,
        int $party,
        int $holes,
        string $status = 'Booked',
        ?int $explicitMemberId = null,
        ?int $staffId = null
    ): ?int {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        // Check if slot already taken via centralized BookingConflictChecker
        $conflictChecker = new BookingConflictChecker();
        if ($conflictChecker->hasConflict($date, $slot, $tee)) {
            return null;
        }

        // Check member
        $memberId = $explicitMemberId;
        if ($memberId === null) {
            $member = $this->memberRepo->findByName($name);
            $memberId = $member ? (int)$member['id'] : null;
        }

        $bookingId = $this->bookingRepo->create([
            'tee_date'  => $date,
            'tee_slot'  => $slot,
            'tee'       => $tee,
            'name'      => $name,
            'party'     => $party,
            'holes'     => $holes,
            'status'    => $status,
            'member_id' => $memberId,
        ]);

        // Calculate pricing
        $pricingSettings = $this->settingRepo->getPricingSettings();
        $unitPrice = PricingCalculator::calculate($holes, $date, $slot, $pricingSettings);
        $amount = $party * $unitPrice;

        // Create transaction
        $txnId = $this->txnRepo->generateNextId();
        $ref = "{$date}|{$tee}-{$slot}";
        $this->txnRepo->create([
            'id'          => $txnId,
            'txn_date'    => $date,
            'txn_time'    => $slot,
            'name'        => $name,
            'description' => "{$holes} holes · {$party} players · " . SlotFormatter::format($slot),
            'type'        => 'Green fee',
            'method'      => $memberId ? 'Member account' : 'Visa',
            'amount'      => $amount,
            'status'      => 'Paid',
            'ref'         => $ref,
        ]);

        $this->activityRepo->log(
            'New booking',
            "{$name} · " . date('M j', strtotime($date)) . ' ' . SlotFormatter::format($slot) . " · {$party} players",
            'forest'
        );

        // Audit Finding 3.9: Add activity log entry when staff creates booking on behalf of member
        $effectiveStaffId = $staffId ?? \App\Core\Session::getUserId();
        if ($effectiveStaffId !== null && $effectiveStaffId !== $memberId) {
            $this->activityRepo->log(
                'Administrative booking created',
                "Staff #{$effectiveStaffId} created booking on behalf of member " . ($memberId ? "#{$memberId} ({$name})" : $name),
                'sand'
            );
        }

        return $bookingId;
    }

    public function getBooking(int $bookingId): ?array
    {
        return $this->bookingRepo->findById($bookingId);
    }

    public function checkIn(int $bookingId): bool
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return false;
        }

        $this->bookingRepo->updateStatus($bookingId, 'Checked in');

        $this->activityRepo->log(
            'Checked in',
            "{$booking['name']} · " . SlotFormatter::format((int)$booking['tee_slot']) . ' · players',
            'forest'
        );

        return true;
    }

    public function markNoShow(int $bookingId): bool
    {
        return $this->bookingRepo->updateStatus($bookingId, 'No-show');
    }

    public function cancelBooking(int $bookingId): bool
    {
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return false;
        }

        $this->bookingRepo->delete($bookingId);

        // Refund linked transaction if present
        $ref = "{$booking['tee_date']}|{$booking['tee']}-{$booking['tee_slot']}";
        $this->txnRepo->updateStatusByRef($ref, 'Refunded');

        $this->activityRepo->log(
            'Cancellation',
            "{$booking['name']} · " . date('M j', strtotime($booking['tee_date'])) . ' ' . SlotFormatter::format((int)$booking['tee_slot']),
            'danger'
        );

        return true;
    }
}
