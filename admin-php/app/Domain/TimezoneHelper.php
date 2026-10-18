<?php
declare(strict_types=1);

namespace App\Domain;

use DateTime;
use DateTimeZone;

/**
 * Domain helper for world timezones, regional grouping, and UTC offsets.
 */
class TimezoneHelper
{
    private static ?array $groupedCache = null;

    /**
     * Get all timezones in the world grouped by continent/region.
     *
     * @return array<string, array<string, string>> Region => [TimezoneId => Label]
     */
    public static function getAllGrouped(): array
    {
        if (self::$groupedCache !== null) {
            return self::$groupedCache;
        }

        $regions = [
            'Common / Universal' => [DateTimeZone::UTC],
            'Africa'             => [DateTimeZone::AFRICA],
            'America'            => [DateTimeZone::AMERICA],
            'Antarctica'         => [DateTimeZone::ANTARCTICA],
            'Asia'               => [DateTimeZone::ASIA],
            'Atlantic'           => [DateTimeZone::ATLANTIC],
            'Australia'          => [DateTimeZone::AUSTRALIA],
            'Europe'             => [DateTimeZone::EUROPE],
            'Indian'             => [DateTimeZone::INDIAN],
            'Pacific'            => [DateTimeZone::PACIFIC],
        ];

        $result = [];

        foreach ($regions as $regionName => $masks) {
            $tzList = [];
            foreach ($masks as $mask) {
                $tzList = array_merge($tzList, DateTimeZone::listIdentifiers($mask));
            }
            $tzList = array_unique($tzList);
            sort($tzList);

            foreach ($tzList as $tz) {
                try {
                    $dt = new DateTime('now', new DateTimeZone($tz));
                    $offset = $dt->format('P');
                    $displayName = str_replace('_', ' ', $tz);
                    $result[$regionName][$tz] = "{$displayName} (UTC{$offset})";
                } catch (\Throwable) {
                    $result[$regionName][$tz] = $tz;
                }
            }
        }

        self::$groupedCache = $result;
        return $result;
    }

    /**
     * Check if a timezone identifier is valid.
     */
    public static function isValid(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }
}
