<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\CourseService;
use App\Services\NotificationService;

final class ForumController
{
    public function index(Request $request, array $params): void
    {
        $course = $this->course($params['slug']);
        $this->guard($course);
        $threads = Database::select(
            'SELECT t.*, u.name AS author_name,
                    (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) AS replies
             FROM threads t INNER JOIN users u ON u.id = t.user_id
             WHERE t.course_id = ?
             ORDER BY t.pinned DESC, datetime(t.updated_at) DESC',
            [(int) $course['id']]
        );
        view('forum/index', [
            'title' => 'Discussion · ' . $course['title'],
            'course' => $course,
            'threads' => $threads,
            'active' => 'learn',
        ], 'layouts/dashboard');
    }

    public function show(Request $request, array $params): void
    {
        $course = $this->course($params['slug']);
        $this->guard($course);
        $thread = Database::first(
            'SELECT t.*, u.name AS author_name FROM threads t INNER JOIN users u ON u.id = t.user_id WHERE t.id = ? AND t.course_id = ?',
            [(int) $params['id'], (int) $course['id']]
        );
        if (!$thread) {
            abort(404);
        }
        view('forum/show', [
            'title' => $thread['title'],
            'course' => $course,
            'thread' => $thread,
            'posts' => Database::select(
                'SELECT p.*, u.name AS author_name, u.role FROM posts p INNER JOIN users u ON u.id = p.user_id WHERE p.thread_id = ? ORDER BY datetime(p.created_at)',
                [(int) $thread['id']]
            ),
            'active' => 'learn',
        ], 'layouts/dashboard');
    }

    public function store(Request $request, array $params): void
    {
        $course = $this->course($params['slug']);
        $this->guard($course, true);
        $data = validate([
            'title' => 'required|min:4|max:160',
            'body' => 'required|min:8|max:8000',
        ]);
        $id = Database::insert('threads', [
            'course_id' => (int) $course['id'],
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'body' => $data['body'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        NotificationService::push((int) $course['instructor_id'], 'forum', 'New discussion', $data['title'], '/courses/' . $course['slug'] . '/discussions/' . $id);
        flash('success', 'Discussion opened.');
        redirect('/courses/' . $course['slug'] . '/discussions/' . $id);
    }

    public function reply(Request $request, array $params): void
    {
        $thread = Database::first('SELECT * FROM threads WHERE id = ?', [(int) $params['id']]);
        if (!$thread) {
            abort(404);
        }
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $thread['course_id']]);
        $this->guard($course, true);
        $data = validate(['body' => 'required|min:2|max:8000']);
        Database::insert('posts', [
            'thread_id' => (int) $thread['id'],
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'created_at' => now(),
        ]);
        Database::update('threads', ['updated_at' => now()], 'id = ?', [(int) $thread['id']]);
        if ((int) $thread['user_id'] !== Auth::id()) {
            NotificationService::push((int) $thread['user_id'], 'forum', 'New reply', $thread['title'], '/courses/' . $course['slug'] . '/discussions/' . $thread['id']);
        }
        flash('success', 'Reply posted.');
        redirect('/courses/' . $course['slug'] . '/discussions/' . $thread['id']);
    }

    public function pin(Request $request, array $params): void
    {
        $thread = Database::first('SELECT t.*, c.instructor_id, c.slug FROM threads t INNER JOIN courses c ON c.id = t.course_id WHERE t.id = ?', [(int) $params['id']]);
        if (!$thread || !is_course_staff(['id' => $thread['course_id'], 'instructor_id' => $thread['instructor_id']])) {
            abort(403);
        }
        Database::update('threads', ['pinned' => (int) $thread['pinned'] ? 0 : 1], 'id = ?', [(int) $thread['id']]);
        redirect('/courses/' . $thread['slug'] . '/discussions/' . $thread['id']);
    }

    private function course(string $slug): array
    {
        $course = CourseService::findBySlug($slug);
        if (!$course) {
            abort(404);
        }
        return $course;
    }

    private function guard(array $course, bool $write = false): void
    {
        if (is_course_staff($course)) {
            return;
        }
        if (!enrollment_for(Auth::id(), (int) $course['id'])) {
            abort(403, 'Discussions are for enrolled students and the teaching staff.');
        }
        if ($write && $course['status'] !== 'published' && !is_course_staff($course)) {
            abort(403);
        }
    }
}
