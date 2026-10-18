<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\BlockedSlotRepository;
use App\Repositories\BookingRepository;
use App\Repositories\MemberRepository;
use App\Services\BookingService;

class MasterGridController
{
    private BookingRepository $bookingRepo;
    private BlockedSlotRepository $blockedSlotRepo;
    private MemberRepository $memberRepo;
    private BookingService $bookingService;

    public function __construct(
        ?BookingRepository $bookingRepo = null,
        ?BlockedSlotRepository $blockedSlotRepo = null,
        ?MemberRepository $memberRepo = null,
        ?BookingService $bookingService = null
    ) {
        $this->bookingRepo     = $bookingRepo ?? new BookingRepository();
        $this->blockedSlotRepo = $blockedSlotRepo ?? new BlockedSlotRepository();
        $this->memberRepo      = $memberRepo ?? new MemberRepository();
        $this->bookingService  = $bookingService ?? new BookingService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        $offset = $request->getInt('offset', 0);
        $offset = max(-30, min(14, $offset));
        $dateStr = date('Y-m-d', strtotime("{$offset} days"));

        $dateLabel = $offset === 0 ? 'Today' : ($offset === 1 ? 'Tomorrow' : ($offset === -1 ? 'Yesterday' : date('D, M j', strtotime($dateStr))));
        $nowMin    = (int)date('H') * 60 + (int)date('i');

        // Handle POST actions
        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'book') {
                $name  = $request->getString('name');
                $slot  = $request->getInt('tee_slot');
                $tee   = $request->getInt('tee');
                $party = $request->getInt('party', 4);
                $holes = $request->getInt('holes', 18);

                $this->bookingService->createBooking($name, $dateStr, $slot, $tee, $party, $holes);
                Response::redirect("mastergrid.php?offset={$offset}");
            }

            if ($action === 'checkin') {
                $bid = $request->getInt('bid');
                $this->bookingService->checkIn($bid);
                Response::redirect("mastergrid.php?offset={$offset}");
            }

            if ($action === 'noshow') {
                $bid = $request->getInt('bid');
                $this->bookingService->markNoShow($bid);
                Response::redirect("mastergrid.php?offset={$offset}");
            }

            if ($action === 'cancel') {
                $bid = $request->getInt('bid');
                $this->bookingService->cancelBooking($bid);
                Response::redirect("mastergrid.php?offset={$offset}");
            }

            if ($action === 'block') {
                $slot   = $request->getInt('tee_slot');
                $tee    = $request->getInt('tee');
                $reason = $request->getString('reason', 'Maintenance');
                $this->blockedSlotRepo->block($dateStr, $slot, $tee, $reason);
                Response::redirect("mastergrid.php?offset={$offset}");
            }

            if ($action === 'unblock') {
                $bid = $request->getInt('block_id');
                $this->blockedSlotRepo->unblock($bid);
                Response::redirect("mastergrid.php?offset={$offset}");
            }
        }

        // Fetch bookings and blocks for the selected day
        $bookings = $this->bookingRepo->getByDate($dateStr);
        $bookingMap = [];
        foreach ($bookings as $b) {
            $bookingMap["{$b['tee']}-{$b['tee_slot']}"] = $b;
        }

        $blocks = $this->blockedSlotRepo->getByDate($dateStr);
        $blockMap = [];
        foreach ($blocks as $bl) {
            $blockMap["{$bl['tee']}-{$bl['tee_slot']}"] = $bl;
        }

        // Slots: 7:00 AM - 5:50 PM every 10 minutes
        $slots = range(420, 1070, 10);
        $tees  = ['Hole 1', 'Hole 10'];
        $focusKey = $request->getString('key');

        $activeMemberNames = $this->memberRepo->getActiveNames();

        View::render('mastergrid', [
            'currentPage'       => 'mastergrid',
            'offset'            => $offset,
            'dateStr'           => $dateStr,
            'dateLabel'         => $dateLabel,
            'nowMin'            => $nowMin,
            'slots'             => $slots,
            'tees'              => $tees,
            'focusKey'          => $focusKey,
            'bookingMap'        => $bookingMap,
            'blockMap'          => $blockMap,
            'activeMemberNames' => $activeMemberNames,
        ]);
    }
}
