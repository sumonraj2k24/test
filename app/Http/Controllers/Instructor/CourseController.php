<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\CourseService;
use App\Services\NotificationService;
use App\Services\StorageService;

final class CourseController
{
    public function index(Request $request, array $params = []): void
    {
        $courses = Database::select(
            'SELECT c.*, cat.name AS category_name
             FROM courses c LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.instructor_id = ?
                OR c.id IN (SELECT course_id FROM course_instructors WHERE user_id = ?)
             ORDER BY datetime(c.updated_at) DESC',
            [Auth::id(), Auth::id()]
        );
        view('instructor/courses', [
            'title' => 'Courses',
            'active' => 'studio-courses',
            'courses' => $courses,
            'mode' => setting('course_management_mode', 'collaborative'),
        ], 'layouts/dashboard');
    }

    public function create(Request $request, array $params = []): void
    {
        $this->ensureCreate();
        view('instructor/course-form', [
            'title' => 'New course',
            'active' => 'studio-courses',
            'course' => null,
            'categories' => Database::select('SELECT * FROM categories ORDER BY position, name'),
        ], 'layouts/dashboard');
    }

    public function store(Request $request, array $params = []): void
    {
        $this->ensureCreate();
        $data = $this->validated($request);
        $id = Database::insert('courses', $data + [
            'instructor_id' => Auth::id(),
            'slug' => unique_slug('courses', $data['title']),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        flash('success', 'Draft created. Build the curriculum next.');
        redirect('/studio/courses/' . $id . '/curriculum');
    }

    public function edit(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        view('instructor/course-form', [
            'title' => 'Edit ' . $course['title'],
            'active' => 'studio-courses',
            'course' => $course,
            'categories' => Database::select('SELECT * FROM categories ORDER BY position, name'),
            'collaborators' => Database::select(
                'SELECT ci.*, u.name, u.email FROM course_instructors ci INNER JOIN users u ON u.id = ci.user_id WHERE ci.course_id = ?',
                [(int) $course['id']]
            ),
        ], 'layouts/dashboard');
    }

    public function update(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        $data = $this->validated($request, $course);
        if ($data['title'] !== $course['title']) {
            $data['slug'] = unique_slug('courses', $data['title'], (int) $course['id']);
        }
        Database::update('courses', $data, 'id = ?', [(int) $course['id']]);
        CourseService::touch($course);
        flash('success', 'Course details saved.');
        redirect('/studio/courses/' . $course['id'] . '/edit');
    }

    public function submit(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        $lessons = (int) Database::value('SELECT COUNT(*) FROM lessons WHERE course_id = ?', [(int) $course['id']]);
        if ($lessons < 1) {
            flash('error', 'Add at least one lesson before submitting.');
            redirect('/studio/courses/' . $course['id'] . '/curriculum');
        }
        $direct = Auth::can('courses.manage')
            || (setting('course_management_mode', 'collaborative') === 'collaborative' && setting('require_course_approval', '1') !== '1');
        Database::update('courses', [
            'status' => $direct ? 'published' : 'pending',
            'published_at' => $direct ? ($course['published_at'] ?: now()) : $course['published_at'],
            'rejection_note' => null,
            'updated_at' => now(),
        ], 'id = ?', [(int) $course['id']]);
        if (!$direct) {
            foreach (Database::select("SELECT id FROM users WHERE role = 'admin'") as $admin) {
                NotificationService::push((int) $admin['id'], 'course', 'Course submitted', $course['title'] . ' is waiting for review.', '/admin/courses');
            }
            flash('success', 'Submitted for review.');
        } else {
            flash('success', 'Course published.');
        }
        redirect('/studio/courses');
    }

    public function collaborator(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        if (setting('course_management_mode', 'collaborative') !== 'collaborative') {
            flash('error', 'The studio is in centralized mode. Collaborators are disabled.');
            back();
        }
        $email = strtolower($request->string('email'));
        $user = Database::first('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user) {
            flash('error', 'No account with that email.');
            back();
        }
        Database::execute(
            'INSERT OR IGNORE INTO course_instructors (course_id, user_id, role, created_at) VALUES (?, ?, ?, ?)',
            [(int) $course['id'], (int) $user['id'], 'collaborator', now()]
        );
        NotificationService::push((int) $user['id'], 'course', 'You were added as a collaborator', $course['title'], '/studio/courses/' . $course['id'] . '/curriculum');
        flash('success', $user['name'] . ' can now edit this course.');
        redirect('/studio/courses/' . $course['id'] . '/edit');
    }

    private function validated(Request $request, ?array $course = null): array
    {
        $data = validate([
            'title' => 'required|min:4|max:140',
            'subtitle' => 'required|min:4|max:180',
            'description' => 'required|min:20',
            'level' => 'required|in:beginner,intermediate,advanced,all',
            'language' => 'required|max:40',
            'price' => 'required|numeric',
            'category_id' => 'required|integer',
        ]);
        $price = (int) round(((float) $data['price']) * 100);
        if ($price < 0) {
            flash('error', 'Price cannot be negative.');
            back();
        }
        $thumbnail = $course['thumbnail'] ?? null;
        if ($file = $request->file('thumbnail')) {
            $stored = StorageService::store($file, 'courses');
            $thumbnail = $stored['path'];
        }
        return [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'description' => $data['description'],
            'level' => $data['level'],
            'language' => $data['language'],
            'price_cents' => $price,
            'compare_cents' => $request->string('compare') !== '' ? (int) round(((float) $request->string('compare')) * 100) : null,
            'category_id' => (int) $data['category_id'],
            'thumbnail' => $thumbnail,
            'preview_video' => $request->string('preview_video') ?: null,
            'requirements' => $request->string('requirements'),
            'outcomes' => $request->string('outcomes'),
            'seo_title' => $request->string('seo_title') ?: null,
            'seo_description' => $request->string('seo_description') ?: null,
            'enforce_sequence' => $request->input('enforce_sequence') ? 1 : 0,
            'updated_at' => now(),
        ];
    }

    private function course(int $id): array
    {
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [$id]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        return $course;
    }

    private function ensureCreate(): void
    {
        if (!Auth::can('courses.create') && !Auth::can('courses.manage')) {
            abort(403);
        }
    }
}
