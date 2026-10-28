<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Core\Session;

/**
 * OwnershipValidator
 * Reusable ownership validation service (Findings 3.7 & 5.19).
 */
class OwnershipValidator
{
    /**
     * Validate whether the authenticated user ($authId) owns the resource ($targetId)
     * or is an authenticated staff member.
     */
    public static function validateOwnership(int $targetId, ?int $authId, bool $allowStaff = true): bool
    {
        if ($authId === null || $targetId <= 0) {
            return false;
        }

        if ($authId === $targetId) {
            return true;
        }

        if ($allowStaff) {
            Session::requireStaff();
            return Session::isStaff();
        }

        return false;
    }
}
