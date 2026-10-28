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
use App\Repositories\WaitlistRepository;

class WaitlistService
{
    private WaitlistRepository $waitlistRepo;
    private BookingRepository $bookingRepo;
    private TransactionRepository $txnRepo;
    private ActivityRepository $activityRepo;
    private MemberRepository $memberRepo;
    private SettingRepository $settingRepo;

    public function __construct(
        ?WaitlistRepository $waitlistRepo = null,
        ?BookingRepository $bookingRepo = null,
        ?TransactionRepository $txnRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?MemberRepository $memberRepo = null,
        ?SettingRepository $settingRepo = null
    ) {
        $this->waitlistRepo = $waitlistRepo ?? new WaitlistRepository();
        $this->bookingRepo  = $bookingRepo ?? new BookingRepository();
        $this->txnRepo      = $txnRepo ?? new TransactionRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
        $this->settingRepo  = $settingRepo ?? new SettingRepository();
    }

    public function listEntries(string $tab = 'upcoming'): array
    {
        $today = date('Y-m-d');
        $rows = $this->waitlistRepo->getEntries($tab, $today);
        $memberNames = $this->memberRepo->getActiveNames();

        return [
            'rows'        => $rows,
            'tab'         => $tab,
            'today'       => $today,
            'memberNames' => $memberNames,
        ];
    }

    public function addEntry(string $name, string $date, int $slot, int $party, int $holes): int
    {
        $id = $this->waitlistRepo->add([
            'name'     => $name,
            'tee_date' => $date,
            'tee_slot' => $slot,
            'party'    => $party,
            'holes'    => $holes,
        ]);

        $this->activityRepo->log(
            'Waitlist joined',
            "{$name} · " . date('M j', strtotime($date)) . ' ' . SlotFormatter::format($slot),
            'wait'
        );

        return $id;
    }

    public function offerSlot(int $id): bool
    {
        $this->activityRepo->log('Waitlist offer sent', '', 'wait');
        return $this->waitlistRepo->updateStatus($id, 'Offered');
    }

    public function removeEntry(int $id): bool
    {
        return $this->waitlistRepo->remove($id);
    }

    public function bookFromWaitlist(int $waitlistId): bool
    {
        $entry = $this->waitlistRepo->findById($waitlistId);
        if (!$entry) {
            return false;
        }

        // Find available tee (0 or 1)
        $date = $entry['tee_date'];
        $slot = (int)$entry['tee_slot'];
        $tee = $this->bookingRepo->slotExists($date, $slot, 0) ? 1 : 0;

        $member = $this->memberRepo->findByName($entry['name']);
        $memberId = $member ? (int)$member['id'] : null;

        $this->bookingRepo->create([
            'tee_date'  => $date,
            'tee_slot'  => $slot,
            'tee'       => $tee,
            'name'      => $entry['name'],
            'party'     => (int)$entry['party'],
            'holes'     => (int)$entry['holes'],
            'status'    => 'Booked',
            'member_id' => $memberId,
        ]);

        $this->waitlistRepo->remove($waitlistId);

        // Record pending payment
        $pricingSettings = $this->settingRepo->getPricingSettings();
        $unitPrice = PricingCalculator::calculate((int)$entry['holes'], $date, $slot, $pricingSettings);
        $amount = (int)$entry['party'] * $unitPrice;
        $txnId = $this->txnRepo->generateNextId();

        $this->txnRepo->create([
            'id'          => $txnId,
            'txn_date'    => $date,
            'txn_time'    => $slot,
            'name'        => $entry['name'],
            'description' => "{$entry['holes']} holes · {$entry['party']} players",
            'type'        => 'Green fee',
            'method'      => 'Visa',
            'amount'      => $amount,
            'status'      => 'Pending',
            'ref'         => "{$date}|{$tee}-{$slot}",
        ]);

        $this->activityRepo->log('Waitlist booked', $entry['name'], 'forest');
        return true;
    }
}
