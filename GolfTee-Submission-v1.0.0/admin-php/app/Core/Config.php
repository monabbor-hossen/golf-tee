<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application configuration registry and read-only accessor.
 * Delegates environment parsing to EnvLoader.
 */
class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(?string $envPath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $envPath ?? dirname(__DIR__, 2) . '/.env';
        self::$items = EnvLoader::load($path);

        $defaultsFile = dirname(__DIR__, 2) . '/config/defaults.php';
        $defaults = file_exists($defaultsFile) ? (require $defaultsFile) : [];

        foreach ($defaults as $key => $defaultVal) {
            if (isset($_ENV[$key])) {
                self::$items[$key] = $_ENV[$key];
            } elseif (!isset(self::$items[$key])) {
                self::$items[$key] = $defaultVal;
            }
        }

        $sessionSecret = (string)(self::$items['SESSION_SECRET'] ?? '');
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
