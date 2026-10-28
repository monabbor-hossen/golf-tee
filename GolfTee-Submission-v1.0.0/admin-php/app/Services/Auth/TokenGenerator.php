<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use RuntimeException;
use Throwable;

/**
 * Standardized cryptographically secure token generator.
 * Provides generateAndStore($memberId) for centralized persistence.
 */
class TokenGenerator
{
    public const TOKEN_LENGTH = 32;
    public const DEFAULT_BYTES = 32; // Generates 64 hexadecimal characters

    /**
     * Generate a cryptographically secure hex token.
     */
    public static function generate(int $bytes = self::DEFAULT_BYTES): string
    {
        try {
            return trim(bin2hex(random_bytes(max(16, $bytes))));
        } catch (Throwable $e) {
            $cryptoStrong = false;
            $raw = openssl_random_pseudo_bytes(max(16, $bytes), $cryptoStrong);
            if ($raw === false || !$cryptoStrong) {
                throw new RuntimeException('Cryptographic failure: unable to generate secure pseudo-random bytes.');
            }
            return trim(bin2hex($raw));
        }
    }

    /**
     * Generate default expiry timestamp for member activation or password reset.
     */
    public static function expiry(string $modifier = '+7 days'): string
    {
        return date('Y-m-d H:i:s', strtotime($modifier));
    }

    /**
     * Centralized token generation and database/session storage in a single service method.
     */
    public static function generateAndStore(int $memberId, string $type = 'auth', string $expiryModifier = '+30 days'): string
    {
        $token = self::generate();
        $expires = self::expiry($expiryModifier);

        $pdo = Database::pdo();
        if ($type === 'auth') {
            Session::start();
            $_SESSION['auth_tokens'][$token] = $memberId;
            $pdo->prepare("UPDATE members SET setup_token = ?, setup_token_expires = ? WHERE id = ?")
                ->execute([$token, $expires, $memberId]);
        } elseif ($type === 'reset') {
            $pdo->prepare("UPDATE members SET reset_token = ?, token_expires_at = ?, password_reset_token = ?, password_reset_expires = ? WHERE id = ?")
                ->execute([$token, $expires, $token, $expires, $memberId]);
        }

        return $token;
    }
}
