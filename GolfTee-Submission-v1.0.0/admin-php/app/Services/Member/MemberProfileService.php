<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * MemberProfileService.
 * Focused service for member profile retrieval and updating member profile data.
 */
class MemberProfileService
{
    private PDO $pdo;
    private PaymentApiHandler $paymentHandler;
    private ScorecardService $scorecardService;

    public function __construct(
        ?PDO $pdo = null,
        ?PaymentApiHandler $paymentHandler = null,
        ?ScorecardService $scorecardService = null
    ) {
        $this->pdo = $pdo ?? Database::pdo();
        $this->paymentHandler = $paymentHandler ?? new PaymentApiHandler($this->pdo);
        $this->scorecardService = $scorecardService ?? new ScorecardService($this->pdo);
    }

    public function getProfile(int $memberId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) {
            return null;
        }

        $member['member_number'] = sprintf('#PV-%05d', (int)$member['id']);
        $cards = $this->paymentHandler->getCards($memberId)['cards'] ?? [];
        $currentHcp = (float)($member['hcp'] ?? 18.0);

        return [
            'status' => 'ok',
            'member' => $member,
            'cards' => $cards,
            'hcp_history' => HandicapCalculationService::getHandicapHistory($currentHcp),
            'best_score' => $this->scorecardService->getBestScore($memberId),
        ];
    }

    public function updateProfile(int $memberId, array $body): array
    {
        $phone = trim((string)($body['phone'] ?? ''));
        $name = trim((string)($body['name'] ?? ''));

        $fields = [];
        $params = [];

        if ($phone !== '') {
            $fields[] = 'phone = ?';
            $params[] = $phone;
        }
        if ($name !== '') {
            $fields[] = 'name = ?';
            $params[] = $name;
        }

        if (!empty($fields)) {
            $params[] = $memberId;
            $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return ['status' => 'ok', 'message' => 'Profile updated successfully'];
    }
}
