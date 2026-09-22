<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\PaymentService;
use App\Services\ProgressService;

final class StudentController
{
    public function learning(Request $request, array $params = []): void
    {
        $rows = Database::select(
            'SELECT e.*, c.title, c.slug, c.thumbnail, c.level, u.name AS instructor_name, cat.name AS category_name
             FROM enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN users u ON u.id = c.instructor_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE e.user_id = ?
             ORDER BY datetime(COALESCE(e.completed_at, e.enrolled_at)) DESC',
            [Auth::id()]
        );
        view('student/learning', [
            'title' => 'My learning',
            'enrollments' => $rows,
            'active' => 'learn',
        ], 'layouts/dashboard');
    }

    public function history(Request $request, array $params = []): void
    {
        view('student/history', [
            'title' => 'Watch history',
            'history' => ProgressService::history(Auth::id()),
            'active' => 'history',
        ], 'layouts/dashboard');
    }

    public function live(Request $request, array $params = []): void
    {
        $userId = Auth::id();
        $classes = Database::select(
            "SELECT l.*, c.title AS course_title, c.slug AS course_slug, u.name AS instructor_name,
                    (SELECT joined_at FROM live_attendance a WHERE a.live_class_id = l.id AND a.user_id = ?) AS joined_at
             FROM live_classes l
             INNER JOIN courses c ON c.id = l.course_id
             INNER JOIN users u ON u.id = l.instructor_id
             WHERE l.course_id IN (SELECT course_id FROM enrollments WHERE user_id = ?)
                OR l.instructor_id = ?
                OR EXISTS (SELECT 1 FROM course_instructors ci WHERE ci.course_id = l.course_id AND ci.user_id = ?)
             ORDER BY datetime(l.starts_at) DESC",
            [$userId, $userId, $userId, $userId]
        );
        view('student/live', [
            'title' => 'Live classes',
            'classes' => $classes,
            'active' => 'live',
        ], 'layouts/dashboard');
    }

    public function room(Request $request, array $params): void
    {
        $class = $this->classForUser((int) $params['id']);
        $attending = Database::first(
            'SELECT * FROM live_attendance WHERE live_class_id = ? AND user_id = ?',
            [(int) $class['id'], Auth::id()]
        );
        view('student/room', [
            'title' => $class['title'] . ' · Live',
            'class' => $class,
            'attending' => $attending,
            'active' => 'live',
        ], 'layouts/dashboard');
    }

    public function attend(Request $request, array $params): void
    {
        $class = $this->classForUser((int) $params['id']);
        Database::execute(
            'INSERT OR IGNORE INTO live_attendance (live_class_id, user_id, joined_at) VALUES (?, ?, ?)',
            [(int) $class['id'], Auth::id(), now()]
        );
        if (!empty($class['lesson_id'])) {
            $lesson = Database::first('SELECT * FROM lessons WHERE id = ?', [(int) $class['lesson_id']]);
            if ($lesson && enrollment_for(Auth::id(), (int) $lesson['course_id'])) {
                ProgressService::complete(Auth::id(), $lesson);
            }
        }
        flash('success', 'You are checked in. The room is open.');
        redirect('/live/room/' . $class['id']);
    }

    public function wishlist(Request $request, array $params = []): void
    {
        $courses = Database::select(
            'SELECT c.*, u.name AS instructor_name, cat.name AS category_name, cat.accent AS category_accent
             FROM wishlists w
             INNER JOIN courses c ON c.id = w.course_id
             INNER JOIN users u ON u.id = c.instructor_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE w.user_id = ?
             ORDER BY datetime(w.created_at) DESC',
            [Auth::id()]
        );
        view('student/wishlist', [
            'title' => 'Wishlist',
            'courses' => $courses,
            'active' => 'wishlist',
        ], 'layouts/dashboard');
    }

