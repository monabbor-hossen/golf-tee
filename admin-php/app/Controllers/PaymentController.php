<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\PaymentService;

class PaymentController
{
    private PaymentService $paymentService;

    public function __construct(?PaymentService $paymentService = null)
    {
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'create') {
                $txnId = $this->paymentService->recordPayment($request->all());
                Response::redirect('payments.php?txnId=' . urlencode($txnId));
            }

            if ($action === 'refund') {
                $id = $request->getString('id');
                $this->paymentService->refund($id);
                Response::redirect('payments.php?txnId=' . urlencode($id));
            }

            if ($action === 'mark_paid') {
                $id = $request->getString('id');
                $this->paymentService->markPaid($id);
                Response::redirect('payments.php?txnId=' . urlencode($id));
            }
        }

        $range    = $request->getString('range', 'all');
        $status   = $request->getString('status', 'All');
        $type     = $request->getString('type', 'All');
        $query    = $request->getString('q');
        $selected = $request->getString('txnId') ?: $request->getString('id');

        // CSV export
        if ($request->getString('action') === 'export') {
            $headers = ['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'];
            $rows = $this->paymentService->exportCsv($range, $status, $type, $query);
            Response::csv('payments-' . date('Y-m-d') . '.csv', $headers, $rows);
        }

        $data = $this->paymentService->listPayments($range, $status, $type, $query, $selected ?: null);
        $data['currentPage'] = 'payments';

        View::render('payments', $data);
    }
}
