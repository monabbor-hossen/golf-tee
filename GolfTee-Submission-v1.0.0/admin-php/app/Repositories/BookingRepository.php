<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class BookingRepository
{
    public function findById(int $id, ?int $authenticatedMemberId = null, bool $isStaff = true): ?array
    {
        $sql = 'SELECT b.*, m.name as member_name, m.email as member_email FROM bookings b LEFT JOIN members m ON b.member_id = m.id WHERE b.id = ?';
        $booking = Database::fetchOne($sql, [$id]);
        if (!$booking) {
            return null;
        }
        if ($authenticatedMemberId !== null && (int)($booking['member_id'] ?? 0) !== $authenticatedMemberId && !$isStaff) {
            return null;
        }
        return $booking;
    }

    public function findByTeeTimeRange(string $date): array
    {
        // Replaces WHERE DATE(tee_time) = ? with range comparison to allow index usage on tee_time
        return Database::fetchAll(
            'SELECT * FROM bookings WHERE tee_time >= ? AND tee_time < DATE_ADD(?, INTERVAL 1 DAY)',
            [$date, $date]
        );
    }

    public function getByDate(string $date): array
    {
        return Database::fetchAll(
            'SELECT * FROM bookings WHERE tee_date = ? ORDER BY tee_slot ASC, tee ASC',
            [$date]
        );
    }

    public function getTodayBookingsWithAmount(string $today): array
    {
        $sql = "
            SELECT b.*, t.amount
            FROM bookings b
            LEFT JOIN transactions t ON t.ref = CONCAT(b.tee_date, '|', b.tee, '-', b.tee_slot)
            WHERE b.tee_date = ? AND b.status != 'No-show'
            ORDER BY b.tee_slot ASC, b.tee ASC
        ";
        return Database::fetchAll($sql, [$today]);
    }

    public function slotExists(string $date, int $slot, int $tee): bool
    {
        $id = Database::fetchColumn(
            'SELECT id FROM bookings WHERE tee_date = ? AND tee_slot = ? AND tee = ?',
            [$date, $slot, $tee]
        );
        return !empty($id);
    }

    public function cancelBooking(int $id, ?int $memberId = null): bool
    {
        if ($memberId !== null) {
            return Database::execute("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND member_id = ?", [$id, $memberId]);
        }
        return Database::execute("UPDATE bookings SET status = 'Cancelled' WHERE id = ?", [$id]);
    }

    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO bookings (tee_date, tee_slot, tee, name, party, holes, status, member_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['tee_date'],
                (int)$data['tee_slot'],
                (int)$data['tee'],
                $data['name'],
                (int)$data['party'],
                (int)$data['holes'],
                $data['status'] ?? 'Booked',
                $data['member_id'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return Database::execute('UPDATE bookings SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM bookings WHERE id = ?', [$id]);
    }

    public function getStartingSoon(string $date, int $fromSlot, int $limit = 7): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM bookings WHERE tee_date = ? AND tee_slot >= ? AND status != 'No-show' ORDER BY tee_slot ASC, tee ASC LIMIT ?");
        $stmt->bindValue(1, $date, \PDO::PARAM_STR);
        $stmt->bindValue(2, $fromSlot, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLateBookings(string $date, int $nowMin, int $limit = 5): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM bookings WHERE tee_date = ? AND status = 'Booked' AND tee_slot < ? ORDER BY tee_slot ASC LIMIT ?");
        $stmt->bindValue(1, $date, \PDO::PARAM_STR);
        $stmt->bindValue(2, $nowMin, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByMemberId(int $memberId, int $limit = 5): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM bookings WHERE member_id = ? ORDER BY tee_date DESC, tee_slot DESC LIMIT ?');
        $stmt->bindValue(1, $memberId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getBookedSlotsMap(string $date): array
    {
        $rows = Database::fetchAll('SELECT tee, tee_slot FROM bookings WHERE tee_date = ?', [$date]);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['tee'] . '-' . $r['tee_slot']] = true;
        }
        return $map;
    }

    public function getStatsByDateRange(string $fromDate): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) as cnt FROM bookings WHERE tee_date >= ? GROUP BY status',
            [$fromDate]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['cnt'];
        }
        return $map;
    }

    public function getBookingsByDayOfWeek(string $fromDate): array
    {
        $rows = Database::fetchAll(
            'SELECT DAYOFWEEK(tee_date) as dow, COUNT(*) as cnt FROM bookings WHERE tee_date >= ? GROUP BY dow ORDER BY dow',
            [$fromDate]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['dow']] = (int)$r['cnt'];
        }
        return $map;
    }

    public function getSortedBookings(string $sort = 'tee_date'): array
    {
        if (!in_array($sort, ['tee_date', 'tee_slot', 'status'], true)) {
            $sort = 'tee_date';
        }
        return Database::fetchAll("SELECT * FROM bookings ORDER BY {$sort} ASC");
    }
}
