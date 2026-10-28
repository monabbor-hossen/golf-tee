<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Core\Session;
use Exception;

/**
 * Centralized authorization middleware enforcing role-based access control across controllers.
 */
class AuthorizationMiddleware
{
    /**
     * Require the active session to hold at least one of the specified roles.
     * Redirects or responds with HTTP 403 Forbidden if unauthorized.
     *
     * @param string|array $roles Single role or array of permitted roles (e.g. 'staff', ['manager', 'admin', 'owner'])
     * @param string|null $redirectUrl Optional redirect location on failure; if null, halts with Response::forbidden()
     */
    public static function requireRole(string|array $roles, ?string $redirectUrl = null): void
    {
        Session::requireAuth();

        $roleList = is_array($roles) ? $roles : [$roles];
        $authorized = false;

        foreach ($roleList as $role) {
            if (Session::hasRole($role)) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            if ($redirectUrl !== null) {
                Session::setFlash('error', 'Insufficient permissions to perform this action.');
                Response::redirect($redirectUrl);
                exit;
            }
            Response::forbidden('Access denied. Higher privileges required.');
            exit;
        }
    }

    /**
     * Ensure current user has strictly higher hierarchy level than target.
     */
    public static function requireHigherRoleLevel(string $targetRole): void
    {
        $currentRole = Session::getRole() ?? '';
        if (self::getRoleLevel($targetRole) >= self::getRoleLevel($currentRole)) {
            throw new Exception('Insufficient privileges');
        }
    }

    public static function getRoleLevel(?string $role): int
    {
        return match (strtolower((string)$role)) {
            'owner'   => 4,
            'admin'   => 3,
            'manager' => 2,
            'staff', 'pro' => 1,
            default   => 0,
        };
    }
}
