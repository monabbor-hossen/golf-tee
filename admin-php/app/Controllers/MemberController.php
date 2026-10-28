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
        if (!Session::isStaff()) {
            Response::redirect('login.php');
            return;
        }

        // Handle POST actions
        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            try {
                if ($action === 'add') {
                    $email = trim((string)$request->post('email', ''));
                    if ($this->memberService->emailExists($email)) {
                        throw new \Exception('Email already registered');
                    }
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
                    if (!Session::hasRole('manager') && !Session::hasRole('admin') && !Session::hasRole('owner')) {
                        Session::setFlash('error', 'Only management staff may delete members.');
                        Response::redirect('members.php');
                        return;
                    }
                    $id = $request->getInt('id');
                    if ($id === Session::getUserId()) {
                        Session::setFlash('error', 'Cannot delete your own account');
                        Response::redirect('members.php');
                        return;
                    }
                    if ($this->memberService->hasFutureBookings($id)) {
                        throw new \Exception('Cannot delete member with active bookings');
                    }

                        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE member_id = ? AND status = 'Pending'");
                        $stmt->execute([$id]);
                        if ((int)$stmt->fetchColumn() > 0) {
                            Session::setFlash('error', 'Cannot delete member with pending transactions.');
                            Response::redirect('members.php');
                            return;
                        }

                        $db->beginTransaction();
                        try {
                            $this->memberService->deleteMember($id);
                            $db->commit();
                            Session::setFlash('success', 'Member deleted successfully.');
                        } catch (\Throwable $e) {
                            $db->rollBack();
                            Session::setFlash('error', 'Failed to delete member: ' . $e->getMessage());
                        }
                    Response::redirect('members.php');
                    return;
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

    public function create(Request $request): void
    {
        Session::requireAuth();
        if (!Session::isStaff()) {
            Response::forbidden('Access denied. Staff role required.');
            return;
        }
        $this->index($request);
    }

    public function store(Request $request): void
    {
        Session::requireAuth();
        if (!Session::isStaff()) {
            Response::forbidden('Access denied. Staff role required.');
            return;
        }
        $this->index($request);
    }

    public function edit(Request $request): void
    {
        Session::requireAuth();
        if (!Session::isStaff()) {
            Response::forbidden('Access denied. Staff role required.');
            return;
        }
        $this->index($request);
    }

    public function update(Request $request): void
    {
        Session::requireAuth();
        if (!Session::isStaff()) {
            Response::forbidden('Access denied. Staff role required.');
            return;
        }
        $this->index($request);
    }

    public function destroy(Request $request): void
    {
        Session::requireAuth();
        if (!Session::isStaff()) {
            Response::forbidden('Access denied. Staff role required.');
            return;
        }
        if (!Session::hasRole('manager') && !Session::hasRole('admin') && !Session::hasRole('owner')) {
            Response::forbidden('Access denied. Manager role required.');
            return;
        }

        $id = $request->getInt('id');
        if ($id > 0) {
            // Finding 3.13: Prevent deletion of owner/admin accounts
            $target = $this->memberService->getMember($id);
            if ($target && isset($target['role']) && in_array(strtolower((string)$target['role']), ['owner', 'admin'], true)) {
                Response::forbidden('Cannot delete admin accounts');
                return;
            }

            if ($target && isset($target['role']) && getRoleLevel($target['role']) >= getRoleLevel(Session::getRole())) {
                throw new \Exception('Insufficient privileges');
            }

            // Finding 4.1: Prevent deletion of current user's own account
            if ($id === Session::getUserId()) {
                throw new \Exception('Cannot delete own account');
            }

            // Explicit guard: DELETE FROM members WHERE id > 0
            $this->memberService->deleteMember($id);
            Session::setFlash('success', 'Member deleted successfully.');
        }
        Response::redirect('members.php');
    }

    private function getRoleLevel(?string $role): int
    {
        return getRoleLevel($role);
    }
}

if (!function_exists('getRoleLevel')) {
    function getRoleLevel(?string $role): int {
        return match (strtolower((string)$role)) {
            'owner' => 100,
            'admin' => 80,
            'manager' => 60,
            'pro' => 40,
            'staff' => 20,
            default => 0,
        };
    }
}
