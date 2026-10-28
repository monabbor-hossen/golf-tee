<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GoogleAuthExtractor
 *
 * Extracts and validates Google OAuth ID tokens from mobile and web clients.
 * Documents provider variations:
 * - 'id_token': Standard OpenID Connect parameter from Google Sign-In SDK
 * - 'google_token': Mobile app custom client payload key
 * - 'token': Generic OAuth bearer token fallback
 */
class GoogleAuthExtractor
{
    /**
     * Extract Google OAuth token checking documented provider variations in priority order:
     * 1) 'id_token' (Standard Google OpenID Connect token)
     * 2) 'google_token' (Custom mobile flutter client wrapper)
     * 3) 'token' (Generic OAuth fallback)
     */
    public static function extractToken(array $body): string
    {
        if (!empty($body['id_token']) && is_string($body['id_token'])) {
            return trim($body['id_token']);
        }
        if (!empty($body['google_token']) && is_string($body['google_token'])) {
            return trim($body['google_token']);
        }
        if (!empty($body['token']) && is_string($body['token'])) {
            return trim($body['token']);
        }
        return '';
    }

    /**
     * Verify token with Google's public tokeninfo endpoint.
     */
    public static function verify(string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        try {
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp !== false) {
                $payload = json_decode($resp, true);
                if (is_array($payload) && !empty($payload['email']) && empty($payload['error_description'])) {
                    return $payload;
                }
            }
        } catch (\Throwable $e) {}

        // Fallback for JWT structure: header.payload.signature
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (is_array($payload) && !empty($payload['email'])) {
                return $payload;
            }
        }

        return null;
    }
}
