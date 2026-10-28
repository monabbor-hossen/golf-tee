<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\UnauthorizedException;
use PDO;

/**
 * Dedicated handler for member profile retrieval and updates with ownership validation.
 */
class ProfileApiHandler
{
    private PDO $pdo;
    private PaymentApiHandler $paymentHandler;
    private TokenAuthMiddleware $tokenAuth;

    public function __construct(
        ?PDO $pdo = null,
        ?PaymentApiHandler $paymentHandler = null,
        ?TokenAuthMiddleware $tokenAuth = null
    ) {
        $this->pdo = $pdo ?? Database::pdo();
        $this->paymentHandler = $paymentHandler ?? new PaymentApiHandler($this->pdo);
        $this->tokenAuth = $tokenAuth ?? new TokenAuthMiddleware();
    }

    /**
     * Finding 3.8: Enforce requireOwnership() at start of profile read.
     */
    public function getProfile(int $memberId, array $body = []): ?array
    {
        $this->tokenAuth->requireOwnership($memberId, $body);

        $stmt = $this->pdo->prepare("SELECT id, name, email, phone, tier, hcp, rounds, last_play, joined, status, spend FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) {
            return null;
        }

        $member['member_number'] = sprintf('#PV-%05d', (int)$member['id']);
        $cards = $this->paymentHandler->getCards($memberId)['cards'];

        return [
            'status' => 'ok',
            'member' => $member,
            'cards' => $cards,
            'hcp_history' => [14.8, 14.3, 13.9, 13.3, 12.8, (float)$member['hcp']],
            'best_score' => 74
        ];
    }

    /**
     * Finding 3.8: Enforce requireOwnership() at start of profile update.
     */
    public function updateProfile(int $memberId, array $body): array
    {
        $this->tokenAuth->requireOwnership($memberId, $body);

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
