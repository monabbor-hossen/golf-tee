<?php
declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 compliant autoloader for the App namespace.
 * Supports domain subdirectories under Services (Booking, Payment, Member, Reports, Auth, Api).
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

        // Subdirectory fallback for domain-grouped Services
        if (strncmp($class, 'App\\Services\\', 13) === 0) {
            $parts = explode('\\', $class);
            $className = end($parts);
            $subdirs = ['Api', 'Booking', 'Payment', 'Member', 'Reports', 'Auth'];
            foreach ($subdirs as $subdir) {
                $subFile = self::$baseDir . 'Services' . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $className . '.php';
                if (file_exists($subFile)) {
                    require_once $subFile;
                    if (!class_exists($class, false) && class_exists("App\\Services\\{$subdir}\\{$className}", false)) {
                        class_alias("App\\Services\\{$subdir}\\{$className}", $class);
                    }
                    return true;
                }
            }
        }

        return false;
    }
}
