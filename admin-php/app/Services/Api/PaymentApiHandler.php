<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Services\PaymentApiHandler as BasePaymentHandler;
use PDO;

/**
 * Dedicated REST API Payment Handler in App\Services\Api namespace.
 * Enforces ownership validation on transaction reads and mutations per Findings 3.15 & 4.7.
 */
class PaymentApiHandler extends BasePaymentHandler
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
        parent::__construct($this->pdo);
    }

    /**
     * Finding 3.15: Verify transaction.member_id === $memberId before returning data.
     */
    public function getTransactionDetail(string $txnId, int $memberId): ?array
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
}
