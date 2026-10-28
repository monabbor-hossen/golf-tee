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

        if ($action === 'check_in') {
            Session::requireStaff();
            if (!Session::hasRole('staff')) {
                Response::forbidden();
                return;
            }
            $id = $request->getInt('id');
            $booking = $this->bookingService->getBooking($id);
            if (!$booking) {
                Response::notFound();
                return;
            }
            // Finding 3.10: ownership validation on booking access
            if (!Session::isStaff() && (int)$booking['member_id'] !== Session::getUserId()) {
                Response::forbidden();
                return;
            }
            $this->bookingService->checkIn($id);
            Response::redirect('../index.php');
        }

        if ($action === 'create') {
            // Finding 3.7: Creating bookings on behalf of members requires elevated privileges
            if (!Session::hasRole('manager') && !Session::hasRole('admin') && !Session::hasRole('owner')) {
                Response::forbidden('Manager role required');
                return;
            }

            $memberId = $request->getInt('member_id');
            // Finding 3.11: Rate limiting per member to prevent spam bookings
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

        Response::redirect('../index.php');
    }

    public function handleNotifications(Request $request): void
    {
        $handler = new \App\Services\NotificationAdminHandler($this->notifRepo);
        $handler->handle($request);
    }

    public function handlePayments(Request $request): void
    {
        Session::requireStaff();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        if ($action === 'refund') {
            // Finding 3.12: Refunds require manager or admin role
            if (!Session::hasRole('manager') && !Session::hasRole('admin') && !Session::hasRole('owner')) {
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
        if (!Session::hasRole('manager') && !Session::hasRole('owner') && !Session::hasRole('admin')) {
            Response::json(['error' => 'Forbidden - Manager role required'], 403);
            return;
        }

        $action = $request->getString('range', $request->getString('action'));

        if ($action === 'export' || $request->getString('action') === 'export') {
            $range = $request->getString('range', '30');
            $headers = ['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'];
            $rows = $this->paymentService->exportCsv($range, 'Paid', 'All');
            Response::csv('report-' . date('Y-m-d') . '.csv', $headers, $rows);
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
