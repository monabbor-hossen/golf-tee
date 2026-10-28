<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class NotificationRepository
{
    private const COLUMNS = 'id, member_id, title, description, tone, page, focus_data, is_read, created_at';

    public function add(string $title, string $desc, string $page, ?string $focusData = null, string $tone = 'gray', ?int $memberId = null): bool
    {
        return Database::execute(
            'INSERT INTO notifications (title, description, page, focus_data, tone, member_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$title, $desc, $page, $focusData, $tone, $memberId]
        );
    }

    public function getRecent(int $limit = 20): array
    {
        $db = Database::pdo();
        $cols = self::COLUMNS;
        $stmt = $db->prepare("SELECT {$cols} FROM notifications ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMemberId(int $memberId, int $limit = 50): array
    {
        $db = Database::pdo();
        $cols = self::COLUMNS;
        $stmt = $db->prepare("SELECT {$cols} FROM notifications WHERE member_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(): int
    {
        return (int)Database::fetchColumn('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
    }

    public function markAsRead(int $id): bool
    {
        return Database::execute('UPDATE notifications SET is_read = 1 WHERE id = ?', [$id]);
    }

    public function getById(int $id): ?array
    {
        $cols = self::COLUMNS;
        return Database::fetch("SELECT {$cols} FROM notifications WHERE id = ?", [$id]);
    }

    public function markAllAsRead(?int $memberId = null): bool
    {
        if ($memberId !== null) {
            return Database::execute('UPDATE notifications SET is_read = 1 WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)', [$memberId]);
        }
        return Database::execute('UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)');
    }

    public function markAllAsReadForMember(int $memberId): bool
    {
        return Database::execute('UPDATE notifications SET is_read = 1 WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)', [$memberId]);
    }
}
