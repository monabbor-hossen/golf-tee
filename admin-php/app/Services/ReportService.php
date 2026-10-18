<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\MemberRepository;
use App\Repositories\TransactionRepository;

class ReportService
{
    private TransactionRepository $txnRepo;
    private BookingRepository $bookingRepo;
    private MemberRepository $memberRepo;

    public function __construct(
        ?TransactionRepository $txnRepo = null,
        ?BookingRepository $bookingRepo = null,
        ?MemberRepository $memberRepo = null
    ) {
        $this->txnRepo     = $txnRepo ?? new TransactionRepository();
        $this->bookingRepo = $bookingRepo ?? new BookingRepository();
        $this->memberRepo  = $memberRepo ?? new MemberRepository();
    }

    public function getReportData(string $range = '30'): array
    {
        $days = (int)$range;
        if ($days <= 0) $days = 30;

        $today = date('Y-m-d');
        $fromDate = date('Y-m-d', strtotime("-{$days} days"));

        $revByType = $this->txnRepo->getRevenueByType($fromDate);
        $totalRev = array_sum(array_column($revByType, 'total'));

        $dailyMap = $this->txnRepo->getDailyRevenue($fromDate);
        $maxRev = max(1, ...array_values($dailyMap ?: [1]));

        $memberStats = $this->memberRepo->getStatusCounts();
        $totalMembers = $this->memberRepo->getTotalCount();

        $bookStats = $this->bookingRepo->getStatsByDateRange($fromDate);
        $totalBookings = array_sum(array_values($bookStats ?: []));

        $topSpend = $this->memberRepo->getTopSpenders($fromDate, 5);

        $byDow = $this->bookingRepo->getBookingsByDayOfWeek($fromDate);
        $maxDow = max(1, ...array_values($byDow ?: [1]));

        return [
            'range'         => (string)$days,
            'fromDate'      => $fromDate,
            'today'         => $today,
            'revByType'     => $revByType,
            'totalRev'      => $totalRev,
            'dailyMap'      => $dailyMap,
            'maxRev'        => $maxRev,
            'memberStats'   => $memberStats,
            'totalMembers'  => $totalMembers,
            'bookStats'     => $bookStats,
            'totalBookings' => $totalBookings,
            'topSpend'      => $topSpend,
            'byDow'         => $byDow,
            'maxDow'        => $maxDow,
        ];
    }
}
