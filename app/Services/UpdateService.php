<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class UpdateService
{
    public static function codeVersion(): string
    {
        return (string) config('version', '1.0.0');
    }

    public static function databaseVersion(): string
    {
        return (string) setting('db_version', '1.0.0');
    }

    public static function pending(): array
    {
        $current = self::databaseVersion();
        $files = glob(BASE_PATH . '/database/updates/*.sql') ?: [];
        $pending = [];
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (version_compare($version, $current, '<=')) {
                continue;
            }
            $notesFile = BASE_PATH . '/database/updates/' . $version . '.txt';
            $pending[] = [
                'version' => $version,
                'file' => $file,
                'notes' => is_file($notesFile) ? trim((string) file_get_contents($notesFile)) : '',
            ];
        }
        usort($pending, static fn (array $a, array $b): int => version_compare($a['version'], $b['version']));
        return $pending;
    }

    public static function applyNext(?int $userId): string
    {
        $pending = self::pending();
        if ($pending === []) {
            throw new \RuntimeException('The database is already current.');
        }
        $next = $pending[0];
        if (version_compare($next['version'], self::codeVersion(), '>')) {
            throw new \RuntimeException('This codebase cannot apply ' . $next['version'] . ' yet.');
        }
        BackupService::create($userId);
        Database::transaction(function () use ($next, $userId): void {
            Database::runFile($next['file']);
            Database::insert('system_updates', [
                'version' => $next['version'],
                'notes' => $next['notes'],
                'applied_at' => now(),
                'applied_by' => $userId,
            ]);
            SettingsService::set('db_version', $next['version']);
        });
        audit('system.update', $next['version']);
        return $next['version'];
    }
}
