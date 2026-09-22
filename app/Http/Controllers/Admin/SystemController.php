<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\BackupService;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Services\UpdateService;

final class SystemController
{
    public function settings(Request $request, array $params = []): void
    {
        view('admin/settings', [
            'title' => 'Settings',
            'active' => 'settings',
        ], 'layouts/dashboard');
    }

    public function saveSettings(Request $request, array $params = []): void
    {
        $keys = [
            'currency', 'platform_commission_percent', 'course_management_mode', 'require_course_approval',
            'support_email', 'storage_disk', 'max_upload_mb', 's3_key', 's3_secret', 's3_region', 's3_bucket', 's3_endpoint',
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from', 'smtp_from_name',
            'google_client_id', 'google_client_secret',
            'zoom_account_id', 'zoom_client_id', 'zoom_client_secret',
            'stripe_secret', 'stripe_webhook_secret', 'paypal_client_id', 'paypal_secret', 'paypal_mode',
            'mollie_api_key', 'paystack_secret',
            'backup_interval_hours', 'backup_retention', 'cron_token', 'app_url',
        ];
        $pairs = [];
        foreach ($keys as $key) {
            if ($key === 'require_course_approval') {
                $pairs[$key] = $request->input('require_course_approval') ? '1' : '0';
                continue;
            }
            if (str_starts_with($key, 'pay_')) {
                continue;
            }
            $pairs[$key] = $request->string($key);
        }
        foreach (['stripe', 'paypal', 'mollie', 'paystack'] as $gateway) {
            $pairs['pay_' . $gateway . '_enabled'] = $request->input('pay_' . $gateway . '_enabled') ? '1' : '0';
        }
        if (!in_array($pairs['course_management_mode'], ['collaborative', 'centralized'], true)) {
            $pairs['course_management_mode'] = 'collaborative';
        }
        if (!in_array($pairs['storage_disk'], ['local', 's3'], true)) {
            $pairs['storage_disk'] = 'local';
        }
        SettingsService::setMany($pairs);
        audit('settings.update', 'system');
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
    }

    public function testMail(Request $request, array $params = []): void
    {
        $to = $request->string('email', (string) setting('support_email', 'studio@meridian.test'));
        $ok = MailService::send($to, 'Meridian SMTP test', '<p>If SMTP is configured, this left the server. Either way, a copy is in the mail log.</p>');
        flash($ok ? 'success' : 'error', $ok ? 'Test message written. Check the mail log, and the inbox if SMTP is set.' : 'SMTP refused the message. The copy is still in the mail log.');
        redirect('/admin/settings#mail');
    }

    public function permissions(Request $request, array $params = []): void
    {
        view('admin/permissions', [
            'title' => 'Permissions',
            'active' => 'permissions',
            'roles' => Database::select('SELECT * FROM roles ORDER BY is_system DESC, name'),
            'permissions' => Database::select('SELECT * FROM permissions ORDER BY group_name, name'),
            'grants' => Database::select('SELECT role, permission_id FROM role_permissions'),
        ], 'layouts/dashboard');
    }

    public function savePermissions(Request $request, array $params = []): void
    {
        $posted = (array) $request->input('grant', []);
        $roles = Database::select('SELECT slug FROM roles');
        $permissions = Database::select('SELECT id, slug FROM permissions');
        $bySlug = [];
        foreach ($permissions as $permission) {
            $bySlug[$permission['slug']] = (int) $permission['id'];
        }
        Database::transaction(function () use ($roles, $posted, $bySlug): void {
            foreach ($roles as $role) {
                $slug = $role['slug'];
                Database::delete('role_permissions', 'role = ?', [$slug]);
                $selected = array_map('intval', (array) ($posted[$slug] ?? []));
                if ($slug === 'admin') {
                    foreach (['admin.access', 'settings.manage', 'roles.manage'] as $required) {
                        if (isset($bySlug[$required])) {
                            $selected[] = $bySlug[$required];
                        }
                    }
                }
                foreach (array_unique($selected) as $permissionId) {
                    if (in_array($permissionId, $bySlug, true)) {
                        Database::insert('role_permissions', [
                            'role' => $slug,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        });
        audit('roles.update', 'permissions');
        flash('success', 'Permission matrix saved. Admin keeps access, settings, and role management.');
        redirect('/admin/permissions');
    }

    public function createRole(Request $request, array $params = []): void
    {
        $data = validate(['name' => 'required|min:2|max:40']);
        $slug = slugify($data['name']);
        if (Database::first('SELECT slug FROM roles WHERE slug = ?', [$slug])) {
            flash('error', 'That role already exists.');
            redirect('/admin/permissions');
        }
        Database::insert('roles', ['slug' => $slug, 'name' => $data['name'], 'is_system' => 0]);
        audit('roles.create', $slug);
        flash('success', 'Role created. Grant permissions, then assign it on a user.');
        redirect('/admin/permissions');
    }

    public function backups(Request $request, array $params = []): void
    {
        view('admin/backups', [
            'title' => 'Backups',
            'active' => 'backups',
            'backups' => Database::select('SELECT b.*, u.name FROM backups b LEFT JOIN users u ON u.id = b.created_by ORDER BY datetime(b.created_at) DESC'),
        ], 'layouts/dashboard');
    }

    public function createBackup(Request $request, array $params = []): void
    {
        $backup = BackupService::create(Auth::id());
        audit('backup.create', $backup['filename']);
        flash('success', 'Backup written: ' . $backup['filename']);
        redirect('/admin/backups');
    }

    public function downloadBackup(Request $request, array $params): void
    {
        $backup = Database::first('SELECT * FROM backups WHERE id = ?', [(int) $params['id']]);
        if (!$backup) {
            abort(404);
        }
        $path = storage_path('backups/' . $backup['filename']);
        if (!is_file($path)) {
            abort(404, 'Backup file is missing from disk.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    public function updates(Request $request, array $params = []): void
    {
        view('admin/updates', [
            'title' => 'System updates',
            'active' => 'updates',
            'pending' => UpdateService::pending(),
            'history' => Database::select('SELECT s.*, u.name FROM system_updates s LEFT JOIN users u ON u.id = s.applied_by ORDER BY datetime(s.applied_at) DESC'),
            'code' => UpdateService::codeVersion(),
            'database' => UpdateService::databaseVersion(),
        ], 'layouts/dashboard');
    }

    public function applyUpdate(Request $request, array $params = []): void
    {
        try {
            $version = UpdateService::applyNext(Auth::id());
            flash('success', 'Applied ' . $version . '. A backup was taken first.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/updates');
    }
}
