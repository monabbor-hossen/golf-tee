<?php
declare(strict_types=1);

namespace App\Services\Api;

/**
 * Standardized JSON API response formatter for mobile endpoints.
 */
class ApiResponseFormatter
{
    public static function formatHomeResponse(array $member, ?array $nextBooking, array $readyWaitlist, array $news, array $conditions): array
    {
        return [
            'status'         => 'ok',
            'member'         => $member,
            'next_booking'   => $nextBooking,
            'ready_waitlist' => $readyWaitlist,
            'news'           => $news,
            'conditions'     => $conditions,
        ];
    }

    public static function formatSlotsResponse(string $date, int $holes, array $slots): array
    {
        return [
            'status' => 'ok',
            'date'   => $date,
            'holes'  => $holes,
            'slots'  => $slots,
        ];
    }

    public static function formatBookingsResponse(array $upcoming, array $past, array $waitlist): array
    {
        return [
            'status'   => 'ok',
            'upcoming' => $upcoming,
            'past'     => $past,
            'waitlist' => $waitlist,
        ];
    }

    public static function formatError(string $message, int $code = 400): array
    {
        return [
            'status'  => 'error',
            'message' => $message,
            'code'    => $code,
        ];
    }
}
