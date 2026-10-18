<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\MemberService;

class MemberController
{
    private MemberService $memberService;

    public function __construct(?MemberService $memberService = null)
    {
        $this->memberService = $memberService ?? new MemberService();
    }

    public function index(Request $request): void
    {
        Session::requireAuth();

        // Handle POST actions
        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'add') {
                $this->memberService->addMember($request->all());
                Response::redirect('members.php');
            }

            if ($action === 'edit') {
                $id = $request->getInt('id');
                $this->memberService->updateMember($id, $request->all());
                Response::redirect('members.php?memberId=' . $id);
            }

            if ($action === 'approve') {
                $id = $request->getInt('id');
                $this->memberService->approveMember($id);
                Response::redirect('members.php?memberId=' . $id);
            }

            if ($action === 'suspend') {
                $id = $request->getInt('id');
                $this->memberService->suspendMember($id);
                Response::redirect('members.php?memberId=' . $id);
            }

            if ($action === 'delete') {
                $id = $request->getInt('id');
                $this->memberService->deleteMember($id);
                Response::redirect('members.php');
            }
        }

        $query    = $request->getString('q');
        $tier     = $request->getString('tier', 'All');
        $status   = $request->getString('status', 'All');
        $sort     = $request->getString('sort', 'name');
        $dir      = $request->getString('dir', 'asc');
        $selected = $request->getInt('memberId') ?: $request->getInt('id');

        // CSV Export
        if ($request->getString('export') === 'csv') {
            $headers = ['ID', 'Name', 'Email', 'Phone', 'Tier', 'Status', 'Handicap', 'Rounds', 'Spend', 'Joined', 'Last Play'];
            $rows = $this->memberService->exportCsv($query, $tier, $status);
            Response::csv('members-' . date('Y-m-d') . '.csv', $headers, $rows);
        }

        $data = $this->memberService->listMembers($query, $tier, $status, $sort, $dir, $selected ?: null);
        $data['currentPage'] = 'members';

        View::render('members', $data);
    }
}
