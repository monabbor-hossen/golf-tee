<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class BookingRepository
{
    public function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM bookings WHERE id = ?', [$id]);
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
        return Database::fetchAll(
            "SELECT * FROM bookings WHERE tee_date = ? AND tee_slot >= ? AND status != 'No-show' ORDER BY tee_slot ASC, tee ASC LIMIT " . (int)$limit,
            [$date, $fromSlot]
        );
    }

    public function getLateBookings(string $date, int $nowMin, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT * FROM bookings WHERE tee_date = ? AND status = 'Booked' AND tee_slot < ? ORDER BY tee_slot ASC LIMIT " . (int)$limit,
            [$date, $nowMin]
        );
    }

    public function getByMemberId(int $memberId, int $limit = 5): array
    {
        return Database::fetchAll(
            'SELECT * FROM bookings WHERE member_id = ? ORDER BY tee_date DESC, tee_slot DESC LIMIT ' . (int)$limit,
            [$memberId]
        );
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
}
