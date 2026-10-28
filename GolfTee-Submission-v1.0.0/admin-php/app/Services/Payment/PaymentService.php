<?php
declare(strict_types=1);

namespace App\Services;

use App\Domain\SlotFormatter;
use App\Repositories\ActivityRepository;
use App\Repositories\MemberRepository;
use App\Repositories\TransactionRepository;

class PaymentService
{
    private TransactionRepository $txnRepo;
    private ActivityRepository $activityRepo;
    private MemberRepository $memberRepo;

    public function __construct(
        ?TransactionRepository $txnRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?MemberRepository $memberRepo = null
    ) {
        $this->txnRepo      = $txnRepo ?? new TransactionRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
    }

    public function listPayments(
        string $range = 'all',
        string $status = 'All',
        string $type = 'All',
        string $query = '',
        ?string $selectedId = null
    ): array {
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $rows = $this->txnRepo->queryTransactions($range, $status, $type, $query, $today, $yesterday);
        $statMap = $this->txnRepo->getStatusStats($range, $today, $yesterday);

        $selectedTxn = null;
        if ($selectedId) {
            $selectedTxn = $this->txnRepo->findById($selectedId);
        }

        $allMemberNames = $this->memberRepo->getAllNames();

        return [
            'rows'           => $rows,
            'statMap'        => $statMap,
            'selectedTxn'    => $selectedTxn,
            'selectedId'     => $selectedId,
            'range'          => $range,
            'status'         => $status,
            'type'           => $type,
            'query'          => $query,
            'allMemberNames' => $allMemberNames,
        ];
    }

    public function recordPayment(array $input): string
    {
        $name   = trim($input['name'] ?? '');
        $type   = $input['type'] ?? 'Green fee';
        $method = $input['method'] ?? 'Visa';
        $desc   = trim($input['description'] ?? '');
        $amount = (float)($input['amount'] ?? 0.0);

        $today = date('Y-m-d');
        $nowMin = (int)date('H') * 60 + (int)date('i');
        $txnId = $this->txnRepo->generateNextId();

        $this->txnRepo->create([
            'id'          => $txnId,
            'txn_date'    => $today,
            'txn_time'    => $nowMin,
            'name'        => $name,
            'description' => $desc,
            'type'        => $type,
            'method'      => $method,
            'amount'      => $amount,
            'status'      => 'Paid',
            'ref'         => null,
        ]);

        $this->activityRepo->log('Payment recorded', "{$name} · " . currencySymbol() . "{$amount}", 'forest');
        return $txnId;
    }

    public function refund(string $id): bool
    {
        $txn = $this->txnRepo->findById($id);
        if (!$txn) {
            return false;
        }

        $this->txnRepo->updateStatus($id, 'Refunded');
        $amountStr = number_format((float)($txn['amount'] ?? 0));
        $this->activityRepo->log('Refund issued', "{$txn['name']} · " . currencySymbol() . "{$amountStr}", 'danger');
        return true;
    }

    public function markPaid(string $id): bool
    {
        return $this->txnRepo->updateStatus($id, 'Paid');
    }

    public function exportCsv(string $range = 'all', string $status = 'All', string $type = 'All', string $query = ''): array
    {
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $rows = $this->txnRepo->queryTransactions($range, $status, $type, $query, $today, $yesterday);
        $export = [];
        foreach ($rows as $r) {
            $export[] = [
                $r['id'],
                $r['txn_date'],
                SlotFormatter::format((int)$r['txn_time']),
                $r['name'],
                $r['description'],
                $r['type'],
                $r['method'],
                $r['amount'],
                $r['status'],
            ];
        }
        return $export;
    }
}
