<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProgressService
{
    public static function sections(int $courseId): array
    {
        $sections = Database::select(
            'SELECT * FROM sections WHERE course_id = ? ORDER BY position, id',
            [$courseId]
        );
        $lessons = Database::select(
            'SELECT * FROM lessons WHERE course_id = ? ORDER BY position, id',
            [$courseId]
        );
        $grouped = [];
        foreach ($lessons as $lesson) {
            $grouped[(int) $lesson['section_id']][] = $lesson;
        }
        foreach ($sections as &$section) {
            $section['lessons'] = $grouped[(int) $section['id']] ?? [];
        }
        return $sections;
    }

    public static function flat(array $sections): array
    {
        $all = [];
        foreach ($sections as $section) {
            foreach ($section['lessons'] as $lesson) {
                $lesson['section_title'] = $section['title'];
                $lesson['drip_days'] = (int) $section['drip_days'];
                $all[] = $lesson;
            }
        }
        return $all;
    }

    public static function access(array $course, array $lesson, ?array $enrollment, array $flat): array
    {
        if (is_course_staff($course)) {
            return ['ok' => true, 'reason' => 'staff'];
        }
        if ((int) $lesson['is_preview'] === 1) {
            return ['ok' => true, 'reason' => 'preview'];
        }
        if (!$enrollment) {
            return ['ok' => false, 'reason' => 'Enroll to open this lesson.'];
        }
        $enrolledAt = strtotime($enrollment['enrolled_at'] . ' UTC') ?: time();
        $unlock = $enrolledAt + ((int) ($lesson['drip_days'] ?? 0) * 86400);
        if (!empty($lesson['drip_at'])) {
            $explicit = strtotime($lesson['drip_at'] . ' UTC') ?: 0;
            if ($explicit > $unlock) {
                $unlock = $explicit;
            }
        }
        if ($unlock > time()) {
            return [
                'ok' => false,
                'reason' => 'Scheduled to open ' . gmdate('M j, Y', $unlock) . '. Drip keeps the cohort together.',
                'unlocks_at' => gmdate('Y-m-d H:i:s', $unlock),
            ];
        }
        if ((int) $course['enforce_sequence'] === 1) {
            foreach ($flat as $item) {
                if ((int) $item['id'] === (int) $lesson['id']) {
                    break;
                }
                $done = Database::first(
                    'SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?',
                    [(int) $enrollment['user_id'], (int) $item['id']]
                );
                if (!$done || (int) $done['completed'] !== 1) {
                    return [
                        'ok' => false,
                        'reason' => 'Finish “' . $item['title'] . '” first. This course is taught in order.',
                    ];
                }
            }
        }
        return ['ok' => true, 'reason' => 'enrolled'];
    }

    public static function mapForUser(int $userId, int $courseId): array
    {
        $rows = Database::select(
            'SELECT * FROM lesson_progress WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['lesson_id']] = $row;
        }
        return $map;
    }

    public static function recordView(int $userId, array $lesson): void
    {
        $existing = Database::first(
            'SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?',
            [$userId, (int) $lesson['id']]
        );
        if ($existing) {
            Database::update('lesson_progress', ['last_watched_at' => now()], 'id = ?', [(int) $existing['id']]);
        } else {
            Database::insert('lesson_progress', [
                'user_id' => $userId,
                'lesson_id' => (int) $lesson['id'],
                'course_id' => (int) $lesson['course_id'],
                'completed' => 0,
                'watched_seconds' => 0,
                'last_watched_at' => now(),
            ]);
        }
        Database::update(
            'enrollments',
            ['last_lesson_id' => (int) $lesson['id']],
            'user_id = ? AND course_id = ?',
            [$userId, (int) $lesson['course_id']]
        );
    }

    public static function complete(int $userId, array $lesson): void
    {
        $existing = Database::first(
            'SELECT id, completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?',
            [$userId, (int) $lesson['id']]
        );
        $wasComplete = $existing && (int) $existing['completed'] === 1;
        $data = [
            'completed' => 1,
            'completed_at' => now(),
            'last_watched_at' => now(),
        ];
        if ($existing) {
            Database::update('lesson_progress', $data, 'id = ?', [(int) $existing['id']]);
        } else {
            Database::insert('lesson_progress', $data + [
                'user_id' => $userId,
                'lesson_id' => (int) $lesson['id'],
                'course_id' => (int) $lesson['course_id'],
                'watched_seconds' => 0,
            ]);
        }
        if (!$wasComplete) {
            self::recompute($userId, (int) $lesson['course_id']);
        }
    }

    public static function recompute(int $userId, int $courseId): void
    {
        $total = (int) Database::value('SELECT COUNT(*) FROM lessons WHERE course_id = ?', [$courseId]);
        $done = (int) Database::value(
            'SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND course_id = ? AND completed = 1',
            [$userId, $courseId]
        );
        $percent = $total > 0 ? (int) floor($done / $total * 100) : 0;
        $enrollment = enrollment_for($userId, $courseId);
        if (!$enrollment) {
            return;
        }
        $updates = ['progress_percent' => $percent];
        if ($percent >= 100 && empty($enrollment['completed_at'])) {
            $updates['completed_at'] = now();
            $updates['certificate_code'] = $enrollment['certificate_code'] ?: CertificateService::code();
            NotificationService::push(
                $userId,
                'certificate',
                'Certificate ready',
                'You finished the course. The certificate is ready to print or verify.',
                '/certificates/' . $updates['certificate_code']
            );
        }
        Database::update('enrollments', $updates, 'id = ?', [(int) $enrollment['id']]);
    }

    public static function history(int $userId, int $limit = 30): array
    {
        return Database::select(
            'SELECT lp.*, l.title AS lesson_title, l.type, c.title AS course_title, c.slug AS course_slug
             FROM lesson_progress lp
             INNER JOIN lessons l ON l.id = lp.lesson_id
             INNER JOIN courses c ON c.id = lp.course_id
             WHERE lp.user_id = ? AND lp.last_watched_at IS NOT NULL
             ORDER BY datetime(lp.last_watched_at) DESC
             LIMIT ' . (int) $limit,
            [$userId]
        );
    }
}
