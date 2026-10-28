<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\EventRepository;
use App\Repositories\MemberRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WaitlistRepository;

class LayoutService
{
    private static ?array $cachedLayoutData = null;

    public static function getLayoutData(): array
    {
        if (self::$cachedLayoutData !== null) {
            return self::$cachedLayoutData;
        }

        $today = date('Y-m-d');
        $settingRepo = new SettingRepository();
        $waitlistRepo = new WaitlistRepository();
        $eventRepo = new EventRepository();
        $memberRepo = new MemberRepository();
        $txnRepo = new TransactionRepository();
        $notifRepo = new NotificationRepository();

        $courseName = $settingRepo->get('course_name', 'Pine Valley Golf Club');
        $waitlistCount = $waitlistRepo->getUpcomingCount($today);
        $eventCount = $eventRepo->getUpcomingCount($today);
        $pendingMemberCount = $memberRepo->getPendingCount();
        $pendingTxnCount = $txnRepo->getPendingCountAndSum()['count'];
        $unreadNotifCount = $notifRepo->getUnreadCount();
        $recentNotifs = $notifRepo->getRecent(20);

        self::$cachedLayoutData = [
            'courseName'         => $courseName,
            'waitlistCount'      => $waitlistCount,
            'eventCount'         => $eventCount,
            'pendingMemberCount' => $pendingMemberCount,
            'pendingTxnCount'    => $pendingTxnCount,
            'unreadNotifCount'   => $unreadNotifCount,
            'recentNotifs'       => $recentNotifs,
            'staffName'          => Session::getStaffName(),
            'staffRole'          => Session::getStaffRole(),
        ];

        return self::$cachedLayoutData;
    }
}
