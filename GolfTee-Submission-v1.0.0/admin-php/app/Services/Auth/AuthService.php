<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\StaffRepository;
use App\Repositories\MemberRepository;

class AuthService
{
    private int $maxAttempts;
    private int $lockoutSeconds;

    private StaffRepository $staffRepo;
    private LoginLockoutManager $lockoutManager;
    private MemberRepository $memberRepo;

    public function __construct(
        ?StaffRepository $staffRepo = null,
        ?LoginLockoutManager $lockoutManager = null,
        ?MemberRepository $memberRepo = null
    ) {
        $this->maxAttempts = (int)\App\Core\Config::get('AUTH_MAX_ATTEMPTS', 5);
        $this->lockoutSeconds = (int)\App\Core\Config::get('AUTH_LOCKOUT_SECONDS', 900);
        $this->staffRepo = $staffRepo ?? new StaffRepository();
        $this->lockoutManager = $lockoutManager ?? new LoginLockoutManager();
        $this->memberRepo = $memberRepo ?? new MemberRepository();
    }

    /**
     * Check if client IP is locked out due to excessive failed attempts.
     */
    public function isLockedOut(?string $ip = null): bool
    {
        return $this->lockoutManager->isLockedOut($ip);
    }

    public function getRemainingLockoutMinutes(?string $ip = null): int
    {
        return $this->lockoutManager->getRemainingMinutes($ip);
    }

    /**
     * Authenticate staff credentials with IP-based rate limiting and brute force protection.
     */
    public function attempt(string $email, string $password): array
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($this->isLockedOut($clientIp)) {
            $mins = $this->getRemainingLockoutMinutes($clientIp);
            return [
                'success' => false,
                'error'   => "Too many failed attempts. Please wait {$mins} minute" . ($mins > 1 ? 's' : '') . ' before trying again.',
            ];
        }

        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL) ?: '');
        $staff = $this->staffRepo->findByEmail($email);

        if ($staff && password_verify($password, $staff['password'] ?? '')) {
            // Prevent session fixation attacks
            Session::start();
            session_regenerate_id(true);

            // Reset throttle on successful sign-in
            $this->lockoutManager->clearAttempts($clientIp);

            Session::login((int)$staff['id'], (string)$staff['name'], (string)$staff['role']);
            return ['success' => true];
        }

        // Record failed attempt
        $this->lockoutManager->recordFailedAttempt($clientIp);
        $attempts = (int)Session::get('_login_attempts', 1);

        $remaining = $this->maxAttempts - $attempts;
        $errMsg = 'Invalid email or password.';
        if ($remaining > 0 && $remaining <= 2) {
            $errMsg .= " Warning: {$remaining} attempt" . ($remaining > 1 ? 's' : '') . ' remaining before temporary lockout.';
        } elseif ($remaining <= 0) {
            $errMsg = 'Too many failed login attempts. Your account has been temporarily locked for 15 minutes.';
        }

        return [
            'success' => false,
            'error'   => $errMsg,
        ];
    }

    /**
     * Atomically authenticate member, generate new token, and establish session.
     */
    public function authenticateAndRefreshToken(int $memberId): array
    {
        $member = $this->memberRepo->findById($memberId);
        if (!$member) {
            return ['error' => 'Member not found', 'code' => 404];
        }

        $token = TokenLifecycleService::generateAndStore($memberId);
        Session::start();
        $_SESSION['member_id'] = $memberId;
        $_SESSION['member_name'] = (string)$member['name'];
        $_SESSION['auth_tokens'][$token] = $memberId;

        return [
            'status' => 'ok',
            'token'  => $token,
            'member' => $member,
        ];
    }

    /**
     * Authenticate member from token and issue refreshed token.
     */
    public function refreshFromToken(string $token): array
    {
        $member = $this->memberRepo->findByToken($token);
        if (!$member || empty($member['id'])) {
            return ['error' => 'Invalid or expired token', 'code' => 401];
        }

        return $this->authenticateAndRefreshToken((int)$member['id']);
    }

    public function logout(): void
    {
        Session::logout();
    }

    public function inviteStaff(string $name, string $email, string $role): bool
    {
        $name = trim($name);
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL) ?: '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $tempPass = bin2hex(random_bytes(16));
        $defaultHashed = password_hash($tempPass, PASSWORD_BCRYPT);
        return $this->staffRepo->create($name, $email, $role, $defaultHashed);
    }

    public function removeStaff(int $staffId, int $currentStaffId): bool
    {
        if ($staffId === $currentStaffId) {
            return false; // Cannot remove self
        }
        return $this->staffRepo->delete($staffId);
    }
}
