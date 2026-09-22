<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class NotificationService
{
    public static function push(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        Database::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'created_at' => now(),
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        );
    }

    public static function markAll(int $userId): void
    {
        Database::execute(
            'UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL',
            [now(), $userId]
        );
    }
}
