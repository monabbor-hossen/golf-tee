<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * TokenExtractor
 *
 * Centralized token extraction utility with documented priority:
 * 1) Authorization Bearer header
 * 2) X-Auth-Token header
 * 3) JSON request body
 * 4) Form POST parameter
 * 5) Query string parameter
 */
class TokenExtractor
{
    /**
     * Documented token extraction priority:
     * 1) Authorization header
     * 2) X-Auth-Token header
     * 3) JSON body
     * 4) POST form
     * 5) Query param
     */
    public static function extractToken(array $body = []): string
    {
        // 1) Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // 2) X-Auth-Token header
        if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            return trim((string)$_SERVER['HTTP_X_AUTH_TOKEN']);
        }

        // 3) JSON body
        if (!empty($body['token'])) {
            return trim((string)$body['token']);
        }

        // 4) POST form
        if (!empty($_POST['token'])) {
            return trim((string)$_POST['token']);
        }

        // 5) Query param
        if (!empty($_GET['token'])) {
            return trim((string)$_GET['token']);
        }

        return '';
    }

    /**
     * Handles both array and string inputs, validates token format, and returns validated token or throws.
     */
    public static function extractAndValidate(array|string $input): string
    {
        $token = is_array($input) ? self::extractToken($input) : trim((string)$input);
        if ($token === '') {
            throw new InvalidArgumentException('Token cannot be empty');
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $token)) {
            throw new InvalidArgumentException('Invalid token format');
        }
        return $token;
    }
}
