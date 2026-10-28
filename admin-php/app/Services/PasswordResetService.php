<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * PasswordResetService
 *
 * Centralized service managing member password setup, token verification,
 * and secure credential updates across web admin and mobile REST API.
 */
class PasswordResetService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Verify that an activation or reset token exists in the database and has not expired.
     */
    public function verifyToken(string $token): ?array
    {
        $token = trim($token);
        if (empty($token) || !preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT id, name, email, tier, status, setup_token, setup_token_expires, password_reset_token, password_reset_expires
            FROM members
            WHERE (password_reset_token = ? AND (password_reset_expires IS NULL OR password_reset_expires > NOW()))
               OR (setup_token = ? AND (setup_token_expires IS NULL OR setup_token_expires > NOW()))
            LIMIT 1
        ");
        $stmt->execute([$token, $token]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        return $member ?: null;
    }

    /**
     * Execute password update after validating token existence and non-expiration.
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $member = $this->verifyToken($token);
        if (!$member) {
            return [
                'success' => false,
                'message' => 'This password setup link is invalid or has expired. Please contact club administration for a new link.'
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters in length.'
            ];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $this->pdo->beginTransaction();
        try {
            // Finding 4.11: UPDATE with WHERE id = ? AND password_reset_token = ? to prevent token bypass
            if (!empty($member['password_reset_token']) && $member['password_reset_token'] === $token) {
                $stmt = $this->pdo->prepare("
                    UPDATE members
                    SET password = ?,
                        password_hash = ?,
                        password_reset_token = NULL,
                        password_reset_expires = NULL,
                        status = IF(status = 'Pending', 'Active', status)
                    WHERE id = ? AND password_reset_token = ?
                ");
                $stmt->execute([$hash, $hash, (int)$member['id'], $token]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE members
                    SET password = ?,
                        password_hash = ?,
                        setup_token = NULL,
                        setup_token_expires = NULL,
                        password_reset_token = NULL,
                        password_reset_expires = NULL,
                        status = IF(status = 'Pending', 'Active', status)
                    WHERE id = ? AND setup_token = ?
                ");
                $stmt->execute([$hash, $hash, (int)$member['id'], $token]);
            }
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Password has been set successfully. You may now sign in.',
                'member'  => $member
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage()
            ];
        }
    }
}
