<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\WaitlistService;

class WaitlistController
{
    private WaitlistService $waitlistService;

    public function __construct(?WaitlistService $waitlistService = null)
    {
        $this->waitlistService = $waitlistService ?? new WaitlistService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'add') {
                $name  = $request->getString('name');
                $date  = $request->getString('tee_date');
                $slot  = $request->getInt('tee_slot');
                $party = $request->getInt('party', 2);
                $holes = $request->getInt('holes', 18);

                $this->waitlistService->addEntry($name, $date, $slot, $party, $holes);
                Response::redirect('waitlist.php');
            }

            if ($action === 'offer') {
                $id = $request->getInt('id');
                $this->waitlistService->offerSlot($id);
                Response::redirect('waitlist.php');
            }

            if ($action === 'book_from_wait') {
                $wid = $request->getInt('wid');
                $this->waitlistService->bookFromWaitlist($wid);
                Response::redirect('waitlist.php');
            }

            if ($action === 'remove') {
                $id = $request->getInt('id');
                $this->waitlistService->removeEntry($id);
                Response::redirect('waitlist.php');
            }

            Response::redirect('waitlist.php');
        }

        $tab = $request->getString('tab', 'upcoming');
        $data = $this->waitlistService->listEntries($tab);
        $data['currentPage'] = 'waitlist';

        View::render('waitlist', $data);
    }
}
