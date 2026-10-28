<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * RateLimiter
 *
 * Enforces per-IP request rate limits (max 100 requests / minute)
 * and security event logging for failed authentication attempts.
 */
class RateLimiter
{
    /**
     * Check rate limit: maximum $maxRequests per $windowSeconds for the client IP.
     */
    public static function check(string $ip, int $maxRequests = 100, int $windowSeconds = 60): bool
    {
        $now = time();
        $cutoff = $now - $windowSeconds;

        try {
            $pdo = Database::pdo();
            // Clean older records
            $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < ?")->execute([$now - 3600]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time >= ?");
            $stmt->execute([$ip, $cutoff]);
            $count = (int)$stmt->fetchColumn();

            if ($count >= $maxRequests) {
                return false;
            }

            $ins = $pdo->prepare("INSERT INTO login_attempts (ip, attempt_time) VALUES (?, ?)");
            $ins->execute([$ip, $now]);
            return true;
        } catch (\Throwable $e) {
            // Safe fallback
            return true;
        }
    }

    /**
     * Log a security event for failed authentication attempts.
     */
    public static function logSecurityEvent(string $event, string $ip, array $context = []): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (title, description, tone, actor)
                VALUES (?, ?, 'danger', 'Security')
            ");
            $desc = "IP: {$ip} · Event: {$event}";
            if (!empty($context['email'])) {
                $desc .= " · Target: " . $context['email'];
            }
            $stmt->execute([$event, $desc]);
        } catch (\Throwable $e) {}
    }
}
