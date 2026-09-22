<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Core\Database;
use App\Core\Request;
use App\Services\CourseService;
use App\Services\ProgressService;
use App\Services\StorageService;

final class CurriculumController
{
    public function edit(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        view('instructor/curriculum', [
            'title' => 'Curriculum · ' . $course['title'],
            'active' => 'studio-courses',
            'course' => $course,
            'sections' => ProgressService::sections((int) $course['id']),
            'editLesson' => $request->int('lesson'),
        ], 'layouts/dashboard');
    }

    public function addSection(Request $request, array $params): void
    {
        $course = $this->course((int) $params['id']);
        $data = validate(['title' => 'required|min:2|max:140']);
        $position = (int) Database::value('SELECT COALESCE(MAX(position),0) + 1 FROM sections WHERE course_id = ?', [(int) $course['id']]);
        Database::insert('sections', [
            'course_id' => (int) $course['id'],
            'title' => $data['title'],
            'position' => $position,
            'drip_days' => max(0, $request->int('drip_days')),
        ]);
        CourseService::touch($course);
        flash('success', 'Section added.');
        redirect('/studio/courses/' . $course['id'] . '/curriculum');
    }

    public function updateSection(Request $request, array $params): void
    {
        $section = $this->section((int) $params['id']);
        $data = validate(['title' => 'required|min:2|max:140']);
        Database::update('sections', [
            'title' => $data['title'],
            'drip_days' => max(0, $request->int('drip_days')),
        ], 'id = ?', [(int) $section['id']]);
        CourseService::touch($this->course((int) $section['course_id']));
        flash('success', 'Section updated.');
        redirect('/studio/courses/' . $section['course_id'] . '/curriculum');
    }

    public function deleteSection(Request $request, array $params): void
    {
        $section = $this->section((int) $params['id']);
        Database::delete('lessons', 'section_id = ?', [(int) $section['id']]);
        Database::delete('sections', 'id = ?', [(int) $section['id']]);
        CourseService::refreshDuration((int) $section['course_id']);
        flash('success', 'Section removed.');
        redirect('/studio/courses/' . $section['course_id'] . '/curriculum');
    }

