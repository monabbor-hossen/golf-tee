<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * Unified Rate Limiting Service (Findings 5.14 & 5.15).
 * Configurable rate limits and windows per operation type (login, registration, booking, api_call).
 */
class RateLimiter
{
    private const DEFAULT_LIMITS = [
        'login'        => ['max' => 5,  'window' => 900],   // 5 attempts per 15 mins
        'registration' => ['max' => 5,  'window' => 3600],  // 5 attempts per 1 hour
        'booking'      => ['max' => 10, 'window' => 3600],  // 10 bookings per 1 hour
        'api_call'     => ['max' => 60, 'window' => 60],    // 60 requests per minute
    ];

    public static function isLimited(string $type, string|int $key, ?int $customMax = null, ?int $customWindow = null): bool
    {
        $config = self::DEFAULT_LIMITS[$type] ?? ['max' => 10, 'window' => 3600];
        $max = $customMax ?? $config['max'];
        $window = $customWindow ?? $config['window'];
        $keyStr = (string)$key;

        if ($type === 'booking') {
            $memberId = (int)$key;
            if ($memberId <= 0) {
                return false;
            }
            try {
                $db = Database::pdo();
                $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)");
                $stmt->execute([$memberId, $window]);
                return (int)$stmt->fetchColumn() >= $max;
            } catch (\Throwable $e) {
                return false;
            }
        }

        // IP / identifier based rate limiting using login_attempts table or session
        $cutoff = time() - $window;
        try {
            $pdo = Database::pdo();
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND attempt_time < ?")->execute([$keyStr, time() - 86400]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time >= ?");
            $stmt->execute([$keyStr, $cutoff]);
            $count = (int)$stmt->fetchColumn();

            if ($count >= $max) {
                return true;
            }
        } catch (\Throwable $e) {}

        Session::start();
        $sessKey = "_{$type}_attempts_{$keyStr}";
        $sessTimeKey = "_{$type}_last_{$keyStr}";
        $attempts = (int)Session::get($sessKey, 0);
        $lastAttemptTime = (int)Session::get($sessTimeKey, 0);

        if ($attempts >= $max) {
            $elapsed = time() - $lastAttemptTime;
            if ($elapsed < $window) {
                return true;
            }
            Session::set($sessKey, 0);
        }

        return false;
    }

    public static function recordAttempt(string $type, string|int $key): void
    {
        $keyStr = (string)$key;
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, attempt_time) VALUES (?, ?)");
            $stmt->execute([$keyStr, time()]);
        } catch (\Throwable $e) {}

        Session::start();
        $sessKey = "_{$type}_attempts_{$keyStr}";
        $sessTimeKey = "_{$type}_last_{$keyStr}";
        $attempts = (int)Session::get($sessKey, 0);
        Session::set($sessKey, $attempts + 1);
        Session::set($sessTimeKey, time());
    }

    public static function clear(string $type, string|int $key): void
    {
        $keyStr = (string)$key;
        try {
            $pdo = Database::pdo();
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$keyStr]);
        } catch (\Throwable $e) {}

        Session::start();
        $sessKey = "_{$type}_attempts_{$keyStr}";
        $sessTimeKey = "_{$type}_last_{$keyStr}";
        Session::set($sessKey, 0);
        Session::remove($sessTimeKey);
    }

    public static function getRemainingMinutes(string $type, string|int $key): int
    {
        $config = self::DEFAULT_LIMITS[$type] ?? ['max' => 10, 'window' => 3600];
        $window = $config['window'];
        $keyStr = (string)$key;
        $cutoff = time() - $window;

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT MAX(attempt_time) FROM login_attempts WHERE ip = ? AND attempt_time >= ?");
            $stmt->execute([$keyStr, $cutoff]);
            $lastTime = (int)$stmt->fetchColumn();
            if ($lastTime > 0) {
                $elapsed = time() - $lastTime;
                $remaining = $window - $elapsed;
                return (int)ceil(max(1, $remaining) / 60);
            }
        } catch (\Throwable $e) {}

        Session::start();
        $sessTimeKey = "_{$type}_last_{$keyStr}";
        $lastAttemptTime = (int)Session::get($sessTimeKey, 0);
        $elapsed = time() - $lastAttemptTime;
        $remaining = $window - $elapsed;
        return (int)ceil(max(1, $remaining) / 60);
    }
}
