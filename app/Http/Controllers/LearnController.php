<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\CourseService;
use App\Services\ProgressService;
use App\Services\QuizService;
use App\Services\StorageService;

final class LearnController
{
    public function course(Request $request, array $params): void
    {
        $course = $this->findCourse($params['slug']);
        $enrollment = enrollment_for(Auth::id(), (int) $course['id']);
        $sections = ProgressService::sections((int) $course['id']);
        $flat = ProgressService::flat($sections);
        $target = $flat[0] ?? null;
        if ($enrollment && $enrollment['last_lesson_id']) {
            foreach ($flat as $lesson) {
                if ((int) $lesson['id'] === (int) $enrollment['last_lesson_id']) {
                    $target = $lesson;
                    break;
                }
            }
        }
        if (!$target) {
            flash('error', 'This course has no lessons yet.');
            redirect('/courses/' . $course['slug']);
        }
        redirect('/learn/' . $course['slug'] . '/lesson/' . $target['id']);
    }

    public function lesson(Request $request, array $params): void
    {
        [$course, $lesson, $sections, $flat, $enrollment, $access] = $this->context($params['slug'], (int) $params['id']);
        if (!$access['ok']) {
            view('learn/locked', [
                'title' => $lesson['title'],
                'course' => $course,
                'lesson' => $lesson,
                'sections' => $sections,
                'flat' => $flat,
                'enrollment' => $enrollment,
                'access' => $access,
                'progressMap' => Auth::id() ? ProgressService::mapForUser(Auth::id(), (int) $course['id']) : [],
            ], 'layouts/player');
            return;
        }
        if ($enrollment && Auth::id()) {
            ProgressService::recordView(Auth::id(), $lesson);
        }
        $note = null;
        if (Auth::id() && table_exists('lesson_notes')) {
            $note = Database::first('SELECT * FROM lesson_notes WHERE user_id = ? AND lesson_id = ?', [Auth::id(), (int) $lesson['id']]);
        }
        view('learn/player', [
            'title' => $lesson['title'] . ' · ' . $course['title'],
            'course' => $course,
            'lesson' => $lesson,
            'sections' => $sections,
            'flat' => $flat,
            'enrollment' => $enrollment,
            'access' => $access,
            'progressMap' => Auth::id() ? ProgressService::mapForUser(Auth::id(), (int) $course['id']) : [],
            'neighbors' => $this->neighbors($flat, (int) $lesson['id']),
            'note' => $note,
            'notesReady' => table_exists('lesson_notes'),
            'live' => !empty($lesson['live_class_id'])
                ? Database::first('SELECT * FROM live_classes WHERE id = ?', [(int) $lesson['live_class_id']])
                : null,
        ], 'layouts/player');
    }

    public function complete(Request $request, array $params): void
    {
        [$course, $lesson, , $flat, $enrollment, $access] = $this->context($params['slug'], (int) $params['id']);
        if (!$enrollment) {
            abort(403, 'Enroll before marking a lesson complete.');
        }
        if (!$access['ok']) {
            flash('error', $access['reason']);
            back();
        }
        if (in_array($lesson['type'], ['quiz', 'assignment'], true)) {
            flash('error', 'This lesson completes when the work is submitted.');
            redirect('/learn/' . $course['slug'] . '/lesson/' . $lesson['id']);
        }
        ProgressService::complete(Auth::id(), $lesson);
        flash('success', 'Marked complete.');
        $next = $this->neighbors($flat, (int) $lesson['id'])['next'];
        redirect($next ? '/learn/' . $course['slug'] . '/lesson/' . $next['id'] : '/learn/' . $course['slug'] . '/lesson/' . $lesson['id']);
    }

    public function quiz(Request $request, array $params): void
    {
        [$course, $lesson, $sections, $flat, $enrollment, $access] = $this->context($params['slug'], (int) $params['lesson']);
        if ($lesson['type'] !== 'quiz') {
            abort(404);
        }
        if (!$access['ok']) {
            flash('error', $access['reason']);
            redirect('/learn/' . $course['slug'] . '/lesson/' . $lesson['id']);
        }
        $quiz = Database::first('SELECT * FROM quizzes WHERE lesson_id = ?', [(int) $lesson['id']]);
        $questions = Database::select('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position, id', [(int) $quiz['id']]);
        $attempts = Database::select(
            'SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY datetime(submitted_at) DESC',
            [(int) $quiz['id'], Auth::id()]
        );
        $started = Session::get('quiz_started', []);
        $key = $quiz['id'] . ':' . Auth::id();
        if (empty($started[$key])) {
            $started[$key] = now();
            Session::put('quiz_started', $started);
        }
        view('learn/quiz', [
            'title' => $quiz['title'],
            'course' => $course,
            'lesson' => $lesson,
            'sections' => $sections,
            'quiz' => $quiz,
            'questions' => $questions,
            'attempts' => $attempts,
            'startedAt' => $started[$key],
            'enrollment' => $enrollment,
            'progressMap' => ProgressService::mapForUser(Auth::id(), (int) $course['id']),
            'flat' => $flat,
        ], 'layouts/player');
    }

