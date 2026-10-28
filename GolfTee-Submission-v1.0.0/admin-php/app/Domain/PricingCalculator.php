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

    /**
     * Calculate total and itemized breakdown for a booking.
     */
    public static function calculateTotal(array $bookingDetails, array $pricingSettings = []): array
    {
        $holes = (int)($bookingDetails['holes'] ?? 18);
        $date = (string)($bookingDetails['date'] ?? $bookingDetails['tee_date'] ?? date('Y-m-d'));
        $slot = (int)($bookingDetails['slot'] ?? $bookingDetails['tee_slot'] ?? 480);
        $party = (int)($bookingDetails['party'] ?? $bookingDetails['players'] ?? 1);
        $addons = $bookingDetails['addons'] ?? [];

        $greenFeePerPlayer = self::calculate($holes, $date, $slot, $pricingSettings);
        $greenFeesTotal = $greenFeePerPlayer * $party;

        $cartFee = 0.0;
        if (in_array('cart', $addons, true)) {
            $cartFee = (float)($pricingSettings['cart_fee'] ?? 25.0) * ceil($party / 2);
        }

        $caddieFee = 0.0;
        if (in_array('caddie', $addons, true)) {
            $caddieFee = (float)($pricingSettings['caddie_fee'] ?? 50.0);
        }

        $total = $greenFeesTotal + $cartFee + $caddieFee;

        return [
            'green_fee_per_player' => $greenFeePerPlayer,
            'green_fees_total'     => $greenFeesTotal,
            'cart_fee'             => $cartFee,
            'caddie_fee'           => $caddieFee,
            'total'                => $total,
            'party'                => $party,
            'holes'                => $holes,
        ];
    }
}
