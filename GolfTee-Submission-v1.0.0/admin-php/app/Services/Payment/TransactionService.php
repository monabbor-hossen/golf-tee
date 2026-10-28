<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use PDO;

/**
 * TransactionService.
 * Handles member transaction queries with strict ownership verification and bounds (LIMIT 100).
 */
class TransactionService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function getTransaction(string $txnId, int $memberId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$txnId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$txn) {
            return null;
        }

        if ((int)$txn['member_id'] !== $memberId && !Session::isStaff()) {
            throw new UnauthorizedException('Unauthorized: You do not own this transaction', 403);
        }

        return $txn;
    }

    public function getTransactions(int $memberId, int $limit = 50): array
    {
        // Enforce maximum upper bound of 100
        $effectiveLimit = min($limit, 100);
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY txn_date DESC, id DESC LIMIT ?");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $effectiveLimit, PDO::PARAM_INT);
        $stmt->execute();
        return ['status' => 'ok', 'transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
