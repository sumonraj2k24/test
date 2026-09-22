<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;
    private static ?array $permissions = null;

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;
        $id = Session::get('user_id');
        if (!$id) {
            return self::$user = null;
        }
        $user = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$user || $user['status'] !== 'active') {
            Session::forget('user_id');
            return self::$user = null;
        }
        return self::$user = $user;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::first('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        if (!$user || !is_string($user['password']) || !password_verify($password, $user['password'])) {
            return false;
        }
        if ($user['status'] === 'suspended') {
            Session::flash('error', 'This account is suspended. Write to the studio if that seems wrong.');
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;
        self::$resolved = true;
        self::$permissions = null;
        \App\Services\CartService::mergeSession((int) $user['id']);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$resolved = true;
        self::$permissions = null;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array($permission, self::permissions(), true);
    }

    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $user = self::user();
        if (!$user) {
            return self::$permissions = [];
        }
        $rows = Database::select(
            'SELECT p.slug FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role = ?',
            [$user['role']]
        );
        return self::$permissions = array_column($rows, 'slug');
    }

    public static function flush(): void
    {
        self::$user = null;
        self::$resolved = false;
        self::$permissions = null;
    }
}
