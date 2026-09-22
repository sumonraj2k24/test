<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BackupService
{
    public static function create(?int $userId = null): array
    {
        $dir = storage_path('backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        Database::pdo()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        $name = 'meridian-' . gmdate('Ymd-His') . '.sqlite';
        $dest = $dir . '/' . $name;
        if (!copy(Database::path(), $dest)) {
            throw new \RuntimeException('Could not copy the database.');
        }
        $id = Database::insert('backups', [
            'filename' => $name,
            'size_bytes' => filesize($dest) ?: 0,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
        self::prune();
        SettingsService::set('last_backup_at', now());
        return ['id' => $id, 'filename' => $name, 'path' => $dest];
    }

    public static function due(): bool
    {
        $hours = (int) setting('backup_interval_hours', '24');
        if ($hours <= 0) {
            return false;
        }
        $last = setting('last_backup_at', '');
        if ($last === '') {
            return true;
        }
        return (strtotime($last . ' UTC') ?: 0) <= time() - ($hours * 3600);
    }

    public static function prune(): void
    {
        $keep = max(1, (int) setting('backup_retention', '8'));
        $rows = Database::select('SELECT * FROM backups ORDER BY datetime(created_at) DESC, id DESC');
        foreach (array_slice($rows, $keep) as $row) {
            $path = storage_path('backups/' . $row['filename']);
            if (is_file($path)) {
                unlink($path);
            }
            Database::delete('backups', 'id = ?', [(int) $row['id']]);
        }
    }
}
