<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class NotificationRepository
{
    public function add(string $title, string $desc, string $page, ?string $focusData = null, string $tone = 'gray'): bool
    {
        return Database::execute(
            'INSERT INTO notifications (title, description, page, focus_data, tone) VALUES (?, ?, ?, ?, ?)',
            [$title, $desc, $page, $focusData, $tone]
        );
    }

    public function getRecent(int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM notifications ORDER BY created_at DESC LIMIT ' . (int)$limit
        );
    }

    public function getUnreadCount(): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
    }

    public function markAsRead(int $id): bool
    {
        return Database::execute('UPDATE notifications SET is_read = 1 WHERE id = ?', [$id]);
    }

    public function markAllAsRead(): bool
    {
        return Database::execute('UPDATE notifications SET is_read = 1');
    }
}
