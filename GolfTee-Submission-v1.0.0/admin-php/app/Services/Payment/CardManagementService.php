<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * CardManagementService.
 * Handles member credit/debit card CRUD operations and defaults.
 */
class CardManagementService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function getCards(int $memberId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, brand, card_holder, last4, CONCAT(exp_month, '/', SUBSTRING(exp_year, 3, 2)) as exp, is_default as isDefault
            FROM member_cards
            WHERE member_id = ?
            ORDER BY is_default DESC, id DESC
        ");
        $stmt->execute([$memberId]);
        $cards = [];
        while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cards[] = [
                'id' => (string)$c['id'],
                'brand' => $c['brand'],
                'card_holder' => $c['card_holder'] ?? '',
                'last4' => $c['last4'],
                'exp' => $c['exp'],
                'isDefault' => (bool)$c['isDefault']
            ];
        }
        return ['status' => 'ok', 'cards' => $cards];
    }

    public function addCard(int $memberId, array $data): array
    {
        $cardNumber = preg_replace('/\D/', '', (string)($data['card_number'] ?? $data['number'] ?? ''));
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return ['error' => 'Please enter a valid card number.', 'code' => 400];
        }

        $last4 = substr($cardNumber, -4);
        $brand = (string)($data['brand'] ?? 'Visa');
        $cardHolder = trim((string)($data['card_holder'] ?? $data['holder'] ?? ''));
        $exp = (string)($data['exp'] ?? '12/28');
        $parts = explode('/', $exp);
        $expMonth = str_pad(trim($parts[0] ?? '12'), 2, '0', STR_PAD_LEFT);
        $expYear = '20' . trim($parts[1] ?? '28');

        $this->pdo->beginTransaction();
        try {
            $isDefault = !empty($data['is_default']) ? 1 : 0;
            if ($isDefault) {
                $this->pdo->prepare("UPDATE member_cards SET is_default = 0 WHERE member_id = ?")->execute([$memberId]);
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO member_cards (member_id, brand, card_holder, last4, exp_month, exp_year, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$memberId, $brand, $cardHolder, $last4, $expMonth, $expYear, $isDefault]);
            $newId = (int)$this->pdo->lastInsertId();

            $this->pdo->commit();

            return [
                'status' => 'ok',
                'message' => 'Card added successfully',
                'card' => [
                    'id' => (string)$newId,
                    'brand' => $brand,
                    'card_holder' => $cardHolder,
                    'last4' => $last4,
                    'exp' => $expMonth . '/' . substr($expYear, 2, 2),
                    'isDefault' => (bool)$isDefault
                ]
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['error' => 'Failed to add card: ' . $e->getMessage(), 'code' => 500];
        }
    }

    public function deleteCard(int $memberId, int $cardId): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM member_cards WHERE id = ? AND member_id = ?");
            $stmt->execute([$cardId, $memberId]);
            $this->pdo->commit();
            return ['status' => 'ok', 'message' => 'Card removed successfully'];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['error' => 'Failed to remove card: ' . $e->getMessage(), 'code' => 500];
        }
    }

    public function setDefaultCard(int $memberId, int $cardId): array
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE member_cards SET is_default = 0 WHERE member_id = ?")->execute([$memberId]);
            $stmt = $this->pdo->prepare("UPDATE member_cards SET is_default = 1 WHERE id = ? AND member_id = ?");
            $stmt->execute([$cardId, $memberId]);
            $this->pdo->commit();
            return ['status' => 'ok', 'message' => 'Default payment method updated'];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['error' => 'Failed to set default card: ' . $e->getMessage(), 'code' => 500];
        }
    }
}
