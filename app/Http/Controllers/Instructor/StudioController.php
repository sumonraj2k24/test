<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\AnalyticsService;
use App\Services\NotificationService;

final class StudioController
{
    public function index(Request $request, array $params = []): void
    {
        $stats = AnalyticsService::instructor(Auth::id());
        view('instructor/dashboard', [
            'title' => 'Studio',
            'active' => 'studio',
            'stats' => $stats,
            'courses' => Database::select(
                'SELECT * FROM courses WHERE instructor_id = ? ORDER BY datetime(updated_at) DESC LIMIT 6',
                [Auth::id()]
            ),
            'upcoming' => Database::select(
                'SELECT l.*, c.title AS course_title FROM live_classes l INNER JOIN courses c ON c.id = l.course_id
                 WHERE l.instructor_id = ? AND l.starts_at >= ? ORDER BY datetime(l.starts_at) LIMIT 4',
                [Auth::id(), now()]
            ),
            'submissions' => Database::select(
                "SELECT s.*, a.title, u.name AS student_name, a.id AS assignment_id
                 FROM assignment_submissions s
                 INNER JOIN assignments a ON a.id = s.assignment_id
                 INNER JOIN users u ON u.id = s.user_id
                 INNER JOIN courses c ON c.id = a.course_id
                 WHERE c.instructor_id = ? AND s.status = 'submitted'
                 ORDER BY datetime(s.submitted_at) DESC LIMIT 5",
                [Auth::id()]
            ),
        ], 'layouts/dashboard');
    }

    public function analytics(Request $request, array $params = []): void
    {
        view('instructor/analytics', [
            'title' => 'Studio analytics',
            'active' => 'studio-analytics',
            'stats' => AnalyticsService::instructor(Auth::id()),
        ], 'layouts/dashboard');
    }

    public function students(Request $request, array $params = []): void
    {
        $courses = Database::select('SELECT id, title, slug, students_count FROM courses WHERE instructor_id = ? ORDER BY title', [Auth::id()]);
        $courseId = $request->int('course') ?: (int) ($courses[0]['id'] ?? 0);
        $rows = [];
        if ($courseId && $this->owns($courseId)) {
            $rows = Database::select(
                'SELECT e.*, u.name, u.email,
                        (SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE qa.user_id = e.user_id AND q.course_id = e.course_id AND qa.passed = 1) AS quizzes_passed
                 FROM enrollments e INNER JOIN users u ON u.id = e.user_id
                 WHERE e.course_id = ? ORDER BY e.progress_percent DESC, u.name',
                [$courseId]
            );
        }
        view('instructor/students', [
            'title' => 'Students',
            'active' => 'studio-students',
            'courses' => $courses,
            'courseId' => $courseId,
            'rows' => $rows,
        ], 'layouts/dashboard');
    }

    public function earnings(Request $request, array $params = []): void
    {
        $stats = AnalyticsService::instructor(Auth::id());
        view('instructor/earnings', [
            'title' => 'Earnings',
            'active' => 'studio-earnings',
            'stats' => $stats,
            'payouts' => Database::select('SELECT * FROM payouts WHERE instructor_id = ? ORDER BY datetime(requested_at) DESC', [Auth::id()]),
            'sales' => Database::select(
                'SELECT oi.*, o.number, o.paid_at, o.status, c.title
                 FROM order_items oi
                 INNER JOIN orders o ON o.id = oi.order_id
                 INNER JOIN courses c ON c.id = oi.course_id
                 WHERE oi.instructor_id = ? AND o.status = "paid"
                 ORDER BY datetime(o.paid_at) DESC LIMIT 30',
                [Auth::id()]
            ),
        ], 'layouts/dashboard');
    }

    public function requestPayout(Request $request, array $params = []): void
    {
        $stats = AnalyticsService::instructor(Auth::id());
        $amount = (int) round(((float) $request->string('amount')) * 100);
        if ($amount < 1000) {
            flash('error', 'Minimum payout is ' . money(1000) . '.');
            back('/studio/earnings');
        }
        if ($amount > $stats['available']) {
            flash('error', 'That is more than the available balance.');
            back('/studio/earnings');
        }
        $user = Auth::user();
        if (empty($user['payout_details'])) {
            flash('error', 'Add payout details on your profile first.');
            redirect('/account');
        }
        Database::insert('payouts', [
            'instructor_id' => Auth::id(),
            'amount_cents' => $amount,
            'status' => 'requested',
            'method' => $user['payout_method'] ?: 'manual',
            'details' => $user['payout_details'],
            'note' => $request->string('note') ?: null,
            'requested_at' => now(),
        ]);
        foreach (Database::select("SELECT id FROM users WHERE role = 'admin'") as $admin) {
            NotificationService::push((int) $admin['id'], 'payout', 'Payout requested', $user['name'] . ' requested ' . money($amount) . '.', '/admin/payouts');
        }
        flash('success', 'Payout requested. It is reserved against your balance.');
        redirect('/studio/earnings');
    }

    private function owns(int $courseId): bool
    {
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [$courseId]);
        return $course && can_edit_course($course);
    }
}
