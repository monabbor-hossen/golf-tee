<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Repositories\MemberRepository;
use App\Repositories\NewsRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use App\Services\BookingRateLimiter;
use App\Services\BookingService;
use App\Services\PaymentService;

/**
 * ApiController
 *
 * Architecture Note: ApiController handles internal Web Admin AJAX mutations
 * using session-based authentication and CSRF token validation.
 * MobileApiController serves the cross-platform Flutter Mobile REST API
 * using token-based Bearer authentication. Both controllers delegate business
 * logic to the shared Services layer (BookingService, PaymentService, etc.).
 */
class ApiController extends BaseApiController
{
    private BookingService $bookingService;
    private PaymentService $paymentService;
    private NotificationRepository $notifRepo;
    private EventRepository $eventRepo;
    private EventRegistrationRepository $regRepo;
    private TransactionRepository $txnRepo;
    private NewsRepository $newsRepo;

    public function __construct(
        ?BookingService $bookingService = null,
        ?PaymentService $paymentService = null,
        ?NotificationRepository $notifRepo = null,
        ?MemberRepository $memberRepo = null,
        ?EventRepository $eventRepo = null,
        ?EventRegistrationRepository $regRepo = null,
        ?TransactionRepository $txnRepo = null,
        ?NewsRepository $newsRepo = null
    ) {
        parent::__construct(null, $memberRepo);
        $this->bookingService = $bookingService ?? new BookingService();
        $this->paymentService = $paymentService ?? new PaymentService();
        $this->notifRepo      = $notifRepo ?? new NotificationRepository();
        $this->eventRepo      = $eventRepo ?? new EventRepository();
        $this->regRepo        = $regRepo ?? new EventRegistrationRepository();
        $this->txnRepo        = $txnRepo ?? new TransactionRepository();
        $this->newsRepo       = $newsRepo ?? new NewsRepository();
    }

    public function handleBookings(Request $request): void
    {
        Session::requireStaff();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        match ($action) {
            'check_in' => $this->actionCheckIn($request),
            'create'   => $this->actionCreate($request),
            'cancel'   => $this->actionCancel($request),
            'waitlist' => $this->actionWaitlist($request),
            default    => Response::redirect('../index.php'),
        };
    }

    private function actionCheckIn(Request $request): void
    {
        if (!Session::hasRole('staff') && Session::getRoleLevel() < 1) {
            Response::forbidden();
            return;
        }
        $id = $request->getInt('id');
        $booking = $this->bookingService->getBooking($id);
        if (!$booking) {
            Response::notFound();
            return;
        }
        if (!Session::isStaff() && (int)$booking['member_id'] !== Session::getUserId()) {
            Response::forbidden();
            return;
        }
        $this->bookingService->checkIn($id);
        Response::redirect('../index.php');
    }

    private function actionCreate(Request $request): void
    {
        if (Session::getRoleLevel() < Session::getRoleLevel('manager')) {
            Response::forbidden('Manager role required');
            return;
        }

        $memberId = $request->getInt('member_id');
        if (BookingRateLimiter::isLimited($memberId)) {
            throw new \Exception('Too many bookings');
        }
        if ($memberId > 0) {
            $member = $this->memberRepo->getById($memberId);
            if (!$member || strtolower((string)$member['status']) !== 'active') {
                throw new \Exception('Invalid member');
            }
        }

        $name  = $request->getString('name');
        $slot  = $request->getInt('tee_slot');
        $tee   = $request->getInt('tee');
        $party = $request->getInt('party', 4);
        $holes = $request->getInt('holes', 18);
        $today = date('Y-m-d');
        $staffId = Session::getUserId();

        $this->bookingService->createBooking($name, $today, $slot, $tee, $party, $holes, 'Booked', $memberId > 0 ? $memberId : null, $staffId);
        Response::redirect('../index.php');
    }

    private function actionCancel(Request $request): void
    {
        $id = $request->getInt('id');
        $booking = $this->bookingService->getBooking($id);
        if (!$booking) {
            Response::notFound();
            return;
        }
        if (!Session::isStaff() && (int)$booking['member_id'] !== Session::getUserId()) {
            Response::forbidden();
            return;
        }
        $this->bookingService->cancelBooking($id);
        Response::redirect('../index.php');
    }

    private function actionWaitlist(Request $request): void
    {
        $id = $request->getInt('id');
        Response::redirect('../waitlist.php');
    }

    public function handleNotifications(Request $request): void
    {
        // Ownership check on member_id
        $memberId = $request->getInt('member_id');
        if ($memberId > 0) {
            $authId = Session::getUserId();
            if ($authId !== $memberId && !Session::isStaff()) {
                Response::forbidden();
                return;
            }
        }
        $handler = new \App\Services\NotificationAdminHandler($this->notifRepo);
        $handler->handle($request);
    }

