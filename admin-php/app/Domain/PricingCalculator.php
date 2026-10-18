<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Domain calculator for green fees and round pricing.
 */
class PricingCalculator
{
    /**
     * Calculate price per player for a round based on holes, date, slot time, and pricing rules.
     *
     * @param int $holes 18 or 9
     * @param string $date Y-m-d format
     * @param int $slot Minutes from midnight (e.g. 420 for 7:00 AM)
     * @param array $pricingSettings Associative array of price settings
     * @return float
     */
    public static function calculate(int $holes, string $date, int $slot, array $pricingSettings): float
    {
        $dayOfWeek = (int)date('N', strtotime($date));
        $isWeekend = ($dayOfWeek === 6 || $dayOfWeek === 7);

        $twilightStart = (int)($pricingSettings['twilight_start'] ?? 900);
        $twilightEnabled = (bool)($pricingSettings['flag_twilight'] ?? 1);

        $is18 = ($holes === 18);

        if ($twilightEnabled && $slot >= $twilightStart) {
            return (float)($is18 ? ($pricingSettings['price_twilight_18'] ?? 55) : ($pricingSettings['price_twilight_9'] ?? 35));
        }

        if ($isWeekend) {
            return (float)($is18 ? ($pricingSettings['price_weekend_18'] ?? 110) : ($pricingSettings['price_weekend_9'] ?? 62));
        }

        return (float)($is18 ? ($pricingSettings['price_weekday_18'] ?? 85) : ($pricingSettings['price_weekday_9'] ?? 48));
    }
}
