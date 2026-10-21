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

            try {
                if ($action === 'add') {
                    $newId = $this->memberService->addMember($request->all());
                    Session::setFlash('success', 'Member added successfully and password setup email dispatched!');
                    Response::redirect('members.php?memberId=' . $newId . '&msg=invite_sent');
                }

                if ($action === 'edit') {
                    $id = $request->getInt('id');
                    $this->memberService->updateMember($id, $request->all());
                    Session::setFlash('success', 'Member details updated successfully.');
                    Response::redirect('members.php?memberId=' . $id);
                }

                if ($action === 'approve') {
                    $id = $request->getInt('id');
                    $this->memberService->approveMember($id);
                    Session::setFlash('success', 'Member approved.');
                    Response::redirect('members.php?memberId=' . $id);
                }

                if ($action === 'suspend') {
                    $id = $request->getInt('id');
                    $this->memberService->suspendMember($id);
                    Session::setFlash('success', 'Member suspended.');
                    Response::redirect('members.php?memberId=' . $id);
                }

                if ($action === 'resend_invite') {
                    $id = $request->getInt('id');
                    $this->memberService->resendInvitation($id);
                    Session::setFlash('success', 'Password setup invitation re-sent successfully!');
                    Response::redirect('members.php?memberId=' . $id . '&msg=invite_sent');
                }

                if ($action === 'delete') {
                    $id = $request->getInt('id');
                    $this->memberService->deleteMember($id);
                    Session::setFlash('success', 'Member deleted.');
                    Response::redirect('members.php');
                }
            } catch (\InvalidArgumentException $e) {
                Session::setFlash('error', $e->getMessage());
                Response::redirect('members.php');
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
                    Session::setFlash('error', 'A member with this email address already exists.');
                } else {
                    Session::setFlash('error', 'Database error: ' . $e->getMessage());
                }
                Response::redirect('members.php');
            } catch (\Throwable $e) {
                Session::setFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
                Response::redirect('members.php');
            }

            Response::redirect('members.php');
        }

        $query    = $request->getString('q');
        $tier     = $request->getString('tier', 'All');
        $status   = $request->getString('status', 'All');
        $spend    = $request->getString('spend', 'All');
        $sort     = $request->getString('sort', 'name');
        
        $rawDir   = $request->getString('dir');
        if ($rawDir === '') {
            $dir = in_array($sort, ['spend', 'rounds'], true) ? 'desc' : 'asc';
        } else {
            $dir = strtolower($rawDir) === 'desc' ? 'desc' : 'asc';
        }
        $selected = $request->getInt('memberId') ?: $request->getInt('id');

        // CSV Export
        if ($request->getString('export') === 'csv') {
            $headers = ['ID', 'Name', 'Email', 'Phone', 'Tier', 'Status', 'Handicap', 'Rounds', 'Spend', 'Joined', 'Last Play'];
            $rows = $this->memberService->exportCsv($query, $tier, $status, $spend, $sort, $dir);
            Response::csv('members-' . date('Y-m-d') . '.csv', $headers, $rows);
        }

        $data = $this->memberService->listMembers($query, $tier, $status, $sort, $dir, $selected ?: null, $spend);
        $data['currentPage']  = 'members';
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');

        View::render('members', $data);
    }
}
