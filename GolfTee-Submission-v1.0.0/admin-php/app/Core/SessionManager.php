<?php
declare(strict_types=1);

namespace App\Core;

/**
 * SessionManager
 * Handles low-level PHP session lifecycle, cookie parameters, timeout, and storage.
 */
class SessionManager
{
    private const SESSION_LIFETIME = 7200; // 2 hours

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (!empty($_SESSION['staff_id'])) {
            $lastActivity = $_SESSION['_last_activity'] ?? time();
            if (time() - $lastActivity > self::SESSION_LIFETIME) {
                $_SESSION = [];
                self::destroy();
                Response::redirect(Config::get('APP_URL') . '/login.php?timeout=1');
            }
            $_SESSION['_last_activity'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