    public function submitQuiz(Request $request, array $params): void
    {
        [$course, $lesson, , , $enrollment, $access] = $this->context($params['slug'], (int) $params['lesson']);
        if (!$enrollment || !$access['ok']) {
            abort(403);
        }
        $quiz = Database::first('SELECT * FROM quizzes WHERE lesson_id = ?', [(int) $lesson['id']]);
        $used = (int) Database::value(
            'SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?',
            [(int) $quiz['id'], Auth::id()]
        );
        if ((int) $quiz['attempts_allowed'] > 0 && $used >= (int) $quiz['attempts_allowed']) {
            flash('error', 'No attempts left on this quiz.');
            redirect('/learn/' . $course['slug'] . '/quiz/' . $lesson['id']);
        }
        $started = Session::get('quiz_started', []);
        $key = $quiz['id'] . ':' . Auth::id();
        $start = $started[$key] ?? now();
        if ((int) $quiz['time_limit_minutes'] > 0) {
            $limit = strtotime($start . ' UTC') + ((int) $quiz['time_limit_minutes'] * 60) + 20;
            if (time() > $limit) {
                flash('error', 'Time is up. The attempt was not recorded. Start again if you have attempts left.');
                unset($started[$key]);
                Session::put('quiz_started', $started);
                redirect('/learn/' . $course['slug'] . '/quiz/' . $lesson['id']);
            }
        }
        $questions = Database::select('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position, id', [(int) $quiz['id']]);
        $grade = QuizService::grade($questions, $request->input('answers', []) ?: []);
        $passed = $grade['score_percent'] >= (int) $quiz['pass_percent'];
        Database::insert('quiz_attempts', [
            'quiz_id' => (int) $quiz['id'],
            'user_id' => Auth::id(),
            'score_percent' => $grade['score_percent'],
            'points_earned' => $grade['points_earned'],
            'points_possible' => $grade['points_possible'],
            'passed' => $passed ? 1 : 0,
            'answers_json' => json_encode(['answers' => $request->input('answers', []), 'detail' => $grade['detail']], json_flags()),
            'started_at' => $start,
            'submitted_at' => now(),
        ]);
        unset($started[$key]);
        Session::put('quiz_started', $started);
        if ($passed) {
            ProgressService::complete(Auth::id(), $lesson);
            flash('success', 'Passed with ' . $grade['score_percent'] . '%.');
        } else {
            flash('error', 'Scored ' . $grade['score_percent'] . '%. The pass mark is ' . $quiz['pass_percent'] . '%.');
        }
        redirect('/learn/' . $course['slug'] . '/quiz/' . $lesson['id']);
    }

    public function assignment(Request $request, array $params): void
    {
        [$course, $lesson, $sections, $flat, $enrollment, $access] = $this->context($params['slug'], (int) $params['lesson']);
        if ($lesson['type'] !== 'assignment') {
            abort(404);
        }
        if (!$access['ok']) {
            flash('error', $access['reason']);
            redirect('/learn/' . $course['slug'] . '/lesson/' . $lesson['id']);
        }
        $assignment = Database::first('SELECT * FROM assignments WHERE lesson_id = ?', [(int) $lesson['id']]);
        $submission = Database::first(
            'SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?',
            [(int) $assignment['id'], Auth::id()]
        );
        view('learn/assignment', [
            'title' => $assignment['title'],
            'course' => $course,
            'lesson' => $lesson,
            'sections' => $sections,
            'flat' => $flat,
            'assignment' => $assignment,
            'submission' => $submission,
            'enrollment' => $enrollment,
            'progressMap' => ProgressService::mapForUser(Auth::id(), (int) $course['id']),
            'dueAt' => $enrollment ? gmdate('Y-m-d H:i:s', (strtotime($enrollment['enrolled_at'] . ' UTC') ?: time()) + ((int) $assignment['due_days'] * 86400)) : null,
        ], 'layouts/player');
    }

