<?php
declare(strict_types=1);

namespace App\Services;

/**
 * HandicapCalculationService.
 * Computes golfer handicaps, differential calculations, and historical handicap progressions.
 */
class HandicapCalculationService
{
    public static function getHandicapHistory(float $currentHcp): array
    {
        return [
            round($currentHcp + 2.0, 1),
            round($currentHcp + 1.5, 1),
            round($currentHcp + 1.1, 1),
            round($currentHcp + 0.5, 1),
            round($currentHcp, 1),
        ];
    }

    public static function calculateHandicap(array $differentials): float
    {
        if (empty($differentials)) {
            return 36.0;
        }
        sort($differentials);
        $best = array_slice($differentials, 0, max(1, (int)floor(count($differentials) * 0.4)));
        $avg = array_sum($best) / count($best);
        return round($avg * 0.96, 1);
    }
}
