<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class EventRepository
{
    public function findById(int $id): ?array
    {
        $sql = '
            SELECT e.*, s.name as creator_name, COUNT(r.id) as reg_count
            FROM events e
            LEFT JOIN staff s ON e.created_by = s.id
            LEFT JOIN event_registrations r ON r.event_id = e.id
            WHERE e.id = ?
            GROUP BY e.id, s.name
        ';
        return Database::fetchOne($sql, [$id]);
    }

    public function getEvents(string $tab, string $today): array
    {
        $operator = ($tab === 'upcoming') ? '>=' : '<';
        $order = ($tab === 'upcoming') ? 'ASC' : 'DESC';

        $sql = "
            SELECT e.*, COUNT(r.id) as reg_count
            FROM events e
            LEFT JOIN event_registrations r ON r.event_id = e.id
            WHERE e.event_date {$operator} ?
            GROUP BY e.id
            ORDER BY e.event_date {$order}
        ";
        return Database::fetchAll($sql, [$today]);
    }

    public function getNextEvent(string $today): ?array
    {
        $sql = "SELECT * FROM events WHERE event_date >= ? ORDER BY event_date ASC LIMIT 1";
        return Database::fetchOne($sql, [$today]);
    }

    public function getUpcomingCount(string $today): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM events WHERE event_date >= ?', [$today]);
    }

    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO events (name, event_date, start_slot, hours, format, capacity, fee, is_open, is_blocked) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)',
            [
                $data['name'],
                $data['event_date'],
                (int)$data['start_slot'],
                (int)$data['hours'],
                $data['format'],
                (int)$data['capacity'],
                (float)$data['fee'],
                !empty($data['is_blocked']) ? 1 : 0
            ]
        );
        return (int)Database::lastInsertId();
    }

    public function toggleOpen(int $id, bool $isOpen): bool
    {
        return Database::execute('UPDATE events SET is_open = ? WHERE id = ?', [$isOpen ? 1 : 0, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM events WHERE id = ?', [$id]);
    }

    public function search(string $query, int $limit = 3): array
    {
        $term = '%' . Database::escapeLike($query) . '%';
        $stmt = Database::pdo()->prepare('SELECT * FROM events WHERE name LIKE ? ORDER BY event_date ASC LIMIT ?');
        $stmt->bindValue(1, $term, \PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
