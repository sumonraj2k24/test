<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\CartService;
use App\Services\CourseService;
use App\Services\ProgressService;

final class CatalogController
{
    public function index(Request $request, array $params = []): void
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
            'level' => (string) $request->query('level', ''),
            'instructor' => (string) $request->query('instructor', ''),
            'rating' => (string) $request->query('rating', ''),
            'price' => (string) $request->query('price', ''),
            'sort' => (string) $request->query('sort', 'featured'),
        ];
        view('courses/index', [
            'title' => 'Courses · ' . setting('site_name', 'Meridian'),
            'seoDescription' => 'Browse and search courses by category, level, rating, and instructor.',
            'filters' => $filters,
            'courses' => CourseService::search($filters),
            'categories' => Database::select('SELECT * FROM categories ORDER BY position, name'),
            'instructors' => Database::select(
                "SELECT DISTINCT u.id, u.name FROM users u
                 INNER JOIN courses c ON c.instructor_id = u.id AND c.status = 'published'
                 ORDER BY u.name"
            ),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $course = CourseService::findBySlug($params['slug']);
        if (!$course) {
            abort(404);
        }
        $public = $course['status'] === 'published';
        if (!$public && !is_course_staff($course)) {
            abort(404);
        }
        $sections = ProgressService::sections((int) $course['id']);
        $userId = Auth::id();
        view('courses/show', [
            'title' => ($course['seo_title'] ?: $course['title']) . ' · ' . setting('site_name', 'Meridian'),
            'seoDescription' => $course['seo_description'] ?: $course['subtitle'],
            'course' => $course,
            'sections' => $sections,
            'outcomes' => CourseService::outcomes($course),
            'requirements' => CourseService::requirements($course),
            'reviews' => Database::select(
                'SELECT r.*, u.name AS student_name FROM reviews r INNER JOIN users u ON u.id = r.user_id WHERE r.course_id = ? ORDER BY datetime(r.created_at) DESC',
                [(int) $course['id']]
            ),
            'related' => $public ? CourseService::related($course) : [],
            'enrollment' => enrollment_for($userId, (int) $course['id']),
            'wished' => $userId && (bool) Database::first('SELECT 1 AS ok FROM wishlists WHERE user_id = ? AND course_id = ?', [$userId, (int) $course['id']]),
            'inCart' => CartService::has((int) $course['id']),
            'collaborators' => Database::select(
                'SELECT u.name, u.headline FROM course_instructors ci INNER JOIN users u ON u.id = ci.user_id WHERE ci.course_id = ?',
                [(int) $course['id']]
            ),
        ]);
    }

    public function instructor(Request $request, array $params): void
    {
        $user = Database::first('SELECT * FROM users WHERE id = ? AND status = ?', [(int) $params['id'], 'active']);
        if (!$user || !in_array($user['role'], ['instructor', 'admin'], true)) {
            abort(404);
        }
        $courses = Database::select(
            "SELECT c.*, cat.name AS category_name, cat.accent AS category_accent, u.name AS instructor_name
             FROM courses c
             INNER JOIN users u ON u.id = c.instructor_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.instructor_id = ? AND c.status = 'published'
             ORDER BY c.is_featured DESC, c.title",
            [(int) $user['id']]
        );
        view('courses/instructor', [
            'title' => $user['name'] . ' · ' . setting('site_name', 'Meridian'),
            'seoDescription' => $user['headline'] ?: $user['bio'],
            'instructor' => $user,
            'courses' => $courses,
        ]);
    }

    public function schedule(Request $request, array $params = []): void
    {
        $classes = Database::select(
            "SELECT l.*, c.title AS course_title, c.slug AS course_slug, u.name AS instructor_name
             FROM live_classes l
             INNER JOIN courses c ON c.id = l.course_id
             INNER JOIN users u ON u.id = l.instructor_id
             WHERE c.status = 'published' AND l.status != 'cancelled'
             ORDER BY datetime(l.starts_at) ASC"
        );
        view('courses/schedule', [
            'title' => 'Live classes · ' . setting('site_name', 'Meridian'),
            'seoDescription' => 'Upcoming Zoom-integrated live classes and in-app rooms.',
            'classes' => $classes,
            'active' => 'live',
        ], Auth::check() ? 'layouts/dashboard' : 'layouts/app');
    }

    public function review(Request $request, array $params): void
    {
        $course = CourseService::findBySlug($params['slug']);
        $userId = Auth::id();
        if (!$course || !enrollment_for($userId, (int) $course['id'])) {
            abort(403, 'Only enrolled students can review a course.');
        }
        $data = validate([
            'rating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'required|min:8|max:2000',
        ]);
        $existing = Database::first('SELECT id FROM reviews WHERE user_id = ? AND course_id = ?', [$userId, (int) $course['id']]);
        if ($existing) {
            Database::update('reviews', [
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'],
            ], 'id = ?', [(int) $existing['id']]);
        } else {
            Database::insert('reviews', [
                'user_id' => $userId,
                'course_id' => (int) $course['id'],
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'],
                'created_at' => now(),
            ]);
        }
        CourseService::refreshRating((int) $course['id']);
        flash('success', 'Review saved.');
        redirect('/courses/' . $course['slug'] . '#reviews');
    }
}
