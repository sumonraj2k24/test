<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;
use App\Services\StorageService;

final class AccountController
{
    public function edit(Request $request, array $params = []): void
    {
        view('account/edit', [
            'title' => 'Profile',
            'active' => 'account',
        ], 'layouts/dashboard');
    }

    public function update(Request $request, array $params = []): void
    {
        $data = validate([
            'name' => 'required|min:2|max:80',
            'headline' => 'max:140',
            'bio' => 'max:2000',
            'payout_method' => 'max:40',
            'payout_details' => 'max:200',
        ]);
        $updates = [
            'name' => $data['name'],
            'headline' => $data['headline'] !== '' ? $data['headline'] : null,
            'bio' => $data['bio'] !== '' ? $data['bio'] : null,
            'payout_method' => $data['payout_method'] !== '' ? $data['payout_method'] : null,
            'payout_details' => $data['payout_details'] !== '' ? $data['payout_details'] : null,
            'updated_at' => now(),
        ];
        if ($password = $request->string('password')) {
            if (strlen($password) < 8) {
                flash('error', 'New password must be at least 8 characters.');
                back('/account');
            }
            $updates['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($file = $request->file('avatar')) {
            try {
                $stored = StorageService::store($file, 'avatars');
                $updates['avatar_path'] = $stored['path'];
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
                back('/account');
            }
        }
        Database::update('users', $updates, 'id = ?', [Auth::id()]);
        Auth::flush();
        flash('success', 'Profile updated.');
        redirect('/account');
    }

    public function notifications(Request $request, array $params = []): void
    {
        view('account/notifications', [
            'title' => 'Notifications',
            'notes' => Database::select(
                'SELECT * FROM notifications WHERE user_id = ? ORDER BY datetime(created_at) DESC LIMIT 80',
                [Auth::id()]
            ),
            'active' => 'notifications',
        ], 'layouts/dashboard');
    }

    public function read(Request $request, array $params = []): void
    {
        if ($id = $request->int('id')) {
            Database::update('notifications', ['read_at' => now()], 'id = ? AND user_id = ?', [$id, Auth::id()]);
            $note = Database::first('SELECT link FROM notifications WHERE id = ? AND user_id = ?', [$id, Auth::id()]);
            if (!empty($note['link'])) {
                redirect($note['link']);
            }
        } else {
            NotificationService::markAll(Auth::id());
            flash('success', 'Notifications marked read.');
        }
        redirect('/notifications');
    }
}
