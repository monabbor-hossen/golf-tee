<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Core\Session;
use App\Core\UnauthorizedException;
use App\Services\OwnershipValidator;
use App\Services\Payment\CardManagementService;
use App\Services\Payment\PaymentProcessor;
use App\Services\Payment\TransactionService;
use PDO;

/**
 * Dedicated REST API Payment Handler in App\Services\Api namespace.
 * Enforces ownership validation on transaction reads and mutations.
 */
class PaymentApiHandler
{
    private PDO $pdo;
    private CardManagementService $cardService;
    private TransactionService $txnService;
    private PaymentProcessor $paymentProcessor;

    public function __construct(
        ?PDO $pdo = null,
        ?CardManagementService $cardService = null,
        ?TransactionService $txnService = null,
        ?PaymentProcessor $paymentProcessor = null
    ) {
        $this->pdo = $pdo ?? Database::pdo();
        $this->cardService = $cardService ?? new CardManagementService($this->pdo);
        $this->txnService = $txnService ?? new TransactionService($this->pdo);
        $this->paymentProcessor = $paymentProcessor ?? new PaymentProcessor($this->pdo);
    }

    public function getCards(int $memberId): array
    {
        return $this->cardService->getCards($memberId);
    }

    public function getTransaction(string $txnId, int $memberId): ?array
    {
        return $this->txnService->getTransaction($txnId, $memberId);
    }

    public function getTransactions(int $memberId, int $limit = 50): array
    {
        return $this->txnService->getTransactions($memberId, $limit);
    }

    public function getTransactionDetail(string $txnId, int $memberId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$txnId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$txn) {
            return null;
        }

        if (!OwnershipValidator::validateOwnership((int)$txn['member_id'], $memberId, true)) {
            throw new UnauthorizedException('Unauthorized: You do not own this transaction', 403);
        }

        return $txn;
    }

    public function addCard(int $memberId, array $data): array
    {
        return $this->cardService->addCard($memberId, $data);
    }

    public function deleteCard(int $memberId, int $cardId): array
    {
        $stmt = $this->pdo->prepare("SELECT member_id FROM member_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false) {
            return ['error' => 'Card not found', 'code' => 404];
        }
        if (!OwnershipValidator::validateOwnership((int)$ownerId, $memberId, true)) {
            return ['error' => 'Forbidden: You do not own this card resource', 'code' => 403];
        }

        return $this->cardService->deleteCard($memberId, $cardId);
    }

    public function setDefaultCard(int $memberId, int $cardId): array
    {
        $stmt = $this->pdo->prepare("SELECT member_id FROM member_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $ownerId = $stmt->fetchColumn();
        if ($ownerId === false) {
            return ['error' => 'Card not found', 'code' => 404];
        }
        if (!OwnershipValidator::validateOwnership((int)$ownerId, $memberId, true)) {
            return ['error' => 'Forbidden: You do not own this card resource', 'code' => 403];
        }

        return $this->cardService->setDefaultCard($memberId, $cardId);
    }
}
