<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthorizationMiddleware;

/**
 * MemberActionHandler
 * Extracts member management POST actions into a dedicated handler class.
 */
class MemberActionHandler
{
    private MemberService $memberService;

    public function __construct(?MemberService $memberService = null)
    {
        $this->memberService = $memberService ?? new MemberService();
    }

    public function handle(Request $request): void
    {
        Session::validateRequestCsrf($request);
        $action = $request->getString('action');

        match ($action) {
            'add'           => $this->add($request),
            'edit'          => $this->edit($request),
            'approve'       => $this->approve($request),
            'suspend'       => $this->suspend($request),
            'resend_invite' => $this->resendInvite($request),
            'delete'        => $this->delete($request),
            default         => Response::redirect('members.php'),
        };
    }

    public function add(Request $request): void
    {
        $email = trim((string)$request->post('email', ''));
        if ($this->memberService->emailExists($email)) {
            throw new \Exception('Email already registered');
        }
        $newId = $this->memberService->addMember($request->all());
        Session::setFlash('success', 'Member added successfully and password setup email dispatched!');
        Response::redirect('members.php?memberId=' . $newId . '&msg=invite_sent');
    }

    public function edit(Request $request): void
    {
        $id = $request->getInt('id');
        $this->memberService->updateMember($id, $request->all());
        Session::setFlash('success', 'Member details updated successfully.');
        Response::redirect('members.php?memberId=' . $id);
    }

    public function approve(Request $request): void
    {
        $id = $request->getInt('id');
        $this->memberService->approveMember($id);
        Session::setFlash('success', 'Member approved.');
        Response::redirect('members.php?memberId=' . $id);
    }

    public function suspend(Request $request): void
    {
        $id = $request->getInt('id');
        $this->memberService->suspendMember($id);
        Session::setFlash('success', 'Member suspended.');
        Response::redirect('members.php?memberId=' . $id);
    }

    public function resendInvite(Request $request): void
    {
        $id = $request->getInt('id');
        $this->memberService->resendInvitation($id);
        Session::setFlash('success', 'Password setup invitation re-sent successfully!');
        Response::redirect('members.php?memberId=' . $id . '&msg=invite_sent');
    }

    public function delete(Request $request): void
    {
        // Enforce role hierarchy: manager, admin, or owner
        AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);

        $id = $request->getInt('id');
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid member ID');
        }

        if ($id === Session::getUserId()) {
            Session::setFlash('error', 'Cannot delete your own account');
            Response::redirect('members.php');
            return;
        }

        try {
            $this->memberService->deleteMember($id);
            Session::setFlash('success', 'Member deleted successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to delete member: ' . $e->getMessage());
        }

        Response::redirect('members.php');
    }
}
