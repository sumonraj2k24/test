<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\CourseService;

final class HomeController
{
    public function index(Request $request, array $params = []): void
    {
        $courses = CourseService::search(['sort' => 'featured']);
        view('home', [
            'title' => (string) setting('seo_title', 'Meridian'),
            'seoDescription' => (string) setting('seo_description', setting('tagline', '')),
            'courses' => array_slice($courses, 0, 6),
            'categories' => Database::select('SELECT * FROM categories ORDER BY position, name'),
            'instructors' => Database::select(
                "SELECT u.id, u.name, u.headline, u.bio, COUNT(c.id) AS course_count
                 FROM users u INNER JOIN courses c ON c.instructor_id = u.id AND c.status = 'published'
                 GROUP BY u.id ORDER BY course_count DESC LIMIT 6"
            ),
            'reviews' => Database::select(
                "SELECT r.*, u.name AS student_name, c.title AS course_title, c.slug AS course_slug
                 FROM reviews r
                 INNER JOIN users u ON u.id = r.user_id
                 INNER JOIN courses c ON c.id = r.course_id
                 ORDER BY datetime(r.created_at) DESC LIMIT 3"
            ),
            'upcoming' => Database::select(
                "SELECT l.*, c.title AS course_title, c.slug AS course_slug, u.name AS instructor_name
                 FROM live_classes l
                 INNER JOIN courses c ON c.id = l.course_id
                 INNER JOIN users u ON u.id = l.instructor_id
                 WHERE l.starts_at >= ? AND c.status = 'published'
                 ORDER BY datetime(l.starts_at) ASC LIMIT 3",
                [now()]
            ),
            'stats' => [
                'courses' => (int) Database::value("SELECT COUNT(*) FROM courses WHERE status = 'published'"),
                'instructors' => (int) Database::value("SELECT COUNT(DISTINCT instructor_id) FROM courses WHERE status = 'published'"),
                'learners' => (int) Database::value('SELECT COUNT(*) FROM enrollments'),
            ],
        ]);
    }
}
