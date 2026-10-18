<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\StaffRepository;

class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    private StaffRepository $staffRepo;

    public function __construct(?StaffRepository $staffRepo = null)
    {
        $this->staffRepo = $staffRepo ?? new StaffRepository();
    }

    /**
     * Check if client is locked out due to excessive failed attempts.
     */
    public function isLockedOut(): bool
    {
        Session::start();
        $attempts = (int)Session::get('_login_attempts', 0);
        $lastAttemptTime = (int)Session::get('_login_last_attempt', 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $elapsed = time() - $lastAttemptTime;
            if ($elapsed < self::LOCKOUT_SECONDS) {
                return true;
            }
            // Lockout expired, reset counter
            Session::set('_login_attempts', 0);
        }

        return false;
    }

    public function getRemainingLockoutMinutes(): int
    {
        Session::start();
        $lastAttemptTime = (int)Session::get('_login_last_attempt', 0);
        $elapsed = time() - $lastAttemptTime;
        $remaining = max(1, (int)ceil((self::LOCKOUT_SECONDS - $elapsed) / 60));
        return $remaining;
    }

    /**
     * Authenticate staff credentials with rate limiting and brute force protection.
     */
    public function attempt(string $email, string $password): array
    {
        if ($this->isLockedOut()) {
            $mins = $this->getRemainingLockoutMinutes();
            return [
                'success' => false,
                'error'   => "Too many failed attempts. Please wait {$mins} minute" . ($mins > 1 ? 's' : '') . ' before trying again.',
            ];
        }

        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL) ?: '');
        $staff = $this->staffRepo->findByEmail($email);

        if ($staff && password_verify($password, $staff['password'] ?? '')) {
            // Reset throttle on successful sign-in
            Session::remove('_login_attempts');
            Session::remove('_login_last_attempt');

            Session::login((int)$staff['id'], (string)$staff['name'], (string)$staff['role']);
            return ['success' => true];
        }

        // Record failed attempt
        Session::start();
        $attempts = (int)Session::get('_login_attempts', 0) + 1;
        Session::set('_login_attempts', $attempts);
        Session::set('_login_last_attempt', time());

        $remaining = self::MAX_ATTEMPTS - $attempts;
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

        $defaultHashed = password_hash('password', PASSWORD_BCRYPT);
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
