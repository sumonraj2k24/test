<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $path = (string) config('database');
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create database directory.');
        }
        $fresh = !is_file($path);
        self::$pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::$pdo->exec('PRAGMA busy_timeout = 5000');
        if ($fresh || !self::hasTable('users')) {
            self::install();
        }
        return self::$pdo;
    }

    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        return $stmt->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $row = self::run($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : $row[0];
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
        self::execute($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): void
    {
        if ($data === []) {
            return;
        }
        $sets = implode(', ', array_map(static fn (string $col): string => $col . ' = ?', array_keys($data)));
        self::execute(
            'UPDATE ' . $table . ' SET ' . $sets . ' WHERE ' . $where,
            [...array_values($data), ...$params]
        );
    }

    public static function delete(string $table, string $where, array $params = []): void
    {
        self::execute('DELETE FROM ' . $table . ' WHERE ' . $where, $params);
    }

    public static function tableExists(string $table): bool
    {
        return self::hasTable($table);
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function runFile(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException('SQL file missing: ' . $path);
        }
        $sql = (string) file_get_contents($path);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*(?:\n|$)/', $sql) ?: [];
        foreach ($parts as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                self::pdo()->exec($statement);
            }
        }
    }

    public static function path(): string
    {
        return (string) config('database');
    }

    private static function install(): void
    {
        self::runFile(BASE_PATH . '/database/schema.sql');
        require_once BASE_PATH . '/database/seeders/DemoSeeder.php';
        \Database\Seeders\DemoSeeder::run();
    }

    private static function hasTable(string $table): bool
    {
        $stmt = self::$pdo?->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->execute([$table]);
        return (bool) $stmt->fetch();
    }

    private static function run(string $sql, array $params): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($params));
        return $stmt;
    }
}
