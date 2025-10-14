<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\View;
use Core\DB;
use Core\Messenger;

final class MessagesController
{
    // ---------------------------------------------------------
    // GET /messages
    // ---------------------------------------------------------
    public function index(): string
    {
        if (!\is_logged_in()) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        $cu = \current_user();
        $userId = (int)($cu['id'] ?? 0);

        // Fetch all messages (read + unread)
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('
            SELECT 
                m.id,
                m.title,
                m.body,
                m.url,
                m.type,
                m.is_read,
                m.created_at,
                COALESCE(u.avatar, sender.avatar) AS sender_avatar
            FROM messages m
            LEFT JOIN users sender ON m.from_user_id = sender.id
            LEFT JOIN users u ON (
                m.type = \'new_user\' 
                AND CAST(SPLIT_PART(m.url, \'/users/\', 2) AS BIGINT) = u.id
            )
            WHERE m.recipient_user_id = :uid
            ORDER BY m.created_at DESC
        ');
        $stmt->execute([':uid' => $userId]);
        $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('messages/list', [
            'title' => 'Üzeneteim',
            'messages' => $messages,
        ]);
    }

    // ---------------------------------------------------------
    // POST /messages/{id}/read
    // ---------------------------------------------------------
    public function markRead($id): void
{
    if (is_array($id)) {
        $id = (int)($id['id'] ?? 0);
    } else {
        $id = (int)$id;
    }

    if (!\is_logged_in()) {
        header('Location: ' . base_url('/login'));
        exit;
    }

    $cu = \current_user();
    \Core\Messenger::markRead($id, (int)$cu['id']);
    header('Location: ' . base_url('/messages'));
    exit;
}

}
