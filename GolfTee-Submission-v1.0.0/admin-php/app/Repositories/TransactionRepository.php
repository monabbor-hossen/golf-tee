<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class TransactionRepository
{
    public function findById(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM transactions WHERE id = ?', [$id]);
    }

    public function generateNextId(): string
    {
        $r = Database::fetchOne('SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) as mx FROM transactions');
        $next = ($r['mx'] ?? 48210) + 1;
        return 'TX-' . $next;
    }

    public function create(array $data): bool
    {
        return Database::execute(
            'INSERT INTO transactions (id, txn_date, txn_time, name, description, type, method, amount, status, ref) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id'],
                $data['txn_date'],
                (int)$data['txn_time'],
                $data['name'],
                $data['description'] ?? '',
                $data['type'] ?? 'Green fee',
                $data['method'] ?? 'Visa',
                (float)$data['amount'],
                $data['status'] ?? 'Paid',
                $data['ref'] ?? null,
            ]
        );
    }

    public function updateStatus(string $id, string $status): bool
    {
        return Database::execute('UPDATE transactions SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function updateStatusByRef(string $ref, string $status): bool
    {
        return Database::execute('UPDATE transactions SET status = ? WHERE ref = ?', [$status, $ref]);
    }

    public function queryTransactions(
        string $range = 'all',
        string $status = 'All',
        string $type = 'All',
        string $query = '',
        string $today = '',
        string $yesterday = ''
    ): array {
        $where = ['1=1'];
        $params = [];

        if ($range === 'today' && $today !== '') {
            $where[] = 'txn_date = ?';
            $params[] = $today;
        } elseif ($range === 'yesterday' && $yesterday !== '') {
            $where[] = 'txn_date = ?';
            $params[] = $yesterday;
        }

        if ($status !== 'All') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($type !== 'All') {
            $where[] = 'type = ?';
            $params[] = $type;
        }

        if ($query !== '') {
            $where[] = '(name LIKE ? OR description LIKE ? OR id LIKE ?)';
            $term = '%' . Database::escapeLike($query) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = 'SELECT * FROM transactions WHERE ' . implode(' AND ', $where) . ' ORDER BY txn_date DESC, txn_time DESC';
        return Database::fetchAll($sql, $params);
    }

    public function getStatusStats(string $range = 'all', string $today = '', string $yesterday = ''): array
    {
        $where = '1=1';
        $params = [];

        if ($range === 'today' && $today !== '') {
            $where = 'txn_date = ?';
            $params[] = $today;
        } elseif ($range === 'yesterday' && $yesterday !== '') {
            $where = 'txn_date = ?';
            $params[] = $yesterday;
        }

        $sql = "SELECT status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total FROM transactions WHERE {$where} GROUP BY status";
        $rows = Database::fetchAll($sql, $params);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = $r;
        }
        return $map;
    }

    public function getPendingCountAndSum(): array
    {
        $row = Database::fetchOne("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total FROM transactions WHERE status = 'Pending'");
        return [
            'count' => (int)($row['cnt'] ?? 0),
            'sum'   => (float)($row['total'] ?? 0.0),
        ];
    }

    public function getRevenueByType(string $fromDate): array
    {
        $sql = "
            SELECT type, COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt
            FROM transactions
            WHERE status = 'Paid' AND txn_date >= ?
            GROUP BY type
            ORDER BY total DESC
        ";
        return Database::fetchAll($sql, [$fromDate]);
    }

    public function getDailyRevenue(string $fromDate): array
    {
        $sql = "
            SELECT txn_date, COALESCE(SUM(amount), 0) as rev
            FROM transactions
            WHERE status = 'Paid' AND txn_date >= ?
            GROUP BY txn_date
            ORDER BY txn_date ASC
        ";
        $rows = Database::fetchAll($sql, [$fromDate]);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['txn_date']] = (float)$r['rev'];
        }
        return $map;
    }

    public function search(string $query, int $limit = 3): array
    {
        $term = '%' . Database::escapeLike($query) . '%';
        $stmt = Database::pdo()->prepare('SELECT * FROM transactions WHERE id LIKE ? OR name LIKE ? ORDER BY txn_date DESC LIMIT ?');
        $stmt->bindValue(1, $term, \PDO::PARAM_STR);
        $stmt->bindValue(2, $term, \PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByMemberId(int $memberId, int $limit = 100): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM transactions WHERE member_id = ? ORDER BY created_at DESC LIMIT 100');
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
