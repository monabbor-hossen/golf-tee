<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class BlockedSlotRepository
{
    public function getByDate(string $date): array
    {
        return Database::fetchAll('SELECT * FROM blocked_slots WHERE tee_date = ?', [$date]);
    }

    public function getCountByDate(string $date): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM blocked_slots WHERE tee_date = ?', [$date]);
    }

    public function block(string $date, int $slot, int $tee, string $reason): bool
    {
        return Database::execute(
            'INSERT IGNORE INTO blocked_slots (tee_date, tee_slot, tee, reason) VALUES (?, ?, ?, ?)',
            [$date, $slot, $tee, $reason]
        );
    }

    public function unblock(int $id): bool
    {
        return Database::execute('DELETE FROM blocked_slots WHERE id = ?', [$id]);
    }
}
