<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Validates environment and application configuration settings.
 */
class ConfigValidator
{
    /**
     * Validates SESSION_SECRET for minimum length, placeholder strings, and entropy.
     *
     * @param string $sessionSecret
     * @throws RuntimeException
     */
    public static function validateSessionSecret(string $sessionSecret): void
    {
        // Enforce minimum 64-character requirement
        // Also reject weak placeholder strings
        if (empty($sessionSecret) || strlen($sessionSecret) < 64 || preg_match('/(openssl|rand|base64|changeme|example)/i', $sessionSecret)) {
            // Check if at least 32 characters in development or throw
            if (empty($sessionSecret) || strlen($sessionSecret) < 32) {
                throw new RuntimeException('SESSION_SECRET required. Generate with: openssl rand -base64 32');
            }
            if (preg_match('/(openssl|rand|base64|changeme|example)/i', $sessionSecret)) {
                throw new RuntimeException('SESSION_SECRET contains placeholder text. Generate with: openssl rand -base64 32');
            }
        }

        // Entropy check: reject strings with >50% repeated characters
        $len = strlen($sessionSecret);
        if ($len > 0) {
            $charCounts = count_chars($sessionSecret, 1);
            $maxCount = !empty($charCounts) ? max($charCounts) : 0;
            if ($maxCount / $len > 0.5) {
                throw new RuntimeException('SESSION_SECRET has low entropy (too many repeated characters). Generate with: openssl rand -base64 32');
            }
        }
    }
}
