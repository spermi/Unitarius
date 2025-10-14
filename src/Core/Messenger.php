<?php
declare(strict_types=1);

namespace Core;

use PDO;
use Throwable;

final class Messenger
{
    // ---------------------------------------------------------
    // Send a message to one specific user.
    // ---------------------------------------------------------
    public static function send(
        int $toUserId,
        string $title,
        string $body,
        ?string $url = null,
        string $type = 'info',
        ?int $fromUserId = null
    ): void {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO messages (recipient_user_id, title, body, url, type, from_user_id)
                VALUES (:uid, :title, :body, :url, :type, :from_uid)
            ');
            $stmt->execute([
                ':uid'      => $toUserId,
                ':title'    => $title,
                ':body'     => $body,
                ':url'      => $url,
                ':type'     => $type,
                ':from_uid' => $fromUserId,
            ]);
        } catch (Throwable $e) {
            error_log('[Messenger::send] ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------
    // Broadcast a message to all users having a given role.
    // ---------------------------------------------------------
    public static function broadcastRole(
        string $roleName,
        string $title,
        string $body,
        ?string $url = null,
        string $type = 'info',
        ?int $fromUserId = null
    ): void {
        try {
            $pdo = DB::pdo();

            // Get all user IDs with the given role
            $query = '
                SELECT ur.user_id
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name = :role
            ';
            $stmt = $pdo->prepare($query);
            $stmt->execute([':role' => $roleName]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!$userIds) {
                return;
            }

            $pdo->beginTransaction();
            $insert = $pdo->prepare('
                INSERT INTO messages (recipient_user_id, recipient_role, title, body, url, type, from_user_id)
                VALUES (:uid, :role, :title, :body, :url, :type, :from_uid)
            ');
            foreach ($userIds as $uid) {
                $insert->execute([
                    ':uid'      => $uid,
                    ':role'     => $roleName,
                    ':title'    => $title,
                    ':body'     => $body,
                    ':url'      => $url,
                    ':type'     => $type,
                    ':from_uid' => $fromUserId,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Messenger::broadcastRole] ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------
    // Broadcast a message to all users having a given permission.
    // ---------------------------------------------------------

    public static function broadcastPermission(
        string $permissionName,
        string $title,
        string $body,
        ?string $url = null,
        string $type = 'info',
        ?int $fromUserId = null
    ): void {
        try {
            $pdo = DB::pdo();

            // Find all users who have this permission (via roles)
            $query = '
                SELECT DISTINCT ur.user_id
                FROM user_roles ur
                JOIN role_permissions rp ON ur.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE p.name = :perm
            ';
            $stmt = $pdo->prepare($query);
            $stmt->execute([':perm' => $permissionName]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!$userIds) {
                return;
            }

            $pdo->beginTransaction();
            $insert = $pdo->prepare('
                INSERT INTO messages (recipient_user_id, title, body, url, type, from_user_id)
                VALUES (:uid, :title, :body, :url, :type, :from_uid)
            ');
            foreach ($userIds as $uid) {
                $insert->execute([
                    ':uid'      => $uid,
                    ':title'    => $title,
                    ':body'     => $body,
                    ':url'      => $url,
                    ':type'     => $type,
                    ':from_uid' => $fromUserId,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Messenger::broadcastPermission] ' . $e->getMessage());
        }
    }




// ---------------------------------------------------------
// Get unread messages for a given user (show avatar for new_user).
// ---------------------------------------------------------
public static function getUnreadFor(int $userId, int $limit = 10): array
{
    try {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('
            SELECT 
                m.id,
                m.title,
                m.body,
                m.url,
                m.type,
                m.created_at,
                COALESCE(u.avatar, sender.avatar) AS sender_avatar
            FROM messages m
            LEFT JOIN users sender ON m.from_user_id = sender.id
            -- When message type is "new_user", extract user ID from URL (/users/{id})
            LEFT JOIN users u ON (
                m.type = \'new_user\' 
                AND CAST(SPLIT_PART(m.url, \'/users/\', 2) AS BIGINT) = u.id
            )
            WHERE m.recipient_user_id = :uid
              AND m.is_read = 0
            ORDER BY m.created_at DESC
            LIMIT :lim
        ');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[Messenger::getUnreadFor] ' . $e->getMessage());
        return [];
    }
}


    // ---------------------------------------------------------
    // Mark a single message as read.
    // ---------------------------------------------------------
    public static function markRead(int $messageId, ?int $userId = null): void
    {
        try {
            $pdo = DB::pdo();
            $sql = 'UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = :id';
            if ($userId !== null) {
                $sql .= ' AND recipient_user_id = :uid';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $messageId, PDO::PARAM_INT);
            if ($userId !== null) {
                $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            }
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('[Messenger::markRead] ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------
    // Mark all messages for a user as read.
    // ---------------------------------------------------------
    public static function markAllRead(int $userId): void
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                UPDATE messages SET is_read = 1, read_at = NOW()
                WHERE recipient_user_id = :uid AND is_read = 0
            ');
            $stmt->execute([':uid' => $userId]);
        } catch (Throwable $e) {
            error_log('[Messenger::markAllRead] ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------
    // Delete old read messages (default: older than 30 days).
    // Returns number of deleted records.
    // ---------------------------------------------------------
    public static function cleanupOld(int $days = 30): int
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                DELETE FROM messages
                WHERE is_read = 1 AND read_at < NOW() - (INTERVAL :days)
            ');
            $stmt->bindValue(':days', "{$days} days", PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log('[Messenger::cleanupOld] ' . $e->getMessage());
            return 0;
        }
    }
}
