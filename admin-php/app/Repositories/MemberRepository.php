<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class MemberRepository
{
    public function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM members WHERE id = ?', [$id]);
    }

    public function findByName(string $name): ?array
    {
        return Database::fetchOne('SELECT * FROM members WHERE name = ? LIMIT 1', [$name]);
    }

    public function getActiveNames(): array
    {
        return Database::fetchAll("SELECT name FROM members WHERE status = 'Active' ORDER BY name");
    }

    public function getAllNames(): array
    {
        return Database::fetchAll("SELECT name FROM members ORDER BY name");
    }

    public function queryMembers(
        string $query = '',
        string $tier = 'All',
        string $status = 'All',
        string $sort = 'name',
        string $dir = 'ASC',
        string $spend = 'All'
    ): array {
        $allowedSorts = ['name', 'tier', 'hcp', 'rounds', 'spend', 'status'];
        $sortCol = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $where = ['1=1'];
        $params = [];

        if ($query !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $search = '%' . Database::escapeLike($query) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if ($tier !== 'All') {
            $where[] = 'tier = ?';
            $params[] = $tier;
        }

        if ($status !== 'All') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($spend !== 'All' && $spend !== '') {
            if ($spend === '5000+' || $spend === 'top') {
                $where[] = 'spend >= 5000';
            } elseif ($spend === '2000-5000') {
                $where[] = 'spend >= 2000 AND spend < 5000';
            } elseif ($spend === '1000-2000') {
                $where[] = 'spend >= 1000 AND spend < 2000';
            } elseif ($spend === 'under1000') {
                $where[] = 'spend < 1000';
            }
        }

        // Top to bottom and custom sort mappings
        if ($sortCol === 'tier') {
            // Top to bottom (ASC): Gold -> Silver -> Standard
            // Bottom to top (DESC): Standard -> Silver -> Gold
            $orderClause = "CASE tier WHEN 'Gold' THEN 1 WHEN 'Silver' THEN 2 WHEN 'Standard' THEN 3 ELSE 4 END {$direction}, spend DESC";
        } elseif ($sortCol === 'status') {
            // Top to bottom (ASC): Active -> Pending -> Suspended
            // Bottom to top (DESC): Suspended -> Pending -> Active
            $orderClause = "CASE status WHEN 'Active' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Suspended' THEN 3 ELSE 4 END {$direction}, name ASC";
        } elseif ($sortCol === 'spend') {
            // Top to bottom (DESC): Highest spend to lowest spend
            // Bottom to top (ASC): Lowest spend to highest spend
            $orderClause = "spend {$direction}, name ASC";
        } elseif ($sortCol === 'rounds') {
            $orderClause = "rounds {$direction}, name ASC";
        } elseif ($sortCol === 'hcp') {
            $orderClause = "hcp {$direction}, name ASC";
        } else {
            $orderClause = "name {$direction}";
        }

        $sql = 'SELECT * FROM members WHERE ' . implode(' AND ', $where) . " ORDER BY {$orderClause}";
        return Database::fetchAll($sql, $params);
    }

    public function getStatusCounts(): array
    {
        $rows = Database::fetchAll('SELECT status, COUNT(*) as c FROM members GROUP BY status');
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['c'];
        }
        return $map;
    }

    public function getTotalCount(): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM members');
    }

    public function getPendingCount(): int
    {
        return (int)Database::fetchColumn("SELECT COUNT(*) FROM members WHERE status = 'Pending'");
    }

    public function getPendingMembers(int $limit = 5): array
    {
        return Database::fetchAll("SELECT * FROM members WHERE status = 'Pending' LIMIT " . (int)$limit);
    }

    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO members (name, email, phone, tier, hcp, status, joined) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['tier'] ?? 'Standard',
                (float)($data['hcp'] ?? 36.0),
                $data['status'] ?? 'Pending',
                $data['joined'] ?? date('M Y')
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return Database::execute(
            'UPDATE members SET name = ?, email = ?, phone = ?, tier = ?, hcp = ?, status = ? WHERE id = ?',
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['tier'] ?? 'Standard',
                (float)($data['hcp'] ?? 36.0),
                $data['status'] ?? 'Active',
                $id
            ]
        );
    }

    public function updateStatus(int $id, string $status): bool
    {
        return Database::execute('UPDATE members SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM members WHERE id = ?', [$id]);
    }

    public function search(string $query, int $limit = 4): array
    {
        $term = '%' . Database::escapeLike($query) . '%';
        return Database::fetchAll(
            "SELECT * FROM members WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? LIMIT " . (int)$limit,
            [$term, $term, $term]
        );
    }

    public function getTopSpenders(string $fromDate, int $limit = 5): array
    {
        $sql = "
            SELECT m.name, m.tier, COALESCE(SUM(t.amount),0) as spend, COUNT(DISTINCT b.id) as rounds
            FROM members m
            LEFT JOIN transactions t ON t.name = m.name AND t.status = 'Paid'
            LEFT JOIN bookings b ON b.member_id = m.id AND b.tee_date >= ?
            GROUP BY m.id
            ORDER BY spend DESC
            LIMIT " . (int)$limit;
        return Database::fetchAll($sql, [$fromDate]);
    }
}
