<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Domain\SlotFormatter;
use App\Repositories\ActivityRepository;
use App\Repositories\MemberRepository;
use Exception;
use InvalidArgumentException;
use PDO;

class MemberService
{
    private MemberRepository $memberRepo;
    private ActivityRepository $activityRepo;
    private EmailService $emailService;

    public function __construct(
        ?MemberRepository $memberRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?EmailService $emailService = null
    ) {
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
        $this->emailService = $emailService ?? new EmailService();
    }

    /**
     * Finding 5.4: Centralized single token generation point in service.
     */
    private function generateAuthToken(): string
    {
        return TokenGenerator::generate();
    }

    /**
     * Finding 3.12: Check if email already registered.
     */
    public function emailExists(string $email): bool
    {
        return $this->memberRepo->findByEmail($email) !== null;
    }

    /**
     * Finding 3.13: Check if member has future or active bookings.
     */
    public function hasFutureBookings(int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE member_id = ? AND tee_date >= CURDATE() AND status != 'Cancelled'");
        $stmt->execute([$memberId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function addMember(array $input): int
    {
        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        if ($email === '') {
            throw new InvalidArgumentException('Email address is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        // Domain validation per Finding 3.12
        $domain = substr(strrchr($email, "@") ?: '', 1);
        if ($domain === '') {
            throw new InvalidArgumentException('Invalid email domain.');
        }

        if ($this->emailExists($email)) {
            throw new Exception('Email already registered');
        }

        $token = $this->generateAuthToken();
        $expires = TokenGenerator::expiry('+24 hours');

        $data = [
            'name'                => $name,
            'email'               => $email,
            'phone'               => trim($input['phone'] ?? ''),
            'tier'                => $input['tier'] ?? 'Standard',
            'hcp'                 => (float)($input['hcp'] ?? 36.0),
            'status'              => $input['status'] ?? 'Pending',
            'joined'              => date('M Y'),
            'reset_token'         => $token,
            'token_expires_at'    => $expires,
            'setup_token'         => $token,
            'setup_token_expires' => $expires,
        ];

        // Finding 3.17: Set token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) in insert
        $db = Database::pdo();
        $stmt = $db->prepare("
            INSERT INTO members (name, email, phone, tier, hcp, status, joined, reset_token, token_expires_at, setup_token, setup_token_expires)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->execute([
            $data['name'], $data['email'], $data['phone'], $data['tier'],
            $data['hcp'], $data['status'], $data['joined'], $token, $token
        ]);
        $id = (int)$db->lastInsertId();
        
        // Dispatch account activation email
        $this->emailService->sendInvitation($data['name'], $data['email'], $token);

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

        // Finding 3.18: Invalidate previous token before generating new one to prevent token reuse
        $db = Database::pdo();
        $db->prepare("UPDATE members SET reset_token = NULL, setup_token = NULL, token_expires_at = NULL, setup_token_expires = NULL WHERE id = ?")->execute([$id]);

        $token = $this->generateAuthToken();
        $expires = TokenGenerator::expiry('+24 hours');
        
        $db->prepare("UPDATE members SET reset_token = ?, setup_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR), setup_token_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?")
           ->execute([$token, $token, $id]);

        $result = $this->emailService->sendInvitation($member['name'], $member['email'], $token);

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
        $data = [];
        if (isset($input['name'])) {
            $data['name'] = trim($input['name']);
        }
        if (isset($input['email'])) {
            $data['email'] = trim($input['email']);
        }
        if (isset($input['phone'])) {
            $data['phone'] = trim($input['phone']);
        }
        if (isset($input['tier'])) {
            $data['tier'] = $input['tier'];
        }
        if (isset($input['hcp'])) {
            $data['hcp'] = (float)$input['hcp'];
        }
        if (isset($input['status'])) {
            $data['status'] = $input['status'];
        }

        $success = $this->memberRepo->update($id, $data);
        if ($success) {
            $this->activityRepo->log('Member updated', "Member ID #{$id}", 'sand');
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
        if (!Session::hasRole('manager') && !Session::hasRole('admin') && !Session::hasRole('owner')) {
            throw new Exception('Insufficient privileges');
        }
        return $this->memberRepo->updateStatus($id, 'Suspended');
    }

    /**
     * Finding 4.2 & 4.8: Strict role validation, positive ID check, and DELETE with WHERE id = ? AND id > 0
     */
    public function deleteMember(int $id): bool
    {
        if (!Session::hasRole('admin') && !Session::hasRole('owner') && !Session::hasRole('manager')) {
            throw new Exception('Unauthorized');
        }

        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid member ID');
        }

        if ($this->hasFutureBookings($id)) {
            throw new Exception('Cannot delete member with active bookings');
        }

        $db = Database::pdo();
        $stmt = $db->prepare("DELETE FROM members WHERE id = ? AND id > 0");
        return $stmt->execute([$id]);
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
