<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class MemberRepository
{
    /**
     * In-memory cache for frequently accessed member records.
     * @var array<int, array>
     */
    private static array $cache = [];

    public function getById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function findById(int $id, ?int $authenticatedStaffId = null, ?int $authenticatedMemberId = null): ?array
    {
        // Enforce ownership if authenticated member context is passed
        if ($authenticatedMemberId !== null && $id !== $authenticatedMemberId && $authenticatedStaffId === null) {
            return null;
        }

        if (isset(self::$cache[$id])) {
            return self::$cache[$id];
        }

        $member = Database::fetchOne('SELECT * FROM members WHERE id = ?', [$id]);
        if ($member) {
            self::$cache[$id] = $member;
        }
        return $member;
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
        string $spend = 'All',
        ?int $authenticatedStaffId = null,
        int $limit = 200,
        int $offset = 0,
        ?int $authenticatedMemberId = null
    ): array {
        $allowedSorts = ['name', 'tier', 'hcp', 'rounds', 'spend', 'status'];
        $sortCol = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $where = ['1=1'];
        $params = [];

        // Scoping / ownership filter: non-staff members only query own data
        if ($authenticatedMemberId !== null && $authenticatedStaffId === null) {
            $where[] = 'id = ?';
            $params[] = $authenticatedMemberId;
        }

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
            $orderClause = "CASE tier WHEN 'Gold' THEN 1 WHEN 'Silver' THEN 2 WHEN 'Standard' THEN 3 ELSE 4 END {$direction}, spend DESC";
        } elseif ($sortCol === 'status') {
            $orderClause = "CASE status WHEN 'Active' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Suspended' THEN 3 ELSE 4 END {$direction}, name ASC";
        } elseif ($sortCol === 'spend') {
            $orderClause = "spend {$direction}, name ASC";
        } elseif ($sortCol === 'rounds') {
            $orderClause = "rounds {$direction}, name ASC";
        } elseif ($sortCol === 'hcp') {
            $orderClause = "hcp {$direction}, name ASC";
        } else {
            $orderClause = "name {$direction}";
        }

        $sql = 'SELECT * FROM members WHERE ' . implode(' AND ', $where) . " ORDER BY {$orderClause} LIMIT ? OFFSET ?";
        $stmt = Database::pdo()->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx++, max(1, min(500, $limit)), \PDO::PARAM_INT);
        $stmt->bindValue($idx++, max(0, $offset), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        $stmt = Database::pdo()->prepare("SELECT * FROM members WHERE status = 'Pending' LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO members (name, email, phone, tier, hcp, status, joined, setup_token, setup_token_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['tier'] ?? 'Standard',
                (float)($data['hcp'] ?? 36.0),
                $data['status'] ?? 'Pending',
                $data['joined'] ?? date('M Y'),
                $data['setup_token'] ?? null,
                $data['setup_token_expires'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM members WHERE LOWER(email) = LOWER(?)', [trim($email)]);
    }

    public function findByToken(string $token): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM members WHERE setup_token = ? AND setup_token_expires > NOW()',
            [trim($token)]
        );
    }

    public function setPasswordAndActivate(int $id, string $passwordHash): bool
    {
        return Database::execute(
            "UPDATE members SET password_hash = ?, setup_token = NULL, setup_token_expires = NULL, status = 'Active' WHERE id = ?",
            [$passwordHash, $id]
        );
    }

    public function setSetupToken(int $id, string $token, string $expires): bool
    {
        return Database::execute(
            'UPDATE members SET setup_token = ?, setup_token_expires = ? WHERE id = ?',
            [$token, $expires, $id]
        );
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
        if (strtolower($status) === 'suspended' && !\App\Core\Session::hasRole('manager') && !\App\Core\Session::hasRole('admin') && !\App\Core\Session::hasRole('owner')) {
            throw new \Exception('Insufficient privileges');
        }
        return Database::execute('UPDATE members SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function deleteMember(int $id): bool
    {
        unset(self::$cache[$id]);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM members WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() !== 1) {
            throw new \Exception('Delete failed');
        }
        return true;
    }

    public function delete(int $id): bool
    {
        return $this->deleteMember($id);
    }

    private function buildMemberQuery(array $filters = []): array
    {
        $sql = "SELECT * FROM members WHERE 1=1";
        $params = [];
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['tier']) && $filters['tier'] !== 'All') {
            $sql .= " AND tier = ?";
            $params[] = $filters['tier'];
        }
        if (!empty($filters['query'])) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $term = '%' . Database::escapeLike((string)$filters['query']) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        return ['sql' => $sql, 'params' => $params];
    }

    public function search(string $query, int $limit = 4): array
    {
        $built = $this->buildMemberQuery(['query' => $query]);
        $sql = $built['sql'] . " LIMIT " . max(1, min(100, $limit));
        return Database::fetchAll($sql, $built['params']);
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
            LIMIT ?";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(1, $fromDate, \PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