    public function addLesson(Request $request, array $params): void
    {
        $section = $this->section((int) $params['id']);
        $data = validate([
            'title' => 'required|min:2|max:160',
            'type' => 'required|in:video,article,document,quiz,assignment,live',
        ]);
        $position = (int) Database::value('SELECT COALESCE(MAX(position),0) + 1 FROM lessons WHERE section_id = ?', [(int) $section['id']]);
        $attachment = null;
        if ($file = $request->file('attachment')) {
            $attachment = StorageService::store($file, 'lessons')['path'];
        }
        $lessonId = Database::insert('lessons', [
            'course_id' => (int) $section['course_id'],
            'section_id' => (int) $section['id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'summary' => $request->string('summary') ?: null,
            'content' => $request->string('content'),
            'video_url' => $request->string('video_url') ?: null,
            'attachment_path' => $attachment,
            'duration_minutes' => max(1, $request->int('duration_minutes', 10)),
            'is_preview' => $request->input('is_preview') ? 1 : 0,
            'position' => $position,
            'drip_at' => $request->string('drip_at') ?: null,
        ]);
        if ($data['type'] === 'quiz') {
            Database::insert('quizzes', [
                'lesson_id' => $lessonId,
                'course_id' => (int) $section['course_id'],
                'title' => $data['title'],
                'pass_percent' => 70,
                'time_limit_minutes' => 10,
                'attempts_allowed' => 3,
            ]);
        }
        if ($data['type'] === 'assignment') {
            Database::insert('assignments', [
                'lesson_id' => $lessonId,
                'course_id' => (int) $section['course_id'],
                'title' => $data['title'],
                'instructions' => $request->string('content') ?: 'Describe the work.',
                'due_days' => 7,
                'max_score' => 100,
            ]);
        }
        CourseService::refreshDuration((int) $section['course_id']);
        CourseService::touch($this->course((int) $section['course_id']));
        flash('success', 'Lesson added.');
        $tail = $data['type'] === 'quiz' ? '/studio/quizzes/' . Database::value('SELECT id FROM quizzes WHERE lesson_id = ?', [$lessonId]) : '/studio/courses/' . $section['course_id'] . '/curriculum?lesson=' . $lessonId;
        redirect($tail);
    }

    public function updateLesson(Request $request, array $params): void
    {
        $lesson = $this->lesson((int) $params['id']);
        $data = validate([
            'title' => 'required|min:2|max:160',
            'type' => 'required|in:video,article,document,quiz,assignment,live',
        ]);
        $attachment = $lesson['attachment_path'];
        if ($file = $request->file('attachment')) {
            $attachment = StorageService::store($file, 'lessons')['path'];
        }
        Database::update('lessons', [
            'title' => $data['title'],
            'type' => $data['type'],
            'summary' => $request->string('summary') ?: null,
            'content' => $request->string('content'),
            'video_url' => $request->string('video_url') ?: null,
            'attachment_path' => $attachment,
            'duration_minutes' => max(1, $request->int('duration_minutes', 10)),
            'is_preview' => $request->input('is_preview') ? 1 : 0,
            'drip_at' => $request->string('drip_at') ?: null,
        ], 'id = ?', [(int) $lesson['id']]);
        if ($data['type'] === 'quiz' && !Database::first('SELECT id FROM quizzes WHERE lesson_id = ?', [(int) $lesson['id']])) {
            Database::insert('quizzes', [
                'lesson_id' => (int) $lesson['id'],
                'course_id' => (int) $lesson['course_id'],
                'title' => $data['title'],
                'pass_percent' => 70,
                'time_limit_minutes' => 10,
                'attempts_allowed' => 3,
            ]);
        }
        if ($data['type'] === 'assignment') {
            $existing = Database::first('SELECT id FROM assignments WHERE lesson_id = ?', [(int) $lesson['id']]);
            if ($existing) {
                Database::update('assignments', [
                    'title' => $data['title'],
                    'instructions' => $request->string('content') ?: 'Describe the work.',
                    'due_days' => max(1, $request->int('due_days', 7)),
                ], 'id = ?', [(int) $existing['id']]);
            }
        }
        CourseService::refreshDuration((int) $lesson['course_id']);
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $lesson['course_id']]);
        CourseService::touch($course);
        flash('success', 'Lesson saved.');
        redirect('/studio/courses/' . $lesson['course_id'] . '/curriculum?lesson=' . $lesson['id']);
    }

    public function deleteLesson(Request $request, array $params): void
    {
        $lesson = $this->lesson((int) $params['id']);
        Database::delete('lessons', 'id = ?', [(int) $lesson['id']]);
        CourseService::refreshDuration((int) $lesson['course_id']);
        flash('success', 'Lesson removed.');
        redirect('/studio/courses/' . $lesson['course_id'] . '/curriculum');
    }

    public function move(Request $request, array $params): void
    {
        $lesson = $this->lesson((int) $params['id']);
        $direction = $request->string('direction') === 'up' ? -1 : 1;
        $siblings = Database::select('SELECT id, position FROM lessons WHERE section_id = ? ORDER BY position, id', [(int) $lesson['section_id']]);
        $index = 0;
        foreach ($siblings as $i => $sibling) {
            if ((int) $sibling['id'] === (int) $lesson['id']) {
                $index = $i;
                break;
            }
        }
        $swap = $siblings[$index + $direction] ?? null;
        if ($swap) {
            Database::update('lessons', ['position' => (int) $swap['position']], 'id = ?', [(int) $lesson['id']]);
            Database::update('lessons', ['position' => (int) $lesson['position']], 'id = ?', [(int) $swap['id']]);
        }
        redirect('/studio/courses/' . $lesson['course_id'] . '/curriculum');
    }

    private function course(int $id): array
    {
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [$id]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        return $course;
    }

    private function section(int $id): array
    {
        $section = Database::first('SELECT * FROM sections WHERE id = ?', [$id]);
        if (!$section || !can_edit_course($this->course((int) $section['course_id']))) {
            abort(403);
        }
        return $section;
    }

    private function lesson(int $id): array
    {
        $lesson = Database::first('SELECT * FROM lessons WHERE id = ?', [$id]);
        if (!$lesson || !can_edit_course($this->course((int) $lesson['course_id']))) {
            abort(403);
        }
        return $lesson;
    }
}
