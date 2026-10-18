<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\PricingCalculator;
use App\Repositories\ActivityRepository;
use App\Repositories\BlockedSlotRepository;
use App\Repositories\BookingRepository;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Repositories\MemberRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WaitlistRepository;

class DashboardService
{
    private BookingRepository $bookingRepo;
    private BlockedSlotRepository $blockedSlotRepo;
    private TransactionRepository $txnRepo;
    private MemberRepository $memberRepo;
    private WaitlistRepository $waitlistRepo;
    private EventRepository $eventRepo;
    private EventRegistrationRepository $regRepo;
    private ActivityRepository $activityRepo;
    private SettingRepository $settingRepo;

    public function __construct(
        ?BookingRepository $bookingRepo = null,
        ?BlockedSlotRepository $blockedSlotRepo = null,
        ?TransactionRepository $txnRepo = null,
        ?MemberRepository $memberRepo = null,
        ?WaitlistRepository $waitlistRepo = null,
        ?EventRepository $eventRepo = null,
        ?EventRegistrationRepository $regRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?SettingRepository $settingRepo = null
    ) {
        $this->bookingRepo     = $bookingRepo ?? new BookingRepository();
        $this->blockedSlotRepo = $blockedSlotRepo ?? new BlockedSlotRepository();
        $this->txnRepo         = $txnRepo ?? new TransactionRepository();
        $this->memberRepo      = $memberRepo ?? new MemberRepository();
        $this->waitlistRepo    = $waitlistRepo ?? new WaitlistRepository();
        $this->eventRepo       = $eventRepo ?? new EventRepository();
        $this->regRepo         = $regRepo ?? new EventRegistrationRepository();
        $this->activityRepo    = $activityRepo ?? new ActivityRepository();
        $this->settingRepo     = $settingRepo ?? new SettingRepository();
    }

    public function getDashboardData(string $today): array
    {
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
        $dayName = date('l', strtotime($today));
        $nowMin = (int)date('H') * 60 + (int)date('i');
        $nowHour = (int)date('H');

        // Bookings today
        $todayBookings = $this->bookingRepo->getTodayBookingsWithAmount($today);
        $totalPlayers = array_sum(array_column($todayBookings, 'party'));

        $pricingSettings = $this->settingRepo->getPricingSettings();
        $totalRevenue = 0.0;
        foreach ($todayBookings as $b) {
            if (!empty($b['amount'])) {
                $totalRevenue += (float)$b['amount'];
            } else {
                $unitPrice = PricingCalculator::calculate((int)$b['holes'], $today, (int)$b['tee_slot'], $pricingSettings);
                $totalRevenue += (int)$b['party'] * $unitPrice;
            }
        }

        // Utilization: 2 tees * 66 slots = 132 slots
        $totalSlots = 2 * 66;
        $blockedCount = $this->blockedSlotRepo->getCountByDate($today);
        $usableSlots = $totalSlots - $blockedCount;
        $bookedCount = count($todayBookings);
        $utilization = $usableSlots > 0 ? (int)round(($bookedCount / $usableSlots) * 100) : 0;

        // Yesterday's revenue for comparison
        $yStats = $this->txnRepo->getStatusStats('yesterday', $today, $yesterday);
        $yRev = (float)($yStats['Paid']['total'] ?? 0);
        $revDelta = $yRev > 0 ? round((($totalRevenue - $yRev) / $yRev) * 100, 1) : 0.0;

        // Waitlist counts
        $waitlistCount = $this->waitlistRepo->getUpcomingCount($today);
        $todayWaitCount = $this->waitlistRepo->getTodayCount($today);

        // Checked in count
        $checkedInCount = count(array_filter($todayBookings, fn($b) => ($b['status'] ?? '') === 'Checked in'));

        // Starting soon
        $soonMin = $nowMin - 30;
        $soonBookings = $this->bookingRepo->getStartingSoon($today, $soonMin, 7);

        // Late bookings
        $lateBookings = $this->bookingRepo->getLateBookings($today, $nowMin, 5);

        // Hourly breakdown
        $hours = range(7, 17);
        $byHour = array_fill_keys($hours, 0);
        foreach ($todayBookings as $b) {
            $h = intdiv((int)$b['tee_slot'], 60);
            if (isset($byHour[$h])) {
                $byHour[$h] += (int)$b['party'];
            }
        }
        $maxH = max(1, ...array_values($byHour));

        // Attention items
        $pendingMembers = $this->memberRepo->getPendingMembers(5);
        $offeredWaitlist = $this->waitlistRepo->getOfferedEntries();
        $pendingPayInfo = $this->txnRepo->getPendingCountAndSum();

        $nextEvent = $this->eventRepo->getNextEvent($today);
        $nextEventReg = $nextEvent ? $this->regRepo->getCount((int)$nextEvent['id']) : 0;

        // Recent activity
        $activities = $this->activityRepo->getRecent(7);

        // Active members names for booking modal datalist
        $memberNames = $this->memberRepo->getActiveNames();

        // Booked slots map for today (to avoid N+1 queries when building time options)
        $bookedSlots = $this->bookingRepo->getBookedSlotsMap($today);

        return [
            'today'           => $today,
            'dayName'         => $dayName,
            'nowMin'          => $nowMin,
            'nowHour'         => $nowHour,
            'totalRevenue'    => $totalRevenue,
            'revDelta'        => $revDelta,
            'totalPlayers'    => $totalPlayers,
            'todayBookings'   => $todayBookings,
            'checkedInCount'  => $checkedInCount,
            'utilization'     => $utilization,
            'waitlistCount'   => $waitlistCount,
            'todayWaitCount'  => $todayWaitCount,
            'soonBookings'    => $soonBookings,
            'lateBookings'    => $lateBookings,
            'byHour'          => $byHour,
            'maxH'            => $maxH,
            'pendingMembers'  => $pendingMembers,
            'offeredWaitlist' => $offeredWaitlist,
            'pendingPayCount' => $pendingPayInfo['count'],
            'pendingPaySum'   => $pendingPayInfo['sum'],
            'nextEvent'       => $nextEvent,
            'nextEventReg'    => $nextEventReg,
            'activities'      => $activities,
            'memberNames'     => $memberNames,
            'bookedSlots'     => $bookedSlots,
        ];
    }
}
