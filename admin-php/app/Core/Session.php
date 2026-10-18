<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session, Flash messaging, CSRF protection, and Auth State management.
 */
class Session
{
    private const SESSION_LIFETIME = 7200; // 2 hours

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            // Configure secure cookie parameters before starting session
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

        // Check for session timeout
        if (!empty($_SESSION['staff_id'])) {
            $lastActivity = $_SESSION['_last_activity'] ?? time();
            if (time() - $lastActivity > self::SESSION_LIFETIME) {
                self::logout();
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

    public static function isAuthenticated(): bool
    {
        self::start();
        return !empty($_SESSION['staff_id']);
    }

    public static function requireAuth(): void
    {
        self::start();
        if (!self::isAuthenticated()) {
            Response::redirect(Config::get('APP_URL') . '/login.php');
        }
    }

    public static function getStaffId(): ?int
    {
        self::start();
        return isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : null;
    }

    public static function getStaffName(): string
    {
        self::start();
        return (string)($_SESSION['staff_name'] ?? 'Admin');
    }

    public static function getStaffRole(): string
    {
        self::start();
        return (string)($_SESSION['staff_role'] ?? 'Staff');
    }

    public static function login(int $id, string $name, string $role): void
    {
        self::start();
        // Prevent session fixation attack
        session_regenerate_id(true);

        $_SESSION['staff_id']       = $id;
        $_SESSION['staff_name']     = $name;
        $_SESSION['staff_role']     = $role;
        $_SESSION['_last_activity'] = time();

        // Refresh CSRF token for the authenticated session
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function logout(): void
    {
        self::start();
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
        session_destroy();
    }

    public static function setFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        self::start();
        $msg = $_SESSION['_flash'][$type] ?? null;
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }

    public static function hasFlash(string $type): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$type]);
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        self::start();
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Validate CSRF on incoming POST requests.
     */
    public static function validateRequestCsrf(?Request $request = null): void
    {
        $req = $request ?? Request::createFromGlobals();
        if (!$req->isPost()) {
            return;
        }

        // Check POST param or HTTP Header X-CSRF-TOKEN
        $submittedToken = (string)($req->post('_csrf') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!self::verifyCsrfToken($submittedToken)) {
            http_response_code(403);
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Response::json(['error' => 'Invalid or expired CSRF token.'], 403);
            }
            die('<div style="font-family:sans-serif;padding:24px;color:#b91c1c;background:#fef2f2;border:1px solid #f87171;border-radius:8px;max-width:500px;margin:50px auto;">' .
                '<h3>403 — Action Forbidden</h3>' .
                '<p>Your session token was missing or expired. Please refresh the page and try again.</p>' .
                '<p><a href="javascript:history.back()" style="color:#1e5631;font-weight:bold;">← Go Back</a></p>' .
                '</div>');
        }
    }
}
