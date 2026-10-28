<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TokenGenerator;

/**
 * TokenLifecycleService
 * Centralized token lifecycle management with consistent expiry handling.
 */
class TokenLifecycleService
{
    public static function generate(): string
    {
        return TokenGenerator::generate();
    }

    public static function expiry(string $modifier = '+24 hours'): string
    {
        return TokenGenerator::expiry($modifier);
    }

    public static function validate(string $token): bool
    {
        if (empty(trim($token))) {
            return false;
        }
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT id FROM members WHERE reset_token = ? AND token_expires_at > NOW()");
        $stmt->execute([$token]);
        return (bool)$stmt->fetchColumn();
    }

    public static function refresh(int $memberId): string
    {
        return self::generateAndStore($memberId);
    }

    public static function generateAndStore(int $memberId): string
    {
        return TokenGenerator::generateAndStore($memberId);
    }
}
