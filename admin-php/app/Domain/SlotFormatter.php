<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Domain utility for time slot representation and formatting.
 */
class SlotFormatter
{
    /**
     * Format minutes from midnight to readable time (e.g. 420 -> "7:00 AM", 840 -> "2:00 PM")
     */
    public static function format(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        $period = $h < 12 ? 'AM' : 'PM';
        $h12 = (($h + 11) % 12) + 1;
        return sprintf('%d:%02d %s', $h12, $m, $period);
    }

    public static function toDisplayTime(int $minutes): string
    {
        return self::format($minutes);
    }

    /**
     * Get human-friendly time-ago representation.
     */
    public static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return round($diff / 60) . 'm ago';
        if ($diff < 86400) return round($diff / 3600) . 'h ago';
        return round($diff / 86400) . 'd ago';
    }

    /**
     * Convert "HH:MM" (e.g. "07:30") to minutes from midnight (450).
     */
    public static function timeStringToMinutes(string $timeStr): int
    {
        $parts = explode(':', $timeStr);
        return ((int)($parts[0] ?? 0) * 60) + (int)($parts[1] ?? 0);
    }
}