    public function handlePayments(Request $request): void
    {
        Session::requireStaff();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        // Ownership validation on action=history
        if ($action === 'history') {
            $memberId = $request->getInt('member_id');
            $authId = Session::getUserId();
            if ($authId !== $memberId && !Session::isStaff()) {
                Response::forbidden();
                return;
            }
            $history = $this->txnRepo->getByMemberId($memberId);
            Response::json(['history' => $history]);
            return;
        }

        if ($action === 'refund') {
            if (Session::getRoleLevel() < Session::getRoleLevel('manager')) {
                Response::forbidden();
                return;
            }

            $id = $request->getString('id');
            $this->paymentService->refund($id);
            Response::redirect('../payments.php?txnId=' . urlencode($id));
        }

        if ($action === 'mark_paid') {
            $id = $request->getString('id');
            $this->paymentService->markPaid($id);
            Response::redirect('../payments.php?txnId=' . urlencode($id));
        }

        if ($action === 'create') {
            $txnId = $this->paymentService->recordPayment($request->all());
            Response::redirect('../payments.php?txnId=' . urlencode($txnId));
        }

        if ($action === 'export') {
            $headers = ['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'];
            $rows = $this->paymentService->exportCsv('all', 'All', 'All');
            Response::csv('payments-' . date('Y-m-d') . '.csv', $headers, $rows);
        }

        Response::redirect('../payments.php');
    }

    public function handleReports(Request $request): void
    {
        Session::requireAuth();
        if (Session::getRoleLevel() < Session::getRoleLevel('manager')) {
            Response::json(['error' => 'Forbidden - Manager role required'], 403);
            return;
        }

        $action = $request->getString('range', $request->getString('action'));

        // Role-level checks within each report action
        if ($action === 'financial' || $request->getString('action') === 'financial' || $action === 'export' || $request->getString('action') === 'export') {
            if (Session::getRoleLevel() < Session::getRoleLevel('manager')) {
                Response::forbidden('Only managers can view financial reports');
                return;
            }
            $range = $request->getString('range', '30');
            $headers = ['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'];
            $rows = $this->paymentService->exportCsv($range, 'Paid', 'All');
            Response::csv('report-' . date('Y-m-d') . '.csv', $headers, $rows);
            return;
        }

        Response::json(['error' => 'Unknown action'], 400);
    }

    public function handleSearch(Request $request): void
    {
        Session::requireAuth();

        $q = trim($request->getString('q'));
        if ($q === '') {
            Response::json(['results' => []]);
        }

        $results = [];

        // Search Pages
        $pageMap = [
            'Dashboard'   => ['url' => '../index.php',     'icon' => '📊'],
            'Master Grid' => ['url' => '../mastergrid.php', 'icon' => '📋'],
            'Waitlist'    => ['url' => '../waitlist.php',   'icon' => '⏰'],
            'Events'      => ['url' => '../events.php',     'icon' => '🏆'],
            'Members'     => ['url' => '../members.php',    'icon' => '👥'],
            'Payments'    => ['url' => '../payments.php',   'icon' => '💳'],
            'Reports'     => ['url' => '../reports.php',    'icon' => '📈'],
            'Settings'    => ['url' => '../settings.php',   'icon' => '⚙️'],
            'Club News'   => ['url' => '../news.php',       'icon' => '📢'],
        ];

        foreach ($pageMap as $name => $info) {
            if (stripos($name, $q) !== false) {
                $results[] = [
                    'group' => 'Pages',
                    'title' => $name,
                    'sub'   => 'Go to page',
                    'url'   => $info['url'],
                    'icon'  => $info['icon'],
                ];
            }
        }

        // Search Members
        $members = $this->memberRepo->search($q, 4);
        foreach ($members as $m) {
            $results[] = [
                'group' => 'Members',
                'title' => $m['name'],
                'sub'   => "{$m['tier']} · {$m['status']} · {$m['email']}",
                'url'   => "../members.php?memberId={$m['id']}",
                'icon'  => '👤',
            ];
        }

        // Search Events
        $events = $this->eventRepo->search($q, 3);
        foreach ($events as $e) {
            $cnt = $this->regRepo->getCount((int)$e['id']);
            $results[] = [
                'group' => 'Events',
                'title' => $e['name'],
                'sub'   => date('M j', strtotime($e['event_date'])) . " · {$cnt}/{$e['capacity']} registered",
                'url'   => "../events.php?id={$e['id']}",
                'icon'  => '🏆',
            ];
        }

        // Search News
        $news = $this->newsRepo->search($q, 3);
        foreach ($news as $art) {
            $results[] = [
                'group' => 'Club News',
                'title' => $art['title'],
                'sub'   => "{$art['category']} · " . date('M j', strtotime($art['published_at'])),
                'url'   => "../news.php?id={$art['id']}",
                'icon'  => '📢',
            ];
        }

        // Search Transactions
        $txns = $this->txnRepo->search($q, 3);
        foreach ($txns as $t) {
            $results[] = [
                'group' => 'Payments',
                'title' => $t['id'],
                'sub'   => "{$t['name']} · " . currencySymbol() . "{$t['amount']} · {$t['status']}",
                'url'   => "../payments.php?txnId={$t['id']}",
                'icon'  => '💳',
            ];
        }

        Response::json(['results' => $results]);
    }
}
