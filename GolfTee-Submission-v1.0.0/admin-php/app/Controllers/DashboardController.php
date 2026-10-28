<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\DashboardService;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct(?DashboardService $dashboardService = null)
    {
        $this->dashboardService = $dashboardService ?? new DashboardService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        $today = date('Y-m-d');
        $data  = $this->dashboardService->getDashboardData($today);
        $data['currentPage'] = 'index';

        View::render('dashboard', $data);
    }
}
