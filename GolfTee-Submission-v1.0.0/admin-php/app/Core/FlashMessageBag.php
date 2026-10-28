<?php
declare(strict_types=1);

namespace App\Core;

/**
 * FlashMessageBag
 * Manages flash messages stored across request lifecycles in session.
 */
class FlashMessageBag
{
    public static function set(string $type, string $message): void
    {
        SessionManager::start();
        $_SESSION['_flash'][$type] = $message;
    }

    public static function get(string $type): ?string
    {
        SessionManager::start();
        $msg = $_SESSION['_flash'][$type] ?? null;
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }

    public static function has(string $type): bool
    {
        SessionManager::start();
        return isset($_SESSION['_flash'][$type]);
    }
}
