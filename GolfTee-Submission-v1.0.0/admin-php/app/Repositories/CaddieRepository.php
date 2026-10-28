<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class CaddieRepository
{
    // ──────────────────────────────────────────────
    // Caddie CRUD
    // ──────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM caddies WHERE id = ?', [$id]);
    }

    public function getAll(string $status = 'All', string $query = '', string $sort = 'name', string $dir = 'asc'): array
    {
        $allowed = ['name', 'experience_years', 'rate_per_round', 'status', 'rating'];
        $col = in_array($sort, $allowed, true) ? $sort : 'name';
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $where = ['1=1'];
        $params = [];

        if ($status !== 'All' && $status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($query !== '') {
            $like = '%' . Database::escapeLike($query) . '%';
            $where[] = '(name LIKE ? OR phone LIKE ? OR specialty LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT * FROM caddies WHERE ' . implode(' AND ', $where)
             . " ORDER BY {$col} {$direction}";
        return Database::fetchAll($sql, $params);
    }

    public function getAvailableOnDate(string $date): array
    {
        $sql = "
            SELECT c.*
            FROM caddies c
            WHERE c.status = 'Available'
              AND c.id NOT IN (
                SELECT cb.caddie_id
                FROM caddie_bookings cb
                WHERE cb.booking_date = ?
                  AND cb.status NOT IN ('Cancelled')
              )
            ORDER BY c.rating DESC, c.name ASC
        ";
        return Database::fetchAll($sql, [$date]);
    }

    public function getStatusCounts(): array
    {
        $rows = Database::fetchAll('SELECT status, COUNT(*) as c FROM caddies GROUP BY status');
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['c'];
        }
        return $map;
    }

    public function getTotalCount(): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM caddies');
    }

    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO caddies (name, phone, experience_years, specialty, rate_per_round, rating, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['phone'] ?? '',
                (int)($data['experience_years'] ?? 0),
                $data['specialty'] ?? '',
                (float)($data['rate_per_round'] ?? 0),
                (float)($data['rating'] ?? 0),
                $data['status'] ?? 'Available',
                $data['notes'] ?? '',
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return Database::execute(
            'UPDATE caddies SET name = ?, phone = ?, experience_years = ?, specialty = ?, rate_per_round = ?, rating = ?, status = ?, notes = ? WHERE id = ?',
            [
                $data['name'],
                $data['phone'] ?? '',
                (int)($data['experience_years'] ?? 0),
                $data['specialty'] ?? '',
                (float)($data['rate_per_round'] ?? 0),
                (float)($data['rating'] ?? 0),
                $data['status'] ?? 'Available',
                $data['notes'] ?? '',
                $id,
            ]
        );
    }

    public function updateStatus(int $id, string $status): bool
    {
        return Database::execute('UPDATE caddies SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM caddies WHERE id = ?', [$id]);
    }

    // ──────────────────────────────────────────────
    // Caddie Bookings
    // ──────────────────────────────────────────────

    public function getBookings(string $status = 'All', string $dateFrom = '', string $dateTo = '', int $caddieId = 0): array
    {
        $where = ['1=1'];
        $params = [];

        if ($status !== 'All' && $status !== '') {
            $where[] = 'cb.status = ?';
            $params[] = $status;
        }

        if ($dateFrom !== '') {
            $where[] = 'cb.booking_date >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'cb.booking_date <= ?';
            $params[] = $dateTo;
        }

        if ($caddieId > 0) {
            $where[] = 'cb.caddie_id = ?';
            $params[] = $caddieId;
        }

        $sql = "
            SELECT cb.*, c.name as caddie_name, c.rate_per_round
            FROM caddie_bookings cb
            JOIN caddies c ON cb.caddie_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY cb.booking_date DESC, cb.tee_slot ASC
        ";
        return Database::fetchAll($sql, $params);
    }

    public function findBookingById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT cb.*, c.name as caddie_name, c.rate_per_round, c.phone as caddie_phone
             FROM caddie_bookings cb
             JOIN caddies c ON cb.caddie_id = c.id
             WHERE cb.id = ?",
            [$id]
        );
    }

    public function isBookingConflict(int $caddieId, string $date, int $slot, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM caddie_bookings WHERE caddie_id = ? AND booking_date = ? AND tee_slot = ? AND status != 'Cancelled'";
        $params = [$caddieId, $date, $slot];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return !empty(Database::fetchColumn($sql, $params));
    }

    public function createBooking(array $data): int
    {
        Database::query(
            'INSERT INTO caddie_bookings (caddie_id, golfer_name, member_id, booking_date, tee_slot, tee, holes, party, fee, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$data['caddie_id'],
                $data['golfer_name'],
                isset($data['member_id']) && $data['member_id'] ? (int)$data['member_id'] : null,
                $data['booking_date'],
                (int)$data['tee_slot'],
                (int)($data['tee'] ?? 0),
                (int)($data['holes'] ?? 18),
                (int)($data['party'] ?? 1),
                (float)($data['fee'] ?? 0),
                $data['status'] ?? 'Confirmed',
                $data['notes'] ?? '',
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function updateBooking(int $id, array $data): bool
    {
        return Database::execute(
            'UPDATE caddie_bookings SET caddie_id = ?, golfer_name = ?, member_id = ?, booking_date = ?, tee_slot = ?, tee = ?, holes = ?, party = ?, fee = ?, status = ?, notes = ? WHERE id = ?',
            [
                (int)$data['caddie_id'],
                $data['golfer_name'],
                isset($data['member_id']) && $data['member_id'] ? (int)$data['member_id'] : null,
                $data['booking_date'],
                (int)$data['tee_slot'],
                (int)($data['tee'] ?? 0),
                (int)($data['holes'] ?? 18),
                (int)($data['party'] ?? 1),
                (float)($data['fee'] ?? 0),
                $data['status'] ?? 'Confirmed',
                $data['notes'] ?? '',
                $id,
            ]
        );
    }

    public function updateBookingStatus(int $id, string $status): bool
    {
        return Database::execute('UPDATE caddie_bookings SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function deleteBooking(int $id): bool
    {
        return Database::execute('DELETE FROM caddie_bookings WHERE id = ?', [$id]);
    }

    public function getBookingCountsByStatus(): array
    {
        $rows = Database::fetchAll('SELECT status, COUNT(*) as c FROM caddie_bookings GROUP BY status');
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['c'];
        }
        return $map;
    }

    public function getTodayBookings(): array
    {
        return Database::fetchAll(
            "SELECT cb.*, c.name as caddie_name FROM caddie_bookings cb JOIN caddies c ON cb.caddie_id = c.id WHERE cb.booking_date = CURDATE() AND cb.status != 'Cancelled' ORDER BY cb.tee_slot ASC",
            []
        );
    }

    public function getUpcomingBookings(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT cb.*, c.name as caddie_name FROM caddie_bookings cb JOIN caddies c ON cb.caddie_id = c.id WHERE cb.booking_date >= CURDATE() AND cb.status != 'Cancelled' ORDER BY cb.booking_date ASC, cb.tee_slot ASC LIMIT " . (int)$limit,
            []
        );
    }

    public function getRevenueTotal(): float
    {
        return (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(fee), 0) FROM caddie_bookings WHERE status IN ('Confirmed','Completed')"
        );
    }

    public function getBookingsByDateRange(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT cb.*, c.name as caddie_name FROM caddie_bookings cb JOIN caddies c ON cb.caddie_id = c.id WHERE cb.booking_date BETWEEN ? AND ? ORDER BY cb.booking_date ASC, cb.tee_slot ASC",
            [$from, $to]
        );
    }
}
