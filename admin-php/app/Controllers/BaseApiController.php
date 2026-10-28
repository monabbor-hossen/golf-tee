<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Repositories\MemberRepository;
use App\Services\PasswordHandler;
use App\Services\TokenAuthMiddleware;
use App\Services\TokenExtractor;
use PDO;

/**
 * BaseApiController
 *
 * Shared base controller providing centralized action dispatching, cached token extraction,
 * and unified authentication/ownership validation for both ApiController and MobileApiController.
 */
abstract class BaseApiController
{
    protected PDO $pdo;
    protected MemberRepository $memberRepo;
    protected ?string $authToken = null;
    protected ?string $extractedPassword = null;

    public function __construct(?PDO $pdo = null, ?MemberRepository $memberRepo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
        $this->memberRepo = $memberRepo ?? new MemberRepository();
    }

    /**
     * Finding 5.3: Single extraction point that extracts once and caches in $this->authToken.
     */
    protected function getAuthToken(?array $body = null): ?string
    {
        if ($this->authToken !== null) {
            return $this->authToken;
        }

        $token = TokenExtractor::extractToken($body ?? []);
        if (!empty($token)) {
            $this->authToken = $token;
            return $this->authToken;
        }

        return null;
    }

    /**
     * Finding 5.2 & 5.14: Protected method extractAndValidatePassword() called once.
     */
    protected function extractAndValidatePassword(?array $body = null, string $field = 'password'): string
    {
        $b = $body ?? $_POST;
        $pwd = PasswordHandler::extract($b, $field);
        PasswordHandler::validate($pwd);
        $this->extractedPassword = $pwd;
        return $pwd;
    }

    /**
     * Resolve authenticated member ID from active session or cached token.
     */
    public function getAuthenticatedMemberId(): ?int
    {
        Session::start();
        if (!empty($_SESSION['member_id'])) {
            return (int)$_SESSION['member_id'];
        }

        $token = $this->getAuthToken();
        if (!empty($token)) {
            $member = $this->memberRepo->findByToken($token);
            if ($member && !empty($member['id'])) {
                return (int)$member['id'];
            }
            if (!empty($_SESSION['auth_tokens'][$token])) {
                return (int)$_SESSION['auth_tokens'][$token];
            }
        }

        return null;
    }

    /**
     * Verify resource ownership against authenticated member or staff role.
     */
    public function requireOwnership(int $targetMemberId, int $unauthCode = 401): ?int
    {
        $authId = $this->getAuthenticatedMemberId();
        if ($authId === null) {
            http_response_code($unauthCode);
            echo json_encode(['error' => 'Unauthorized']);
            return null;
        }

        $token = $this->getAuthToken();
        if (!empty($token)) {
            $member = $this->memberRepo->findByToken($token);
            if ($member && (int)$member['id'] !== $targetMemberId && !Session::isStaff()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden - Token does not match member']);
                return null;
            }
        }

        if ($authId !== $targetMemberId && !Session::isStaff()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - You do not own this member resource']);
            return null;
        }

        return $authId;
    }

    /**
     * Finding 5.12: Common handleAction() dispatcher for shared action routing.
     */
    public function handleAction(string $action, array $params = []): void
    {
        $method = 'action' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $action)));
        if (method_exists($this, $method)) {
            $this->$method($params);
            return;
        }

        if (method_exists($this, $action)) {
            $this->$action($params);
            return;
        }

        Response::notFound('Action not found');
    }
}
