<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\SlotFormatter;

/**
 * BookingValidator
 *
 * Single source of truth for tee time reservation validation rules
 * (slot availability, party size, hole constraints, twilight hours).
 */
class BookingValidator
{
    /**
     * Validate tee time reservation parameters.
     */
    public static function validate(array $params): array
    {
        $errors = [];

        $holes = (int)($params['holes'] ?? 18);
        if ($holes !== 9 && $holes !== 18) {
            $errors[] = 'Holes must be either 9 or 18.';
        }

        $players = (int)($params['players'] ?? ($params['party'] ?? 2));
        if ($players < 1 || $players > 4) {
            $errors[] = 'Party size must be between 1 and 4 players.';
        }

        $slot = (int)($params['min'] ?? ($params['tee_slot'] ?? 480));
        if ($slot < 420 || $slot > 1070) {
            $errors[] = 'Invalid tee slot time. Course operates between 7:00 AM and 5:50 PM.';
        }

        $date = (string)($params['date'] ?? ($params['tee_date'] ?? ''));
        if (empty($date) || !strtotime($date)) {
            $errors[] = 'Invalid reservation date format.';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors
        ];
    }
}
