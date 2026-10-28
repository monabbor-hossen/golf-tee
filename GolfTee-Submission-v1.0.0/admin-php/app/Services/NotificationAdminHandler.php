<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\NotificationRepository;
use PDO;

/**
 * Dedicated handler for staff notifications AJAX endpoints with strict multi-tenant member scoping.
 */
class NotificationAdminHandler
{
    private NotificationRepository $notifRepo;

    public function __construct(?NotificationRepository $notifRepo = null)
    {
        $this->notifRepo = $notifRepo ?? new NotificationRepository();
    }

    public function handle(Request $request): void
    {
        Session::requireStaff();
        Session::validateRequestCsrf($request);

        $action = $request->getString('action');

        if ($action === 'mark_read') {
            $id = $request->getInt('id');
            $memberId = (int)Session::getUserId();
            
            // Multi-tenant scoping: WHERE member_id = ? AND id = ?
            $db = Database::pdo();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE member_id = ? AND id = ?");
            $stmt->execute([$memberId, $id]);

            $this->notifRepo->markAsRead($id);
            Response::json(['ok' => true]);
            return;
        }

        if ($action === 'mark_all_read') {
            $memberId = (int)Session::getUserId();
            $db = Database::pdo();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE member_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $stmt->execute([$memberId]);

            $this->notifRepo->markAllAsRead($memberId);
            Response::json(['ok' => true]);
            return;
        }

        if ($action === 'delete') {
            $id = $request->getInt('id');
            $memberId = (int)Session::getUserId();
            $db = Database::pdo();
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND member_id = ?");
            $stmt->execute([$id, $memberId]);
            Response::json(['ok' => true]);
            return;
        }

        Response::json(['error' => 'Unknown action'], 400);
    }

    /**
     * Scoped member query preventing cross-tenant data leaks.
     */
    public function getNotificationsForMember(int $memberId, int $limit = 50): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT id, member_id, title, description, tone, page, focus_data, is_read, created_at FROM notifications WHERE member_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsReadForMember(int $id, int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE member_id = ? AND id = ?");
        return $stmt->execute([$memberId, $id]);
    }

    public function deleteForMember(int $id, int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare("DELETE FROM notifications WHERE member_id = ? AND id = ?");
        return $stmt->execute([$memberId, $id]);
    }
}
