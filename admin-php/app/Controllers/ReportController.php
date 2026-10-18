<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\PaymentService;
use App\Services\ReportService;

class ReportController
{
    private ReportService $reportService;
    private PaymentService $paymentService;

    public function __construct(
        ?ReportService $reportService = null,
        ?PaymentService $paymentService = null
    ) {
        $this->reportService  = $reportService ?? new ReportService();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        $range = $request->getString('range', '30');

        // CSV export
        if ($request->getString('action') === 'export') {
            $headers = ['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'];
            $rows = $this->paymentService->exportCsv('all', 'Paid', 'All');
            Response::csv('report-' . date('Y-m-d') . '.csv', $headers, $rows);
        }

        $data = $this->reportService->getReportData($range);
        $data['currentPage'] = 'reports';

        View::render('reports', $data);
    }
}
