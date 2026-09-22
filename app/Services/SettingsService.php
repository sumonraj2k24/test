<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SettingsService
{
    private static ?array $all = null;

    public static function warm(): void
    {
        self::$all = [];
        if (!Database::tableExists('settings')) {
            return;
        }
        foreach (Database::select('SELECT key, value FROM settings') as $row) {
            self::$all[$row['key']] = $row['value'];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$all === null) {
            self::warm();
        }
        if (!array_key_exists($key, self::$all) || self::$all[$key] === null || self::$all[$key] === '') {
            return $default;
        }
        return self::$all[$key];
    }

    public static function all(): array
    {
        if (self::$all === null) {
            self::warm();
        }
        return self::$all;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::execute(
            'INSERT INTO settings (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value',
            [$key, $value]
        );
        if (self::$all === null) {
            self::$all = [];
        }
        self::$all[$key] = $value;
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::set((string) $key, $value === null ? null : (string) $value);
        }
    }
}
