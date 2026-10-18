<?php
declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 compliant autoloader for the App namespace.
 */
class Autoloader
{
    private static bool $registered = false;
    private static string $baseDir;

    public static function register(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;

        if (!self::$registered) {
            spl_autoload_register([self::class, 'loadClass']);
            self::$registered = true;
        }
    }

    public static function loadClass(string $class): bool
    {
        $prefix = 'App\\';
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return false;
        }

        $relativeClass = substr($class, $len);
        $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }
}
