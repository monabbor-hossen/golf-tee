<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class ActivityRepository
{
    public function log(string $title, string $desc, string $tone = 'gray'): bool
    {
        return Database::execute(
            'INSERT INTO activity_log (title, description, tone) VALUES (?, ?, ?)',
            [$title, $desc, $tone]
        );
    }

    public function getRecent(int $limit = 7): array
    {
        return Database::fetchAll(
            'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ' . (int)$limit
        );
    }
}