    public function submitAssignment(Request $request, array $params): void
    {
        [$course, $lesson, , , $enrollment, $access] = $this->context($params['slug'], (int) $params['lesson']);
        if (!$enrollment || !$access['ok']) {
            abort(403);
        }
        $data = validate(['content' => 'required|min:20|max:20000']);
        $assignment = Database::first('SELECT * FROM assignments WHERE lesson_id = ?', [(int) $lesson['id']]);
        $filePath = null;
        if ($file = $request->file('attachment')) {
            try {
                $stored = StorageService::store($file, 'assignments');
                $filePath = $stored['path'];
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
                back();
            }
        }
        $existing = Database::first(
            'SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?',
            [(int) $assignment['id'], Auth::id()]
        );
        $payload = [
            'content' => $data['content'],
            'file_path' => $filePath ?: ($existing['file_path'] ?? null),
            'status' => 'submitted',
            'score' => null,
            'feedback' => null,
            'graded_at' => null,
            'submitted_at' => now(),
        ];
        if ($existing) {
            Database::update('assignment_submissions', $payload, 'id = ?', [(int) $existing['id']]);
        } else {
            Database::insert('assignment_submissions', $payload + [
                'assignment_id' => (int) $assignment['id'],
                'user_id' => Auth::id(),
            ]);
        }
        ProgressService::complete(Auth::id(), $lesson);
        \App\Services\NotificationService::push(
            (int) $course['instructor_id'],
            'assignment',
            'Assignment submitted',
            Auth::user()['name'] . ' submitted “' . $assignment['title'] . '”.',
            '/studio/assignments/' . $assignment['id']
        );
        flash('success', 'Submitted. The lesson is marked complete; grading is feedback, not a gate.');
        redirect('/learn/' . $course['slug'] . '/assignment/' . $lesson['id']);
    }

    public function note(Request $request, array $params): void
    {
        if (!table_exists('lesson_notes')) {
            flash('error', 'Notes arrive with update 1.1.0. An administrator can apply it from System updates.');
            back();
        }
        [$course, $lesson, , , $enrollment] = $this->context($params['slug'], (int) $params['id']);
        if (!$enrollment && !is_course_staff($course)) {
            abort(403);
        }
        $body = trim($request->string('body'));
        if ($body === '') {
            Database::delete('lesson_notes', 'user_id = ? AND lesson_id = ?', [Auth::id(), (int) $lesson['id']]);
        } else {
            Database::execute(
                'INSERT INTO lesson_notes (user_id, lesson_id, body, updated_at) VALUES (?, ?, ?, ?)
                 ON CONFLICT(user_id, lesson_id) DO UPDATE SET body = excluded.body, updated_at = excluded.updated_at',
                [Auth::id(), (int) $lesson['id'], $body, now()]
            );
        }
        flash('success', 'Note saved.');
        redirect('/learn/' . $course['slug'] . '/lesson/' . $lesson['id']);
    }

    private function context(string $slug, int $lessonId): array
    {
        $course = $this->findCourse($slug);
        $lesson = Database::first('SELECT * FROM lessons WHERE id = ? AND course_id = ?', [$lessonId, (int) $course['id']]);
        if (!$lesson) {
            abort(404);
        }
        $sections = ProgressService::sections((int) $course['id']);
        $flat = ProgressService::flat($sections);
        foreach ($flat as $item) {
            if ((int) $item['id'] === (int) $lesson['id']) {
                $lesson = $item;
                break;
            }
        }
        $enrollment = enrollment_for(Auth::id(), (int) $course['id']);
        if (!$enrollment && !is_course_staff($course) && (int) $lesson['is_preview'] !== 1) {
            flash('error', 'Enroll to open this lesson.');
            redirect('/courses/' . $course['slug']);
        }
        $access = ProgressService::access($course, $lesson, $enrollment, $flat);
        return [$course, $lesson, $sections, $flat, $enrollment, $access];
    }

    private function findCourse(string $slug): array
    {
        $course = CourseService::findBySlug($slug);
        if (!$course) {
            abort(404);
        }
        if ($course['status'] !== 'published' && !is_course_staff($course)) {
            abort(404);
        }
        return $course;
    }

    private function neighbors(array $flat, int $lessonId): array
    {
        $prev = null;
        $next = null;
        foreach ($flat as $i => $lesson) {
            if ((int) $lesson['id'] === $lessonId) {
                $prev = $flat[$i - 1] ?? null;
                $next = $flat[$i + 1] ?? null;
                break;
            }
        }
        return ['prev' => $prev, 'next' => $next];
    }
}
