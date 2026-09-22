<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class CourseService
{
    public static function search(array $filters, bool $includeUnpublished = false): array
    {
        $sql = 'SELECT c.*, u.name AS instructor_name, u.headline AS instructor_headline,
                       cat.name AS category_name, cat.slug AS category_slug, cat.accent AS category_accent
                FROM courses c
                INNER JOIN users u ON u.id = c.instructor_id
                LEFT JOIN categories cat ON cat.id = c.category_id
                WHERE 1 = 1';
        $params = [];
        if (!$includeUnpublished) {
            $sql .= " AND c.status = 'published'";
        } elseif (!empty($filters['status'])) {
            $sql .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (c.title LIKE ? OR c.subtitle LIKE ? OR c.description LIKE ? OR u.name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filters['category'])) {
            $sql .= ' AND cat.slug = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['level'])) {
            $sql .= ' AND c.level = ?';
            $params[] = $filters['level'];
        }
        if (!empty($filters['instructor'])) {
            $sql .= ' AND c.instructor_id = ?';
            $params[] = (int) $filters['instructor'];
        }
        if (!empty($filters['rating'])) {
            $sql .= ' AND c.rating_avg >= ?';
            $params[] = (float) $filters['rating'];
        }
        if (($filters['price'] ?? '') === 'free') {
            $sql .= ' AND c.price_cents = 0';
        } elseif (($filters['price'] ?? '') === 'paid') {
            $sql .= ' AND c.price_cents > 0';
        }
        $sql .= match ($filters['sort'] ?? 'featured') {
            'newest' => ' ORDER BY datetime(c.published_at) DESC, c.id DESC',
            'rating' => ' ORDER BY c.rating_avg DESC, c.rating_count DESC',
            'price_asc' => ' ORDER BY c.price_cents ASC, c.title',
            'price_desc' => ' ORDER BY c.price_cents DESC, c.title',
            'popular' => ' ORDER BY c.students_count DESC, c.rating_avg DESC',
            default => ' ORDER BY c.is_featured DESC, c.rating_avg DESC, c.students_count DESC',
        };
        return Database::select($sql, $params);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT c.*, u.name AS instructor_name, u.headline AS instructor_headline, u.bio AS instructor_bio,
                    u.avatar_path AS instructor_avatar, cat.name AS category_name, cat.slug AS category_slug, cat.accent AS category_accent
             FROM courses c
             INNER JOIN users u ON u.id = c.instructor_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.slug = ?',
            [$slug]
        );
    }

    public static function related(array $course, int $limit = 3): array
    {
        return Database::select(
            "SELECT c.*, u.name AS instructor_name, cat.name AS category_name, cat.accent AS category_accent
             FROM courses c
             INNER JOIN users u ON u.id = c.instructor_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.status = 'published' AND c.id != ? AND (c.category_id = ? OR c.instructor_id = ?)
             ORDER BY c.is_featured DESC, c.rating_avg DESC
             LIMIT " . (int) $limit,
            [(int) $course['id'], (int) $course['category_id'], (int) $course['instructor_id']]
        );
    }

    public static function refreshDuration(int $courseId): void
    {
        $minutes = (int) Database::value(
            'SELECT COALESCE(SUM(duration_minutes), 0) FROM lessons WHERE course_id = ?',
            [$courseId]
        );
        Database::update('courses', [
            'duration_minutes' => $minutes,
            'updated_at' => now(),
        ], 'id = ?', [$courseId]);
    }

    public static function refreshRating(int $courseId): void
    {
        $row = Database::first(
            'SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE course_id = ?',
            [$courseId]
        );
        Database::update('courses', [
            'rating_count' => (int) ($row['n'] ?? 0),
            'rating_avg' => round((float) ($row['avg'] ?? 0), 2),
        ], 'id = ?', [$courseId]);
    }

    public static function touch(array $course): void
    {
        $updates = ['updated_at' => now()];
        $central = setting('course_management_mode', 'collaborative') === 'centralized';
        if ($course['status'] === 'published' && $central && !\App\Core\Auth::can('courses.manage')) {
            $updates['status'] = 'pending';
            $updates['rejection_note'] = null;
        }
        Database::update('courses', $updates, 'id = ?', [(int) $course['id']]);
    }

    public static function refreshStudents(int $courseId): void
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM enrollments WHERE course_id = ?', [$courseId]);
        Database::update('courses', ['students_count' => $count], 'id = ?', [$courseId]);
    }

    public static function outcomes(array $course): array
    {
        return self::lines($course['outcomes'] ?? '');
    }

    public static function requirements(array $course): array
    {
        return self::lines($course['requirements'] ?? '');
    }

    private static function lines(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        return array_values(array_filter(array_map('trim', $lines)));
    }
}
