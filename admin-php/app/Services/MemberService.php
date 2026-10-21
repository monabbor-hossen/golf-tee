<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityRepository;
use App\Repositories\BookingRepository;
use App\Repositories\MemberRepository;

class MemberService
{
    private MemberRepository $memberRepo;
    private BookingRepository $bookingRepo;
    private ActivityRepository $activityRepo;
    private MailService $mailService;

    public function __construct(
        ?MemberRepository $memberRepo = null,
        ?BookingRepository $bookingRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?MailService $mailService = null
    ) {
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
        $this->bookingRepo  = $bookingRepo ?? new BookingRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
        $this->mailService  = $mailService ?? new MailService();
    }

    public function listMembers(
        string $query = '',
        string $tier = 'All',
        string $status = 'All',
        string $sort = 'name',
        string $dir = 'asc',
        ?int $selectedId = null,
        string $spend = 'All'
    ): array {
        $members = $this->memberRepo->queryMembers($query, $tier, $status, $sort, $dir, $spend);
        $counts = $this->memberRepo->getStatusCounts();
        $total = $this->memberRepo->getTotalCount();

        $selectedMember = null;
        if ($selectedId) {
            $selectedMember = $this->memberRepo->findById($selectedId);
        }
        if (!$selectedMember && !empty($members)) {
            $selectedMember = $members[0];
            $selectedId = (int)$selectedMember['id'];
        }

        $bookingHistory = [];
        if ($selectedMember) {
            $bookingHistory = $this->bookingRepo->getByMemberId((int)$selectedMember['id'], 5);
        }

        return [
            'members'        => $members,
            'counts'         => $counts,
            'total'          => $total,
            'selectedMember' => $selectedMember,
            'selectedId'     => $selectedId,
            'bookingHistory' => $bookingHistory,
            'query'          => $query,
            'tier'           => $tier,
            'status'         => $status,
            'spend'          => $spend,
            'sort'           => $sort,
            'dir'            => $dir,
        ];
    }

    public function addMember(array $input): int
    {
        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        if ($email === '') {
            throw new \InvalidArgumentException('Email address is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }

        $existing = $this->memberRepo->findByEmail($email);
        if ($existing) {
            throw new \InvalidArgumentException("A member with the email '{$email}' already exists.");
        }

        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $data = [
            'name'                => $name,
            'email'               => $email,
            'phone'               => trim($input['phone'] ?? ''),
            'tier'                => $input['tier'] ?? 'Standard',
            'hcp'                 => (float)($input['hcp'] ?? 36.0),
            'status'              => $input['status'] ?? 'Pending',
            'joined'              => date('M Y'),
            'setup_token'         => $token,
            'setup_token_expires' => $expires,
        ];

        $id = $this->memberRepo->create($data);
        
        // Dispatch account activation email
        $mailResult = $this->mailService->sendMemberSetupInvitation($data['name'], $data['email'], $token);

        $this->activityRepo->log(
            'New member account created',
            "{$data['name']} ({$data['email']}) · Invitation email sent with password setup link",
            'forest'
        );

        return $id;
    }

    public function resendInvitation(int $id): ?array
    {
        $member = $this->memberRepo->findById($id);
        if (!$member) {
            return null;
        }

        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->memberRepo->setSetupToken($id, $token, $expires);

        $result = $this->mailService->sendMemberSetupInvitation($member['name'], $member['email'], $token);

        $this->activityRepo->log(
            'Setup invitation resent',
            "Resent password setup link to {$member['name']} ({$member['email']})",
            'forest'
        );

        return $result;
    }

    public function setPasswordViaToken(string $token, string $password): array
    {
        $token = trim($token);
        if (empty($token)) {
            return ['success' => false, 'message' => 'Invalid or missing activation token.'];
        }

        $member = $this->memberRepo->findByToken($token);
        if (!$member) {
            return ['success' => false, 'message' => 'This password setup link is invalid or has expired. Please contact club administration for a new link.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters in length.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ok = $this->memberRepo->setPasswordAndActivate((int)$member['id'], $hash);

        if ($ok) {
            $this->activityRepo->log(
                'Member password created',
                "{$member['name']} activated their account and set security password.",
                'forest'
            );
            return [
                'success' => true,
                'message' => 'Your password has been set successfully! You may now log in to the Pine Valley Golf app.',
                'member'  => [
                    'id'    => $member['id'],
                    'name'  => $member['name'],
                    'email' => $member['email'],
                ]
            ];
        }

        return ['success' => false, 'message' => 'Failed to save password. Please try again.'];
    }

    public function updateMember(int $id, array $input): bool
    {
        $data = [
            'name'   => trim($input['name'] ?? ''),
            'email'  => trim($input['email'] ?? ''),
            'phone'  => trim($input['phone'] ?? ''),
            'tier'   => $input['tier'] ?? 'Standard',
            'hcp'    => (float)($input['hcp'] ?? 36.0),
            'status' => $input['status'] ?? 'Active',
        ];

        $success = $this->memberRepo->update($id, $data);
        if ($success) {
            $this->activityRepo->log('Member updated', $data['name'], 'sand');
        }
        return $success;
    }

    public function approveMember(int $id): bool
    {
        $member = $this->memberRepo->findById($id);
        if (!$member) {
            return false;
        }

        $this->memberRepo->updateStatus($id, 'Active');
        $this->activityRepo->log('Member approved', $member['name'], 'forest');
        return true;
    }

    public function suspendMember(int $id): bool
    {
        return $this->memberRepo->updateStatus($id, 'Suspended');
    }

    public function deleteMember(int $id): bool
    {
        return $this->memberRepo->delete($id);
    }

    public function exportCsv(
        string $query = '',
        string $tier = 'All',
        string $status = 'All',
        string $spend = 'All',
        string $sort = 'name',
        string $dir = 'asc'
    ): array {
        $members = $this->memberRepo->queryMembers($query, $tier, $status, $sort, $dir, $spend);
        $rows = [];
        foreach ($members as $m) {
            $rows[] = [
                $m['id'],
                $m['name'],
                $m['email'],
                $m['phone'],
                $m['tier'],
                $m['status'],
                $m['hcp'],
                $m['rounds'],
                $m['spend'],
                $m['joined'],
                $m['last_play'] ?? '',
            ];
        }
        return $rows;
    }
}
