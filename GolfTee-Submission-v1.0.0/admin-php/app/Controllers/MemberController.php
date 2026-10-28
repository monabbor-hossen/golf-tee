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
            try {
                $handler = new \App\Services\MemberActionHandler($this->memberService);
                $handler->handle($request);
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
        Session::requireStaff();
        \App\Services\AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);

        $id = $request->getInt('id');
        if ($id > 0) {
            // Prevent deletion of owner/admin accounts
            $target = $this->memberService->getMember($id);
            if ($target && isset($target['role']) && in_array(strtolower((string)$target['role']), ['owner', 'admin'], true)) {
                Response::forbidden('Cannot delete admin accounts');
                return;
            }

            if ($target && isset($target['role']) && getRoleLevel($target['role']) >= getRoleLevel(Session::getRole())) {
                throw new \Exception('Insufficient privileges');
            }

            // Prevent deletion of current user's own account
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
