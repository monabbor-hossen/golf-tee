<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Repositories\MemberRepository;

/**
 * Centralized token authentication middleware for mobile REST API endpoints.
 * Enforces strict cryptographic/token-based verification and disallows untrusted headers.
 */
class TokenAuthMiddleware
{
    private MemberRepository $memberRepo;
    private static ?self $instance = null;

    public function __construct(?MemberRepository $memberRepo = null)
    {
        $this->memberRepo = $memberRepo ?? new MemberRepository();
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Authenticate request by Bearer token or session. Returns authenticated member ID.
     * Throws UnauthorizedException if token is missing or invalid.
     */
    public function authenticateMember(array $body = []): int
    {
        Session::start();
        if (!empty($_SESSION['member_id'])) {
            return (int)$_SESSION['member_id'];
        }

        $token = TokenExtractor::extractToken($body);
        if (!empty($token)) {
            $member = $this->memberRepo->findByToken($token);
            if ($member && !empty($member['id'])) {
                return (int)$member['id'];
            }
            if (!empty($_SESSION['auth_tokens'][$token])) {
                return (int)$_SESSION['auth_tokens'][$token];
            }
        }

        // Finding 3.9: Untrusted header fallback removed to prevent header spoofing.

        throw new UnauthorizedException('Authentication required', 401);
    }

    /**
     * Static and instance authenticate method matching audit specification.
     */
    public static function authenticate(array $body = []): int
    {
        return self::getInstance()->authenticateMember($body);
    }

    /**
     * Authenticate and verify resource ownership in a single step.
     */
    public function requireOwnership(int $targetMemberId, array $body = []): int
    {
        $authId = $this->authenticateMember($body);
        if ($authId !== $targetMemberId && !Session::isStaff()) {
            throw new UnauthorizedException('Forbidden - You do not own this member resource', 403);
        }
        return $authId;
    }

    /**
     * Static helper for requireOwnership.
     */
    public static function verifyOwnership(int $targetMemberId, array $body = []): int
    {
        return self::getInstance()->requireOwnership($targetMemberId, $body);
    }
}
