<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * PaymentProcessor
 *
 * Consolidated payment processing pipeline guaranteeing transaction
 * isolation and rollback protection across web admin and mobile app.
 */
class PaymentProcessor
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Process and record a financial transaction within a transaction wrapper.
     */
    public function recordTransaction(array $data): array
    {
        $amount = (float)($data['amount'] ?? 0.00);
        $name = trim((string)($data['name'] ?? 'Guest'));
        $description = trim((string)($data['description'] ?? 'Green fee'));
        $type = (string)($data['type'] ?? 'Green fee');
        $method = (string)($data['method'] ?? 'Visa');
        $status = (string)($data['status'] ?? 'Paid');
        $memberId = isset($data['member_id']) && (int)$data['member_id'] > 0 ? (int)$data['member_id'] : null;
        $bookingId = isset($data['booking_id']) && (int)$data['booking_id'] > 0 ? (int)$data['booking_id'] : null;
        $txnId = $data['id'] ?? ('TXN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));
        $date = $data['txn_date'] ?? date('Y-m-d');
        $time = (int)($data['txn_time'] ?? (date('H') * 60 + date('i')));

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref, member_id, booking_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $txnId,
                $date,
                $time,
                $name,
                $description,
                $type,
                $method,
                $amount,
                $status,
                (string)($bookingId ?? ''),
                $memberId,
                $bookingId
            ]);

            if ($memberId !== null && $amount > 0) {
                $up = $this->pdo->prepare("UPDATE members SET spend = spend + ? WHERE id = ?");
                $up->execute([$amount, $memberId]);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'txn_id'  => $txnId,
                'amount'  => $amount
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error'   => 'Payment transaction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper for quick member payment recording.
     */
    public function recordPayment(int $memberId, float $amount, string $description, string $method = 'Credit Card'): string
    {
        $res = $this->recordTransaction([
            'amount' => $amount,
            'name' => 'Member Payment',
            'description' => $description,
            'type' => 'Green fee',
            'method' => $method,
            'member_id' => $memberId,
        ]);
        return (string)($res['txn_id'] ?? '');
    }
}
