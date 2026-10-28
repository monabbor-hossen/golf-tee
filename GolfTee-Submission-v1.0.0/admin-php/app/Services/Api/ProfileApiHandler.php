<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Services\Auth\TokenAuthMiddleware;
use App\Services\Member\MemberProfileService;
use App\Services\Member\ScorecardService;
use PDO;

/**
 * Dedicated REST API Profile Handler in App\Services\Api namespace.
 * Handles member profile retrieval and updates with ownership validation.
 */
class ProfileApiHandler
{
    private PDO $pdo;
    private PaymentApiHandler $paymentHandler;
    private TokenAuthMiddleware $tokenAuth;
    private MemberProfileService $profileService;
    private ScorecardService $scorecardService;

    public function __construct(
        ?PDO $pdo = null,
        ?PaymentApiHandler $paymentHandler = null,
        ?TokenAuthMiddleware $tokenAuth = null,
        ?MemberProfileService $profileService = null,
        ?ScorecardService $scorecardService = null
    ) {
        $this->pdo = $pdo ?? Database::pdo();
        $this->paymentHandler = $paymentHandler ?? new PaymentApiHandler($this->pdo);
        $this->tokenAuth = $tokenAuth ?? new TokenAuthMiddleware();
        $this->scorecardService = $scorecardService ?? new ScorecardService($this->pdo);
        $this->profileService = $profileService ?? new MemberProfileService($this->pdo, $this->paymentHandler, $this->scorecardService);
    }

    public function getProfile(int $memberId, array $body = []): ?array
    {
        $this->tokenAuth->requireOwnership($memberId, $body);
        return $this->profileService->getProfile($memberId);
    }

    public function updateProfile(int $memberId, array $body): array
    {
        $this->tokenAuth->requireOwnership($memberId, $body);
        return $this->profileService->updateProfile($memberId, $body);
    }

    public function saveScore(int $memberId, array $body): array
    {
        $this->tokenAuth->requireOwnership($memberId, $body);
        return $this->scorecardService->saveScore($memberId, $body);
    }
}
