<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

final class UserController
{
    public function index(Request $request, array $params = []): void
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $sql = 'SELECT * FROM users WHERE 1 = 1';
        $bind = [];
        if ($q !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ?)';
            $bind[] = '%' . $q . '%';
            $bind[] = '%' . $q . '%';
        }
        if ($role !== '') {
            $sql .= ' AND role = ?';
            $bind[] = $role;
        }
        $sql .= ' ORDER BY datetime(created_at) DESC';
        view('admin/users', [
            'title' => 'Users',
            'active' => 'users',
            'users' => Database::select($sql, $bind),
            'roles' => Database::select('SELECT * FROM roles ORDER BY is_system DESC, name'),
            'q' => $q,
            'role' => $role,
        ], 'layouts/dashboard');
    }

    public function update(Request $request, array $params): void
    {
        $user = Database::first('SELECT * FROM users WHERE id = ?', [(int) $params['id']]);
        if (!$user) {
            abort(404);
        }
        $role = $request->string('role');
        if (!Database::first('SELECT slug FROM roles WHERE slug = ?', [$role])) {
            flash('error', 'Unknown role.');
            back();
        }
        if ((int) $user['id'] === Auth::id() && $role !== 'admin') {
            flash('error', 'You cannot remove your own admin role from this screen.');
            back();
        }
        $status = $request->string('status') === 'suspended' ? 'suspended' : 'active';
        Database::update('users', [
            'role' => $role,
            'status' => $status,
            'name' => $request->string('name') ?: $user['name'],
            'updated_at' => now(),
        ], 'id = ?', [(int) $user['id']]);
        audit('user.update', $user['email'], ['role' => $role, 'status' => $status]);
        flash('success', 'User updated.');
        redirect('/admin/users');
    }

    public function applications(Request $request, array $params = []): void
    {
        view('admin/applications', [
            'title' => 'Instructor applications',
            'active' => 'applications',
            'applications' => Database::select(
                'SELECT a.*, u.name, u.email, u.headline FROM instructor_applications a INNER JOIN users u ON u.id = a.user_id ORDER BY CASE a.status WHEN "pending" THEN 0 ELSE 1 END, datetime(a.created_at) DESC'
            ),
        ], 'layouts/dashboard');
    }

    public function reviewApplication(Request $request, array $params): void
    {
        $app = Database::first('SELECT * FROM instructor_applications WHERE id = ?', [(int) $params['id']]);
        if (!$app) {
            abort(404);
        }
        $decision = $request->string('decision') === 'approved' ? 'approved' : 'rejected';
        Database::update('instructor_applications', [
            'status' => $decision,
            'review_note' => $request->string('review_note') ?: null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ], 'id = ?', [(int) $app['id']]);
        if ($decision === 'approved') {
            Database::update('users', ['role' => 'instructor', 'updated_at' => now()], 'id = ?', [(int) $app['user_id']]);
            NotificationService::push((int) $app['user_id'], 'application', 'You can teach', 'Your instructor application was approved. The studio is open.', '/studio');
        } else {
            NotificationService::push((int) $app['user_id'], 'application', 'Application not approved', $request->string('review_note') ?: 'The studio passed on this application.', '/teach');
        }
        audit('application.' . $decision, 'user:' . $app['user_id']);
        flash('success', 'Application ' . $decision . '.');
        redirect('/admin/applications');
    }
}
