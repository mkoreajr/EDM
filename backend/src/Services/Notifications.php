<?php

namespace App\Services;

use App\Core\DB;

/**
 * In-app notifications for users.
 */
final class Notifications
{
    public static function send(int $userId, string $title, string $message): void
    {
        DB::execute(
            'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)',
            [$userId, $title, $message]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public static function count(int $userId): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$userId]);
    }

    /** @return list<array<string,mixed>> */
    public static function latest(int $userId, int $limit = 5, int $offset = 0): array
    {
        return DB::all(
            'SELECT id, title, message, read_at, created_at FROM notifications
             WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    public static function markRead(int $userId, int $id): void
    {
        DB::execute(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND read_at IS NULL',
            [$id, $userId]
        );
    }

    public static function clear(int $userId): void
    {
        DB::execute('DELETE FROM notifications WHERE user_id = ?', [$userId]);
    }
}
