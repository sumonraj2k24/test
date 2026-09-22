<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;
use App\Services\ZoomService;

final class LiveController
{
    public function index(Request $request, array $params = []): void
    {
        view('instructor/live', [
            'title' => 'Live classes',
            'active' => 'studio-live',
            'classes' => Database::select(
                'SELECT l.*, c.title AS course_title,
                        (SELECT COUNT(*) FROM live_attendance a WHERE a.live_class_id = l.id) AS attendance
                 FROM live_classes l INNER JOIN courses c ON c.id = l.course_id
                 WHERE l.instructor_id = ? OR c.instructor_id = ?
                 ORDER BY datetime(l.starts_at) DESC',
                [Auth::id(), Auth::id()]
            ),
            'courses' => Database::select('SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title', [Auth::id()]),
            'zoomReady' => ZoomService::configured(),
        ], 'layouts/dashboard');
    }

    public function store(Request $request, array $params = []): void
    {
        $data = validate([
            'course_id' => 'required|integer',
            'title' => 'required|min:3|max:140',
            'starts_at' => 'required',
            'duration_minutes' => 'required|integer',
        ]);
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $data['course_id']]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        $starts = str_replace('T', ' ', $data['starts_at']);
        if (strlen($starts) === 16) {
            $starts .= ':00';
        }
        $zoom = ['meeting_id' => null, 'join_url' => $request->string('zoom_join_url') ?: null, 'start_url' => null];
        if ($request->input('create_zoom')) {
            $created = ZoomService::createMeeting($data['title'], $starts, (int) $data['duration_minutes']);
            if (!$created['ok']) {
                flash('error', $created['error']);
                if (!$zoom['join_url']) {
                    back('/studio/live');
                }
            } else {
                $zoom = [
                    'meeting_id' => $created['meeting_id'],
                    'join_url' => $created['join_url'],
                    'start_url' => $created['start_url'],
                ];
            }
        }
        $id = Database::insert('live_classes', [
            'course_id' => (int) $course['id'],
            'lesson_id' => $request->int('lesson_id') ?: null,
            'instructor_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $request->string('description') ?: null,
            'starts_at' => $starts,
            'duration_minutes' => max(15, (int) $data['duration_minutes']),
            'zoom_meeting_id' => $zoom['meeting_id'],
            'zoom_join_url' => $zoom['join_url'],
            'zoom_start_url' => $zoom['start_url'],
            'status' => 'scheduled',
            'created_at' => now(),
        ]);
        if ($request->int('lesson_id')) {
            Database::update('lessons', ['live_class_id' => $id, 'type' => 'live'], 'id = ? AND course_id = ?', [$request->int('lesson_id'), (int) $course['id']]);
        }
        $students = Database::select('SELECT user_id FROM enrollments WHERE course_id = ?', [(int) $course['id']]);
        foreach ($students as $student) {
            NotificationService::push((int) $student['user_id'], 'live', 'Live class scheduled', $data['title'] . ' · ' . pretty_datetime($starts), '/live/room/' . $id);
        }
        flash('success', 'Live class scheduled. Students were notified.');
        redirect('/studio/live');
    }

    public function delete(Request $request, array $params): void
    {
        $class = Database::first('SELECT * FROM live_classes WHERE id = ?', [(int) $params['id']]);
        if (!$class) {
            abort(404);
        }
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $class['course_id']]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        Database::update('live_classes', ['status' => 'cancelled'], 'id = ?', [(int) $class['id']]);
        flash('success', 'Class cancelled.');
        redirect('/studio/live');
    }
}
