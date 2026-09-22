<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

final class MessageController
{
    public function index(Request $request, array $params = []): void
    {
        $userId = Auth::id();
        $threads = Database::select(
            'SELECT u.id, u.name, u.headline, u.role,
                    (SELECT body FROM messages m WHERE (m.sender_id = u.id AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = u.id) ORDER BY datetime(m.created_at) DESC LIMIT 1) AS last_body,
                    (SELECT created_at FROM messages m WHERE (m.sender_id = u.id AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = u.id) ORDER BY datetime(m.created_at) DESC LIMIT 1) AS last_at,
                    (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.recipient_id = ? AND m.read_at IS NULL) AS unread
             FROM users u
             WHERE u.id IN (
                SELECT sender_id FROM messages WHERE recipient_id = ?
                UNION
                SELECT recipient_id FROM messages WHERE sender_id = ?
             )
             ORDER BY datetime(last_at) DESC',
            [$userId, $userId, $userId, $userId, $userId, $userId, $userId]
        );
        view('messages/index', [
            'title' => 'Messages',
            'threads' => $threads,
            'active' => 'messages',
        ], 'layouts/dashboard');
    }

    public function show(Request $request, array $params): void
    {
        $other = Database::first('SELECT id, name, headline, role FROM users WHERE id = ?', [(int) $params['id']]);
        if (!$other || (int) $other['id'] === Auth::id()) {
            abort(404);
        }
        Database::execute(
            'UPDATE messages SET read_at = ? WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL',
            [now(), (int) $other['id'], Auth::id()]
        );
        $messages = Database::select(
            'SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY datetime(created_at)',
            [Auth::id(), (int) $other['id'], (int) $other['id'], Auth::id()]
        );
        view('messages/show', [
            'title' => $other['name'],
            'other' => $other,
            'messages' => $messages,
            'courseId' => $request->int('course'),
            'active' => 'messages',
        ], 'layouts/dashboard');
    }

    public function store(Request $request, array $params = []): void
    {
        $data = validate([
            'recipient_id' => 'required|integer',
            'body' => 'required|min:1|max:8000',
            'subject' => 'max:160',
        ]);
        $recipient = Database::first('SELECT * FROM users WHERE id = ? AND status = ?', [(int) $data['recipient_id'], 'active']);
        if (!$recipient || (int) $recipient['id'] === Auth::id()) {
            abort(404);
        }
        Database::insert('messages', [
            'sender_id' => Auth::id(),
            'recipient_id' => (int) $recipient['id'],
            'course_id' => $request->int('course_id') ?: null,
            'subject' => $data['subject'] !== '' ? $data['subject'] : null,
            'body' => $data['body'],
            'created_at' => now(),
        ]);
        NotificationService::push(
            (int) $recipient['id'],
            'message',
            'Message from ' . Auth::user()['name'],
            mb_strimwidth($data['body'], 0, 140, '…'),
            '/messages/with/' . Auth::id()
        );
        flash('success', 'Message sent.');
        redirect('/messages/with/' . $recipient['id']);
    }
}
