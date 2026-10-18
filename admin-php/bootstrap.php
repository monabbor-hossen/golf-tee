<?php
declare(strict_types=1);

/**
 * Golf Tee Course Admin — Application Bootstrap & Security Pipeline
 */

// ── Security Headers ──────────────────────────────────────────
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self'; form-action 'self'");
}

require_once __DIR__ . '/app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Domain\PricingCalculator;
use App\Domain\SlotFormatter;
use App\Repositories\ActivityRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TransactionRepository;

// Initialize autoloader
Autoloader::register(__DIR__ . '/app');

// Initialize config from .env or .env.example
Config::load(__DIR__);

// Initialize secure session
Session::start();

// Configure views directory
View::setViewsPath(__DIR__ . '/views');

// Global constants for backward compatibility
if (!defined('DB_HOST'))    define('DB_HOST',    Config::get('DB_HOST', 'localhost'));
if (!defined('DB_NAME'))    define('DB_NAME',    Config::get('DB_NAME', 'golf_admin'));
if (!defined('DB_USER'))    define('DB_USER',    Config::get('DB_USER', 'root'));
if (!defined('DB_PASS'))    define('DB_PASS',    Config::get('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', Config::get('DB_CHARSET', 'utf8mb4'));
if (!defined('APP_NAME'))   define('APP_NAME',   Config::get('APP_NAME', 'Pine Valley Golf Club'));
if (!defined('APP_URL'))    define('APP_URL',    Config::get('APP_URL', 'http://localhost/golf-tee/admin-php'));

// ─────────────────────────────────────────────────────────────
// Security & Backward Compatibility Bridge Functions
// ─────────────────────────────────────────────────────────────

if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken(): string {
        return Session::csrfToken();
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string {
        return Session::csrfField();
    }
}

if (!function_exists('db')) {
    function db(): PDO {
        return Database::pdo();
    }
}

if (!function_exists('getSetting')) {
    function getSetting(string $key, string $default = ''): string {
        static $repo = null;
        if ($repo === null) {
            $repo = new SettingRepository();
        }
        return $repo->get($key, $default);
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $key, string $value): void {
        static $repo = null;
        if ($repo === null) {
            $repo = new SettingRepository();
        }
        $repo->set($key, $value);
    }
}

if (!function_exists('logActivity')) {
    function logActivity(string $title, string $desc, string $tone = 'gray'): void {
        static $repo = null;
        if ($repo === null) {
            $repo = new ActivityRepository();
        }
        $repo->log($title, $desc, $tone);
    }
}

if (!function_exists('addNotification')) {
    function addNotification(string $title, string $desc, string $page, ?string $focusData = null, string $tone = 'gray'): void {
        static $repo = null;
        if ($repo === null) {
            $repo = new NotificationRepository();
        }
        $repo->add($title, $desc, $page, $focusData, $tone);
    }
}

if (!function_exists('nextTxnId')) {
    function nextTxnId(): string {
        static $repo = null;
        if ($repo === null) {
            $repo = new TransactionRepository();
        }
        return $repo->generateNextId();
    }
}

if (!function_exists('getPrice')) {
    function getPrice(int $holes, string $date, int $slot): float {
        static $settingRepo = null;
        if ($settingRepo === null) {
            $settingRepo = new SettingRepository();
        }
        return PricingCalculator::calculate($holes, $date, $slot, $settingRepo->getPricingSettings());
    }
}

if (!function_exists('fmtSlot')) {
    function fmtSlot(int $minutes): string {
        return SlotFormatter::format($minutes);
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo(string $datetime): string {
        return SlotFormatter::timeAgo($datetime);
    }
}

// ─────────────────────────────────────────────────────────────
// Runtime Timezone Initialization
// ─────────────────────────────────────────────────────────────
try {
    $configuredTz = getSetting('timezone', Config::get('APP_TIMEZONE', 'America/New_York'));
    if ($configuredTz !== '' && in_array($configuredTz, DateTimeZone::listIdentifiers(), true)) {
        date_default_timezone_set($configuredTz);
    } else {
        date_default_timezone_set('America/New_York');
    }
} catch (\Throwable) {
    date_default_timezone_set('America/New_York');
}
