<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;

final class CartService
{
    public static function ids(): array
    {
        if ($userId = Auth::id()) {
            $rows = Database::select('SELECT course_id FROM cart_items WHERE user_id = ?', [$userId]);
            return array_map('intval', array_column($rows, 'course_id'));
        }
        return array_values(array_unique(array_map('intval', Session::get('cart', []))));
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    public static function courses(): array
    {
        $ids = self::ids();
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return Database::select(
            "SELECT c.*, u.name AS instructor_name
             FROM courses c INNER JOIN users u ON u.id = c.instructor_id
             WHERE c.id IN ($placeholders) AND c.status = 'published'",
            $ids
        );
    }

    public static function add(int $courseId): void
    {
        $course = Database::first("SELECT id, price_cents, status FROM courses WHERE id = ? AND status = 'published'", [$courseId]);
        if (!$course) {
            flash('error', 'That course is not available.');
            return;
        }
        if ($userId = Auth::id()) {
            if (enrollment_for($userId, $courseId)) {
                flash('error', 'You are already enrolled in that course.');
                return;
            }
            Database::execute(
                'INSERT OR IGNORE INTO cart_items (user_id, course_id, created_at) VALUES (?, ?, ?)',
                [$userId, $courseId, now()]
            );
        } else {
            $cart = self::ids();
            $cart[] = $courseId;
            Session::put('cart', array_values(array_unique($cart)));
        }
        flash('success', 'Added to cart.');
    }

    public static function remove(int $courseId): void
    {
        if ($userId = Auth::id()) {
            Database::delete('cart_items', 'user_id = ? AND course_id = ?', [$userId, $courseId]);
        } else {
            Session::put('cart', array_values(array_filter(self::ids(), fn (int $id): bool => $id !== $courseId)));
        }
    }

    public static function clear(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ($userId) {
            Database::delete('cart_items', 'user_id = ?', [$userId]);
        }
        Session::put('cart', []);
    }

    public static function mergeSession(int $userId): void
    {
        foreach (Session::get('cart', []) as $courseId) {
            if (!enrollment_for($userId, (int) $courseId)) {
                Database::execute(
                    'INSERT OR IGNORE INTO cart_items (user_id, course_id, created_at) VALUES (?, ?, ?)',
                    [$userId, (int) $courseId, now()]
                );
            }
        }
        Session::put('cart', []);
    }

    public static function has(int $courseId): bool
    {
        return in_array($courseId, self::ids(), true);
    }
}
