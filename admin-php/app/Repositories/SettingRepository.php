<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class SettingRepository
{
    private static array $cache = [];

    public function get(string $key, string $default = ''): string
    {
        if (!isset(self::$cache[$key])) {
            $val = Database::fetchColumn('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
            self::$cache[$key] = $val !== null ? (string)$val : $default;
        }
        return self::$cache[$key];
    }

    public function set(string $key, string $value): void
    {
        Database::execute(
            'INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=?',
            [$key, $value, $value]
        );
        self::$cache[$key] = $value;
    }

    public function getAll(): array
    {
        $rows = Database::fetchAll('SELECT `key`,`value` FROM settings');
        $map = [];
        foreach ($rows as $r) {
            $map[$r['key']] = $r['value'];
            self::$cache[$r['key']] = $r['value'];
        }
        return $map;
    }

    public function getPricingSettings(): array
    {
        $all = $this->getAll();
        return [
            'twilight_start'    => (int)($all['twilight_start'] ?? 900),
            'flag_twilight'     => (bool)($all['flag_twilight'] ?? 1),
            'price_weekday_18'  => (float)($all['price_weekday_18'] ?? 85),
            'price_weekday_9'   => (float)($all['price_weekday_9'] ?? 48),
            'price_weekend_18'  => (float)($all['price_weekend_18'] ?? 110),
            'price_weekend_9'   => (float)($all['price_weekend_9'] ?? 62),
            'price_twilight_18' => (float)($all['price_twilight_18'] ?? 55),
            'price_twilight_9'  => (float)($all['price_twilight_9'] ?? 35),
        ];
    }
}
