<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

final class QuizController
{
    public function results(Request $request, array $params = []): void
    {
        $rows = Database::select(
            'SELECT qa.*, q.title AS quiz_title, c.title AS course_title, u.name AS student_name
             FROM quiz_attempts qa
             INNER JOIN quizzes q ON q.id = qa.quiz_id
             INNER JOIN courses c ON c.id = q.course_id
             INNER JOIN users u ON u.id = qa.user_id
             WHERE c.instructor_id = ?
             ORDER BY datetime(qa.submitted_at) DESC LIMIT 40',
            [Auth::id()]
        );
        view('instructor/quizzes', [
            'title' => 'Quiz performance',
            'active' => 'studio-quizzes',
            'attempts' => $rows,
            'assignments' => Database::select(
                'SELECT a.*, c.title AS course_title,
                        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) AS submissions,
                        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.status = "submitted") AS waiting
                 FROM assignments a INNER JOIN courses c ON c.id = a.course_id
                 WHERE c.instructor_id = ? ORDER BY c.title',
                [Auth::id()]
            ),
        ], 'layouts/dashboard');
    }

    public function edit(Request $request, array $params): void
    {
        $quiz = $this->quiz((int) $params['id']);
        view('instructor/quiz-edit', [
            'title' => 'Quiz · ' . $quiz['title'],
            'active' => 'studio-courses',
            'quiz' => $quiz,
            'questions' => Database::select('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position, id', [(int) $quiz['id']]),
        ], 'layouts/dashboard');
    }

    public function update(Request $request, array $params): void
    {
        $quiz = $this->quiz((int) $params['id']);
        $data = validate([
            'title' => 'required|min:2|max:160',
            'pass_percent' => 'required|integer',
            'time_limit_minutes' => 'required|integer',
            'attempts_allowed' => 'required|integer',
        ]);
        Database::update('quizzes', [
            'title' => $data['title'],
            'description' => $request->string('description') ?: null,
            'pass_percent' => min(100, max(1, (int) $data['pass_percent'])),
            'time_limit_minutes' => max(0, (int) $data['time_limit_minutes']),
            'attempts_allowed' => max(0, (int) $data['attempts_allowed']),
        ], 'id = ?', [(int) $quiz['id']]);
        Database::update('lessons', ['title' => $data['title']], 'id = ?', [(int) $quiz['lesson_id']]);
        flash('success', 'Quiz settings saved.');
        redirect('/studio/quizzes/' . $quiz['id']);
    }

    public function addQuestion(Request $request, array $params): void
    {
        $quiz = $this->quiz((int) $params['id']);
        $data = validate([
            'question' => 'required|min:4|max:500',
            'type' => 'required|in:single,multi,text',
        ]);
        $options = [];
        $correct = [];
        if ($data['type'] === 'text') {
            $correct = array_values(array_filter(array_map('trim', explode(',', $request->string('accepted')))));
            if ($correct === []) {
                flash('error', 'Add at least one accepted answer.');
                back();
            }
        } else {
            $texts = $request->input('options', []);
            $marked = $request->input('correct', []);
            $marked = is_array($marked) ? $marked : [$marked];
            foreach ((array) $texts as $i => $text) {
                $text = trim((string) $text);
                if ($text === '') {
                    continue;
                }
                $id = (string) count($options);
                $options[] = ['id' => $id, 'text' => $text];
                if (in_array((string) $i, array_map('strval', $marked), true)) {
                    $correct[] = $id;
                }
            }
            if (count($options) < 2 || $correct === []) {
                flash('error', 'Add at least two options and mark a correct one.');
                back();
            }
        }
        $position = (int) Database::value('SELECT COALESCE(MAX(position),0)+1 FROM quiz_questions WHERE quiz_id = ?', [(int) $quiz['id']]);
        Database::insert('quiz_questions', [
            'quiz_id' => (int) $quiz['id'],
            'question' => $data['question'],
            'type' => $data['type'],
            'options_json' => json_encode($options, json_flags()),
            'correct_json' => json_encode($correct, json_flags()),
            'points' => max(1, $request->int('points', 1)),
            'position' => $position,
        ]);
        flash('success', 'Question added.');
        redirect('/studio/quizzes/' . $quiz['id']);
    }

    public function deleteQuestion(Request $request, array $params): void
    {
        $question = Database::first(
            'SELECT qq.*, q.course_id FROM quiz_questions qq INNER JOIN quizzes q ON q.id = qq.quiz_id WHERE qq.id = ?',
            [(int) $params['id']]
        );
        if (!$question) {
            abort(404);
        }
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $question['course_id']]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        Database::delete('quiz_questions', 'id = ?', [(int) $question['id']]);
        redirect('/studio/quizzes/' . $question['quiz_id']);
    }

    public function gradeList(Request $request, array $params): void
    {
        $assignment = $this->assignment((int) $params['id']);
        view('instructor/grade', [
            'title' => 'Grade · ' . $assignment['title'],
            'active' => 'studio-quizzes',
            'assignment' => $assignment,
            'submissions' => Database::select(
                'SELECT s.*, u.name, u.email FROM assignment_submissions s INNER JOIN users u ON u.id = s.user_id WHERE s.assignment_id = ? ORDER BY datetime(s.submitted_at) DESC',
                [(int) $assignment['id']]
            ),
        ], 'layouts/dashboard');
    }

    public function grade(Request $request, array $params): void
    {
        $submission = Database::first(
            'SELECT s.*, a.course_id, a.title FROM assignment_submissions s INNER JOIN assignments a ON a.id = s.assignment_id WHERE s.id = ?',
            [(int) $params['id']]
        );
        if (!$submission) {
            abort(404);
        }
        $course = Database::first('SELECT * FROM courses WHERE id = ?', [(int) $submission['course_id']]);
        if (!$course || !can_edit_course($course)) {
            abort(403);
        }
        $score = max(0, min(100, $request->int('score')));
        Database::update('assignment_submissions', [
            'score' => $score,
            'feedback' => $request->string('feedback') ?: null,
            'status' => 'graded',
            'graded_at' => now(),
        ], 'id = ?', [(int) $submission['id']]);
        NotificationService::push(
            (int) $submission['user_id'],
            'assignment',
            'Assignment graded',
            $submission['title'] . ' · ' . $score . '/100',
            '/learn'
        );
        flash('success', 'Grade saved.');
        redirect('/studio/assignments/' . $submission['assignment_id']);
    }

    private function quiz(int $id): array
    {
        $quiz = Database::first(
            'SELECT q.*, c.instructor_id, c.status, c.title AS course_title FROM quizzes q INNER JOIN courses c ON c.id = q.course_id WHERE q.id = ?',
            [$id]
        );
        if (!$quiz || !can_edit_course($quiz)) {
            abort(403);
        }
        return $quiz;
    }

    private function assignment(int $id): array
    {
        $assignment = Database::first(
            'SELECT a.*, c.instructor_id, c.status, c.title AS course_title FROM assignments a INNER JOIN courses c ON c.id = a.course_id WHERE a.id = ?',
            [$id]
        );
        if (!$assignment || !can_edit_course($assignment)) {
            abort(403);
        }
        return $assignment;
    }
}
