<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\SlotFormatter;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Repositories\MemberRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use App\Services\BookingService;
use App\Services\PaymentService;

class ApiController
{
    private BookingService $bookingService;
    private PaymentService $paymentService;
    private NotificationRepository $notifRepo;
    private MemberRepository $memberRepo;
    private EventRepository $eventRepo;
    private EventRegistrationRepository $regRepo;
    private TransactionRepository $txnRepo;

    public function __construct(
        ?BookingService $bookingService = null,
        ?PaymentService $paymentService = null,
        ?NotificationRepository $notifRepo = null,
        ?MemberRepository $memberRepo = null,
        ?EventRepository $eventRepo = null,
        ?EventRegistrationRepository $regRepo = null,
        ?TransactionRepository $txnRepo = null
    ) {
        $this->bookingService = $bookingService ?? new BookingService();
        $this->paymentService = $paymentService ?? new PaymentService();
        $this->notifRepo      = $notifRepo ?? new NotificationRepository();
        $this->memberRepo     = $memberRepo ?? new MemberRepository();
        $this->eventRepo      = $eventRepo ?? new EventRepository();
        $this->regRepo        = $regRepo ?? new EventRegistrationRepository();
        $this->txnRepo        = $txnRepo ?? new TransactionRepository();
    }

    public function handleBookings(Request $request): void
    {
        Session::requireAuth();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        if ($action === 'check_in') {
            $id = $request->getInt('id');
            $this->bookingService->checkIn($id);
            Response::redirect('../index.php');
        }

        if ($action === 'create') {
            $name  = $request->getString('name');
            $slot  = $request->getInt('tee_slot');
            $tee   = $request->getInt('tee');
            $party = $request->getInt('party', 4);
            $holes = $request->getInt('holes', 18);
            $today = date('Y-m-d');

            $this->bookingService->createBooking($name, $today, $slot, $tee, $party, $holes);
            Response::redirect('../index.php');
        }

        Response::redirect('../index.php');
    }

    public function handleNotifications(Request $request): void
    {
        Session::requireAuth();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        if ($action === 'mark_read') {
            $id = $request->getInt('id');
            $this->notifRepo->markAsRead($id);
            Response::json(['ok' => true]);
        }

        if ($action === 'mark_all_read') {
            $this->notifRepo->markAllAsRead();
            Response::json(['ok' => true]);
        }

        Response::json(['error' => 'Unknown action'], 400);
    }

    public function handlePayments(Request $request): void
    {
        Session::requireAuth();
        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
        }

        $action = $request->getString('action');

        if ($action === 'refund') {
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

        $action = $request->getString('action');

        if ($action === 'export') {
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

        // Search Transactions
        $txns = $this->txnRepo->search($q, 3);
        foreach ($txns as $t) {
            $results[] = [
                'group' => 'Payments',
                'title' => $t['id'],
                'sub'   => "{$t['name']} · \${$t['amount']} · {$t['status']}",
                'url'   => "../payments.php?txnId={$t['id']}",
                'icon'  => '💳',
            ];
        }

        Response::json(['results' => $results]);
    }
}