    public function toggleWishlist(Request $request, array $params = []): void
    {
        $courseId = $request->int('course_id');
        $course = Database::first("SELECT id FROM courses WHERE id = ? AND status = 'published'", [$courseId]);
        if (!$course) {
            abort(404);
        }
        $existing = Database::first('SELECT 1 AS ok FROM wishlists WHERE user_id = ? AND course_id = ?', [Auth::id(), $courseId]);
        if ($existing) {
            Database::delete('wishlists', 'user_id = ? AND course_id = ?', [Auth::id(), $courseId]);
            flash('success', 'Removed from wishlist.');
        } else {
            Database::insert('wishlists', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'created_at' => now(),
            ]);
            flash('success', 'Saved to wishlist.');
        }
        back('/');
    }

    public function orders(Request $request, array $params = []): void
    {
        view('student/orders', [
            'title' => 'Orders',
            'orders' => Database::select('SELECT * FROM orders WHERE user_id = ? ORDER BY datetime(created_at) DESC', [Auth::id()]),
            'active' => 'orders',
        ], 'layouts/dashboard');
    }

    public function order(Request $request, array $params): void
    {
        $order = Database::first('SELECT * FROM orders WHERE id = ? AND user_id = ?', [(int) $params['id'], Auth::id()]);
        if (!$order) {
            abort(404);
        }
        view('student/order', [
            'title' => $order['number'],
            'order' => $order,
            'items' => Database::select(
                'SELECT oi.*, c.title, c.slug, u.name AS instructor_name
                 FROM order_items oi
                 INNER JOIN courses c ON c.id = oi.course_id
                 INNER JOIN users u ON u.id = oi.instructor_id
                 WHERE oi.order_id = ?',
                [(int) $order['id']]
            ),
            'transactions' => Database::select('SELECT * FROM transactions WHERE order_id = ? ORDER BY id', [(int) $order['id']]),
            'active' => 'orders',
        ], 'layouts/dashboard');
    }

    public function certificates(Request $request, array $params = []): void
    {
        $rows = Database::select(
            'SELECT e.*, c.title, c.slug, u.name AS instructor_name
             FROM enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN users u ON u.id = c.instructor_id
             WHERE e.user_id = ? AND e.certificate_code IS NOT NULL
             ORDER BY datetime(e.completed_at) DESC',
            [Auth::id()]
        );
        view('student/certificates', [
            'title' => 'Certificates',
            'certificates' => $rows,
            'active' => 'certificates',
        ], 'layouts/dashboard');
    }

    public function enrollFree(Request $request, array $params): void
    {
        $course = Database::first("SELECT * FROM courses WHERE slug = ? AND status = 'published'", [$params['slug']]);
        if (!$course) {
            abort(404);
        }
        if ((int) $course['price_cents'] > 0) {
            flash('error', 'This course is paid. Add it to the cart.');
            redirect('/courses/' . $course['slug']);
        }
        if (!enrollment_for(Auth::id(), (int) $course['id'])) {
            PaymentService::enroll(Auth::id(), (int) $course['id'], null);
            \App\Services\NotificationService::push(Auth::id(), 'enroll', 'You are enrolled', $course['title'] . ' is on your shelf.', '/learn/' . $course['slug']);
        }
        flash('success', 'You are enrolled. Start when you are ready.');
        redirect('/learn/' . $course['slug']);
    }

    public function teachForm(Request $request, array $params = []): void
    {
        $existing = Database::first(
            'SELECT * FROM instructor_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [Auth::id()]
        );
        view('student/teach', [
            'title' => 'Teach on ' . setting('site_name', 'Meridian'),
            'application' => $existing,
            'active' => 'teach',
        ], Auth::check() && (Auth::user()['role'] ?? '') !== 'student' ? 'layouts/dashboard' : (Auth::check() ? 'layouts/dashboard' : 'layouts/app'));
    }

    public function teachSubmit(Request $request, array $params = []): void
    {
        if ((Auth::user()['role'] ?? '') === 'instructor' || Auth::can('studio.access')) {
            flash('error', 'You already teach here.');
            redirect('/studio');
        }
        $pending = Database::first(
            "SELECT id FROM instructor_applications WHERE user_id = ? AND status = 'pending'",
            [Auth::id()]
        );
        if ($pending) {
            flash('error', 'Your application is already in the queue.');
            redirect('/teach');
        }
        $data = validate([
            'expertise' => 'required|min:3|max:120',
            'years_experience' => 'required|integer',
            'statement' => 'required|min:40|max:4000',
            'sample_url' => 'max:300',
        ]);
        Database::insert('instructor_applications', [
            'user_id' => Auth::id(),
            'expertise' => $data['expertise'],
            'years_experience' => (int) $data['years_experience'],
            'statement' => $data['statement'],
            'sample_url' => $data['sample_url'] !== '' ? $data['sample_url'] : null,
            'status' => 'pending',
            'created_at' => now(),
        ]);
        $admins = Database::select("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
        foreach ($admins as $admin) {
            \App\Services\NotificationService::push((int) $admin['id'], 'application', 'Instructor application', Auth::user()['name'] . ' applied to teach.', '/admin/applications');
        }
        flash('success', 'Application sent. An administrator will read it.');
        redirect('/teach');
    }

    private function classForUser(int $id): array
    {
        $class = Database::first(
            'SELECT l.*, c.title AS course_title, c.slug AS course_slug, u.name AS instructor_name
             FROM live_classes l
             INNER JOIN courses c ON c.id = l.course_id
             INNER JOIN users u ON u.id = l.instructor_id
             WHERE l.id = ?',
            [$id]
        );
        if (!$class) {
            abort(404);
        }
        $userId = Auth::id();
        $allowed = (int) $class['instructor_id'] === $userId
            || enrollment_for($userId, (int) $class['course_id'])
            || is_course_staff(['id' => $class['course_id'], 'instructor_id' => $class['instructor_id']]);
        if (!$allowed) {
            abort(403, 'Enroll in the course to enter this room.');
        }
        return $class;
    }
}
