<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application Configuration and Environment loader.
 */
class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(string $baseDir): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $baseDir . '/.env';
        if (!file_exists($envFile)) {
            $envFile = $baseDir . '/.env.example';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '#') || !str_contains($line, '=')) {
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

        // Set defaults if not provided
        self::$items['DB_HOST'] = $_ENV['DB_HOST'] ?? self::$items['DB_HOST'] ?? 'localhost';
        self::$items['DB_NAME'] = $_ENV['DB_NAME'] ?? self::$items['DB_NAME'] ?? 'golf_admin';
        self::$items['DB_USER'] = $_ENV['DB_USER'] ?? self::$items['DB_USER'] ?? 'root';
        self::$items['DB_PASS'] = $_ENV['DB_PASS'] ?? self::$items['DB_PASS'] ?? '';
        self::$items['DB_CHARSET'] = $_ENV['DB_CHARSET'] ?? self::$items['DB_CHARSET'] ?? 'utf8mb4';
        self::$items['APP_NAME'] = $_ENV['APP_NAME'] ?? self::$items['APP_NAME'] ?? 'Pine Valley Golf Club';
        self::$items['APP_URL'] = $_ENV['APP_URL'] ?? self::$items['APP_URL'] ?? 'http://localhost/golf-tee/admin-php';

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
