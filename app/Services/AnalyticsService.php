<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AnalyticsService
{
    public static function admin(): array
    {
        $revenue = (int) Database::value("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status = 'paid'");
        $monthRevenue = (int) Database::value(
            "SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status = 'paid' AND created_at >= ?",
            [gmdate('Y-m-01 00:00:00')]
        );
        $fees = (int) Database::value(
            "SELECT COALESCE(SUM(oi.platform_fee_cents),0)
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.status = 'paid'"
        );
        return [
            'users' => (int) Database::value('SELECT COUNT(*) FROM users'),
            'students' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'student'"),
            'instructors' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'instructor'"),
            'courses' => (int) Database::value("SELECT COUNT(*) FROM courses WHERE status = 'published'"),
            'pending_courses' => (int) Database::value("SELECT COUNT(*) FROM courses WHERE status = 'pending'"),
            'pending_applications' => (int) Database::value("SELECT COUNT(*) FROM instructor_applications WHERE status = 'pending'"),
            'enrollments' => (int) Database::value('SELECT COUNT(*) FROM enrollments'),
            'revenue' => $revenue,
            'month_revenue' => $monthRevenue,
            'fees' => $fees,
            'series' => self::monthly("SELECT strftime('%Y-%m', created_at) AS ym, SUM(total_cents) AS value FROM orders WHERE status = 'paid' GROUP BY ym"),
            'enrollment_series' => self::monthly("SELECT strftime('%Y-%m', enrolled_at) AS ym, COUNT(*) AS value FROM enrollments GROUP BY ym"),
            'gateways' => Database::select("SELECT gateway, COUNT(*) AS n, SUM(total_cents) AS total FROM orders WHERE status = 'paid' GROUP BY gateway"),
            'top_courses' => Database::select(
                "SELECT c.title, c.slug, c.students_count, c.rating_avg,
                        COALESCE(SUM(oi.price_cents),0) AS revenue
                 FROM courses c
                 LEFT JOIN order_items oi ON oi.course_id = c.id
                 LEFT JOIN orders o ON o.id = oi.order_id AND o.status = 'paid'
                 GROUP BY c.id
                 ORDER BY revenue DESC, c.students_count DESC
                 LIMIT 6"
            ),
        ];
    }

    public static function instructor(int $userId): array
    {
        $courseIds = self::instructorCourseIds($userId);
        if ($courseIds === []) {
            return [
                'courses' => 0,
                'students' => 0,
                'revenue' => 0,
                'available' => 0,
                'completion' => 0,
                'quiz_pass' => 0,
                'series' => self::monthly('SELECT NULL AS ym, 0 AS value WHERE 0'),
                'by_course' => [],
            ];
        }
        $in = implode(',', array_fill(0, count($courseIds), '?'));
        $students = (int) Database::value("SELECT COUNT(*) FROM enrollments WHERE course_id IN ($in)", $courseIds);
        $completed = (int) Database::value("SELECT COUNT(*) FROM enrollments WHERE completed_at IS NOT NULL AND course_id IN ($in)", $courseIds);
        $revenue = (int) Database::value(
            "SELECT COALESCE(SUM(oi.instructor_earning_cents),0)
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.status = 'paid' AND oi.instructor_id = ?",
            [$userId]
        );
        $reserved = (int) Database::value(
            "SELECT COALESCE(SUM(amount_cents),0) FROM payouts WHERE instructor_id = ? AND status IN ('requested','approved','paid')",
            [$userId]
        );
        $attempts = (int) Database::value(
            "SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE q.course_id IN ($in)",
            $courseIds
        );
        $passed = (int) Database::value(
            "SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE qa.passed = 1 AND q.course_id IN ($in)",
            $courseIds
        );
        return [
            'courses' => count($courseIds),
            'students' => $students,
            'revenue' => $revenue,
            'available' => max(0, $revenue - $reserved),
            'reserved' => $reserved,
            'completion' => $students > 0 ? (int) round($completed / $students * 100) : 0,
            'quiz_pass' => $attempts > 0 ? (int) round($passed / $attempts * 100) : 0,
            'series' => self::monthly(
                "SELECT strftime('%Y-%m', o.paid_at) AS ym, SUM(oi.instructor_earning_cents) AS value
                 FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
                 WHERE o.status = 'paid' AND oi.instructor_id = " . (int) $userId . " GROUP BY ym"
            ),
            'by_course' => Database::select(
                "SELECT c.id, c.title, c.slug, c.students_count, c.rating_avg,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.completed_at IS NOT NULL) AS completed,
                        COALESCE((SELECT SUM(oi.instructor_earning_cents) FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE oi.course_id = c.id AND o.status = 'paid'), 0) AS revenue
                 FROM courses c WHERE c.instructor_id = ?
                 ORDER BY revenue DESC",
                [$userId]
            ),
        ];
    }

    public static function instructorCourseIds(int $userId): array
    {
        $rows = Database::select(
            'SELECT id FROM courses WHERE instructor_id = ?
             UNION
             SELECT course_id FROM course_instructors WHERE user_id = ?',
            [$userId, $userId]
        );
        return array_map('intval', array_column($rows, 'id'));
    }

    private static function monthly(string $sql): array
    {
        $rows = Database::select($sql);
        $indexed = [];
        foreach ($rows as $row) {
            if (!empty($row['ym'])) {
                $indexed[$row['ym']] = (int) $row['value'];
            }
        }
        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $stamp = strtotime(gmdate('Y-m-01') . " -$i months UTC");
            $key = gmdate('Y-m', $stamp);
            $out[] = ['label' => gmdate('M', $stamp), 'value' => $indexed[$key] ?? 0];
        }
        return $out;
    }
}
