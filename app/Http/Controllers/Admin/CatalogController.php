<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

final class CatalogController
{
    public function courses(Request $request, array $params = []): void
    {
        $status = (string) $request->query('status', '');
        view('admin/courses', [
            'title' => 'Courses',
            'active' => 'courses',
            'courses' => \App\Services\CourseService::search([
                'q' => (string) $request->query('q', ''),
                'status' => $status,
                'sort' => 'newest',
            ], true),
            'status' => $status,
            'q' => (string) $request->query('q', ''),
        ], 'layouts/dashboard');
    }

    public function moderate(Request $request, array $params): void
    {
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $params['id']]);
        if (!$course) {
            abort(404);
        }
        $action = $request->string('action');
        $updates = ['updated_at' => now()];
        if ($action === 'approve') {
            $updates['status'] = 'published';
            $updates['published_at'] = $course['published_at'] ?: now();
            $updates['rejection_note'] = null;
            NotificationService::push((int) $course['instructor_id'], 'course', 'Course published', $course['title'] . ' is live.', '/studio/courses');
        } elseif ($action === 'reject') {
            $updates['status'] = 'rejected';
            $updates['rejection_note'] = $request->string('note') ?: 'Needs another pass.';
            NotificationService::push((int) $course['instructor_id'], 'course', 'Course returned', $updates['rejection_note'], '/studio/courses/' . $course['id'] . '/edit');
        } elseif ($action === 'unpublish') {
            $updates['status'] = 'draft';
        } elseif ($action === 'feature') {
            $updates['is_featured'] = (int) $course['is_featured'] ? 0 : 1;
        } else {
            flash('error', 'Unknown action.');
            back();
        }
        Database::update('courses', $updates, 'id = ?', [(int) $course['id']]);
        audit('course.' . $action, $course['slug']);
        flash('success', 'Course updated.');
        redirect('/admin/courses');
    }

    public function categories(Request $request, array $params = []): void
    {
        view('admin/categories', [
            'title' => 'Categories',
            'active' => 'categories',
            'categories' => Database::select('SELECT c.*, (SELECT COUNT(*) FROM courses x WHERE x.category_id = c.id) AS course_count FROM categories c ORDER BY position, name'),
        ], 'layouts/dashboard');
    }

    public function saveCategory(Request $request, array $params = []): void
    {
        $data = validate([
            'name' => 'required|min:2|max:60',
            'description' => 'max:300',
        ]);
        $id = $request->int('id');
        $payload = [
            'name' => $data['name'],
            'slug' => unique_slug('categories', $data['name'], $id ?: null),
            'description' => $data['description'] ?: null,
            'accent' => safe_hex($request->string('accent', '#1f4f45'), '#1f4f45'),
            'position' => $request->int('position'),
        ];
        if ($id) {
            Database::update('categories', $payload, 'id = ?', [$id]);
        } else {
            Database::insert('categories', $payload);
        }
        audit('category.save', $payload['slug']);
        flash('success', 'Category saved.');
        redirect('/admin/categories');
    }

    public function deleteCategory(Request $request, array $params): void
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM courses WHERE category_id = ?', [(int) $params['id']]);
        if ($count > 0) {
            flash('error', 'Move or delete the courses in this category first.');
            redirect('/admin/categories');
        }
        Database::delete('categories', 'id = ?', [(int) $params['id']]);
        flash('success', 'Category removed.');
        redirect('/admin/categories');
    }
}
