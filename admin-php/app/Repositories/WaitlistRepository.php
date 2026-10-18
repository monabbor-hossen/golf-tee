<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class WaitlistRepository
{
    public function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM waitlist WHERE id = ?', [$id]);
    }

    public function getEntries(string $tab, string $today): array
    {
        $operator = ($tab === 'upcoming') ? '>=' : '<';
        $order = ($tab === 'upcoming') ? 'ASC' : 'DESC';

        $sql = "
            SELECT w.*, DATEDIFF(w.tee_date, ?) as day_offset
            FROM waitlist w
            WHERE w.tee_date {$operator} ?
            ORDER BY w.tee_date {$order}, w.tee_slot ASC
        ";
        return Database::fetchAll($sql, [$today, $today]);
    }

    public function getUpcomingCount(string $today): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM waitlist WHERE tee_date >= ?', [$today]);
    }

    public function getTodayCount(string $today): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM waitlist WHERE tee_date = ?', [$today]);
    }

    public function getOfferedEntries(): array
    {
        return Database::fetchAll("SELECT * FROM waitlist WHERE status = 'Offered'");
    }

    public function add(array $data): int
    {
        Database::query(
            "INSERT INTO waitlist (name, tee_date, tee_slot, party, holes, status) VALUES (?, ?, ?, ?, ?, 'Waiting')",
            [
                $data['name'],
                $data['tee_date'],
                (int)$data['tee_slot'],
                (int)$data['party'],
                (int)$data['holes']
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return Database::execute('UPDATE waitlist SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function remove(int $id): bool
    {
        return Database::execute('DELETE FROM waitlist WHERE id = ?', [$id]);
    }
}
