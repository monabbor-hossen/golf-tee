<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CaddieService;

class CaddieController
{
    private CaddieService $caddieService;

    public function __construct(?CaddieService $caddieService = null)
    {
        $this->caddieService = $caddieService ?? new CaddieService();
    }

    public function index(Request $request): void
    {
        Session::requireStaff();
        \App\Services\AuthorizationMiddleware::requireRole(['staff', 'pro', 'manager', 'admin', 'owner'], 'index.php');

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            // ── Caddie CRUD ──────────────────────
            if ($action === 'add_caddie') {
                $this->caddieService->addCaddie($request->all());
                Response::redirect('caddies.php?tab=all');
            }

            if ($action === 'edit_caddie') {
                $id = $request->getInt('id');
                $this->caddieService->updateCaddie($id, $request->all());
                Response::redirect('caddies.php?tab=all&selected=' . $id);
            }

            if ($action === 'delete_caddie') {
                \App\Services\AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);
                $id = $request->getInt('id');
                $this->caddieService->deleteCaddie($id);
                Response::redirect('caddies.php?tab=all');
            }

            // ── Booking CRUD ──────────────────────
            if ($action === 'add_booking') {
                $id = $this->caddieService->createBooking($request->all());
                if ($id === false) {
                    Response::redirect('caddies.php?tab=bookings&error=conflict');
                } else {
                    Response::redirect('caddies.php?tab=bookings&booking_id=' . $id);
                }
            }

            if ($action === 'edit_booking') {
                $id = $request->getInt('id');
                $ok = $this->caddieService->updateBooking($id, $request->all());
                if (!$ok) {
                    Response::redirect('caddies.php?tab=bookings&error=conflict&booking_id=' . $id);
                } else {
                    Response::redirect('caddies.php?tab=bookings&booking_id=' . $id);
                }
            }

            if ($action === 'cancel_booking') {
                $id = $request->getInt('id');
                $this->caddieService->cancelBooking($id);
                Response::redirect('caddies.php?tab=bookings');
            }

            if ($action === 'complete_booking') {
                $id = $request->getInt('id');
                $this->caddieService->completeBooking($id);
                Response::redirect('caddies.php?tab=bookings');
            }

            if ($action === 'delete_booking') {
                $id = $request->getInt('id');
                $this->caddieService->deleteBooking($id);
                Response::redirect('caddies.php?tab=bookings');
            }

            Response::redirect('caddies.php');
        }

        // ── GET ───────────────────────────────────
        $tab        = $request->getString('tab', 'all');
        $query      = $request->getString('q', '');
        $sort       = $request->getString('sort', 'name');
        $dir        = $request->getString('dir', 'asc');
        $selectedId = $request->getInt('selected');
        $bookingId  = $request->getInt('booking_id');
        $error      = $request->getString('error');

        // Date filters for bookings tab
        $dateFrom = $request->getString('date_from', '');
        $dateTo   = $request->getString('date_to', '');
        $statusFilter = $request->getString('status', 'All');
        $caddieFilter = $request->getInt('caddie_id');

        if ($tab === 'bookings') {
            $data = $this->caddieService->listBookingsPage($statusFilter, $dateFrom, $dateTo, $caddieFilter);
            $data['selectedBookingId'] = $bookingId;
        } else {
            $data = $this->caddieService->listPage($tab, $query, $sort, $dir, $selectedId);
        }

        $data['currentPage'] = 'caddies';
        $data['tab']         = $tab;
        $data['error']       = $error;

        View::render('caddies', $data);
    }
}
