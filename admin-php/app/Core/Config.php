<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public const TOKEN_FIELD_NAMES = ['google_token', 'id_token', 'token'];
    public const AUTH_MAX_ATTEMPTS = 5;
    public const AUTH_LOCKOUT_SECONDS = 900;

    public static function load(?string $envPath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $envPath ?? dirname(__DIR__, 2) . '/.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                $_ENV[$key] = $val;
                putenv("$key=$val");
                self::$items[$key] = $val;
            }
        }

        // Read exclusively from environment / configuration file without hard-coded fallbacks
        self::$items['DB_HOST'] = (string)($_ENV['DB_HOST'] ?? (self::$items['DB_HOST'] ?? 'localhost'));
        self::$items['DB_NAME'] = (string)($_ENV['DB_NAME'] ?? (self::$items['DB_NAME'] ?? 'golf_admin'));
        self::$items['DB_USER'] = (string)($_ENV['DB_USER'] ?? (self::$items['DB_USER'] ?? 'root'));
        self::$items['DB_PASS'] = (string)($_ENV['DB_PASS'] ?? (self::$items['DB_PASS'] ?? ''));
        self::$items['DB_CHARSET'] = (string)($_ENV['DB_CHARSET'] ?? (self::$items['DB_CHARSET'] ?? 'utf8mb4'));
        self::$items['APP_NAME'] = (string)($_ENV['APP_NAME'] ?? (self::$items['APP_NAME'] ?? 'Pine Valley Golf Club'));
        self::$items['APP_URL'] = (string)($_ENV['APP_URL'] ?? (self::$items['APP_URL'] ?? 'http://localhost/golf-tee/admin-php'));
        self::$items['SESSION_SECRET'] = (string)($_ENV['SESSION_SECRET'] ?? (self::$items['SESSION_SECRET'] ?? ''));
        self::$items['TOKEN_FIELD_NAMES'] = self::TOKEN_FIELD_NAMES;
        self::$items['AUTH_MAX_ATTEMPTS'] = (int)($_ENV['AUTH_MAX_ATTEMPTS'] ?? self::AUTH_MAX_ATTEMPTS);
        self::$items['AUTH_LOCKOUT_SECONDS'] = (int)($_ENV['AUTH_LOCKOUT_SECONDS'] ?? self::AUTH_LOCKOUT_SECONDS);

        $sessionSecret = (string)self::$items['SESSION_SECRET'];
        // Finding 3.16 & 5.15 & 1.2: Validation logic via ConfigValidator and condition check
        if (empty($sessionSecret) || strlen($sessionSecret) < 64 || preg_match('/(openssl|rand|base64|changeme|example)/i', $sessionSecret)) {
            ConfigValidator::validateSessionSecret($sessionSecret);
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$items[$key]) || isset($_ENV[$key]);
    }

    public static function all(): array
    {
        return self::$items;
    }
}
