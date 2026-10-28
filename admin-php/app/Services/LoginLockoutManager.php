<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * Dedicated service for managing brute force login lockout and remaining lockout duration.
 */
class LoginLockoutManager
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 900; // 15 minutes

    public static function isLockedOut(?string $ip = null): bool
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $cutoff = time() - self::LOCKOUT_SECONDS;

        try {
            $pdo = Database::pdo();
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND attempt_time < ?")->execute([$clientIp, time() - 86400]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time >= ?");
            $stmt->execute([$clientIp, $cutoff]);
            $count = (int)$stmt->fetchColumn();

            if ($count >= self::MAX_ATTEMPTS) {
                return true;
            }
        } catch (\Throwable $e) {}

        // Fallback to session tracking
        Session::start();
        $attempts = (int)Session::get('_login_attempts', 0);
        $lastAttemptTime = (int)Session::get('_login_last_attempt', 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $elapsed = time() - $lastAttemptTime;
            if ($elapsed < self::LOCKOUT_SECONDS) {
                return true;
            }
            Session::set('_login_attempts', 0);
        }

        return false;
    }

    public function getRemainingMinutes(?string $ip = null): int
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $cutoff = time() - self::LOCKOUT_SECONDS;

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT MAX(attempt_time) FROM login_attempts WHERE ip = ? AND attempt_time >= ?");
            $stmt->execute([$clientIp, $cutoff]);
            $lastTime = (int)$stmt->fetchColumn();
            if ($lastTime > 0) {
                $elapsed = time() - $lastTime;
                $remaining = self::LOCKOUT_SECONDS - $elapsed;
                return (int)ceil(max(1, $remaining) / 60);
            }
        } catch (\Throwable $e) {}

        Session::start();
        $lastAttemptTime = (int)Session::get('_login_last_attempt', 0);
        $elapsed = time() - $lastAttemptTime;
        $remaining = self::LOCKOUT_SECONDS - $elapsed;
        return (int)ceil(max(1, $remaining) / 60);
    }

    public function recordFailedAttempt(?string $ip = null): void
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, attempt_time) VALUES (?, ?)");
            $stmt->execute([$clientIp, time()]);
        } catch (\Throwable $e) {}

        Session::start();
        $attempts = (int)Session::get('_login_attempts', 0);
        Session::set('_login_attempts', $attempts + 1);
        Session::set('_login_last_attempt', time());
    }

    public function clearAttempts(?string $ip = null): void
    {
        $clientIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        try {
            $pdo = Database::pdo();
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$clientIp]);
        } catch (\Throwable $e) {}

        Session::start();
        Session::set('_login_attempts', 0);
        Session::remove('_login_last_attempt');
    }
}
