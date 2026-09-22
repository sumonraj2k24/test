<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Services\AnalyticsService;
use App\Services\UpdateService;

final class DashboardController
{
    public function index(Request $request, array $params = []): void
    {
        view('admin/dashboard', [
            'title' => 'Admin',
            'active' => 'overview',
            'stats' => AnalyticsService::admin(),
            'pendingCourses' => Database::select(
                "SELECT c.*, u.name AS instructor_name FROM courses c INNER JOIN users u ON u.id = c.instructor_id WHERE c.status = 'pending' ORDER BY datetime(c.updated_at) DESC LIMIT 5"
            ),
            'applications' => Database::select(
                "SELECT a.*, u.name, u.email FROM instructor_applications a INNER JOIN users u ON u.id = a.user_id WHERE a.status = 'pending' ORDER BY datetime(a.created_at) DESC LIMIT 5"
            ),
            'audit' => Database::select(
                'SELECT a.*, u.name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY datetime(a.created_at) DESC LIMIT 8'
            ),
            'updates' => UpdateService::pending(),
        ], 'layouts/dashboard');
    }

    public function analytics(Request $request, array $params = []): void
    {
        view('admin/analytics', [
            'title' => 'Platform analytics',
            'active' => 'analytics',
            'stats' => AnalyticsService::admin(),
        ], 'layouts/dashboard');
    }
}
