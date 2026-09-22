<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\SettingsService;
use App\Support\Markdown;

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require BASE_PATH . '/config/app.php';
    }
    return $cfg[$key] ?? $default;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setting(string $key, mixed $default = null): mixed
{
    return SettingsService::get($key, $default);
}

function url(string $path = '/'): string
{
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    $base = rtrim((string) (setting('app_url', '') ?: ''), '/');
    if ($base === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = ($https ? 'https' : 'http') . '://' . $host;
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function back(string $fallback = '/'): never
{
    $to = $_SERVER['HTTP_REFERER'] ?? $fallback;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $parts = parse_url($to);
    if (isset($parts['host']) && $parts['host'] !== $host) {
        $to = $fallback;
    }
    redirect($to);
}

function view(string $name, array $data = [], ?string $layout = 'layouts/app'): void
{
    View::render($name, $data, $layout);
}

function partial(string $name, array $data = []): void
{
    View::partial($name, $data);
}

function abort(int $status, string $message = ''): never
{
    throw new App\Core\HttpException($status, $message);
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function unique_slug(string $table, string $value, ?int $ignoreId = null): string
{
    $base = slugify($value);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        if (!Database::first($sql, $params)) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function money(int $cents, ?string $currency = null): string
{
    $currency = $currency ?: (string) setting('currency', 'USD');
    $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦'];
    $symbol = $symbols[$currency] ?? ($currency . ' ');
    $negative = $cents < 0;
    $formatted = number_format(abs($cents) / 100, 2);
    return ($negative ? '−' : '') . $symbol . $formatted;
}

function pretty_date(?string $iso): string
{
    if (!$iso) {
        return '—';
    }
    $t = strtotime($iso . (strlen($iso) === 10 ? ' 00:00:00' : '') . ' UTC');
    return $t ? gmdate('M j, Y', $t) : $iso;
}

function pretty_datetime(?string $iso): string
{
    if (!$iso) {
        return '—';
    }
    $t = strtotime($iso . ' UTC');
    return $t ? gmdate('M j, Y · H:i', $t) . ' UTC' : $iso;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'M';
}

function md(?string $text): string
{
    return Markdown::render($text);
}

function excerpt(?string $text, int $limit = 160): string
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags(Markdown::render($text))) ?? '');
    if (strlen($plain) <= $limit) {
        return $plain;
    }
    return rtrim(substr($plain, 0, $limit - 1)) . '…';
}

function csrf_token(): string
{
    return App\Core\Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::flashed('old') ?? [];
    return $old[$key] ?? $default;
}

function errors(): array
{
    $errors = Session::flashed('errors');
    return is_array($errors) ? $errors : [];
}

function error(string $key): ?string
{
    return errors()[$key] ?? null;
}

function flash(string $key, mixed $value): void
{
    Session::flash($key, $value);
}

function validate(array $rules, ?array $data = null): array
{
    $data = $data ?? App\Core\Request::capture()->all();
    $errors = Validator::check($data, $rules);
    if ($errors) {
        $old = $data;
        unset($old['password'], $old['password_confirmation'], $old['_token']);
        Session::flash('errors', $errors);
        Session::flash('old', $old);
        back();
    }
    return $data;
}

function storage_path(string $path = ''): string
{
    return BASE_PATH . '/storage' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function public_path(string $path = ''): string
{
    return BASE_PATH . '/public' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function table_exists(string $table): bool
{
    return Database::tableExists($table);
}

function audit(string $action, ?string $subject = null, array $meta = []): void
{
    Database::insert('audit_logs', [
        'user_id' => Auth::id(),
        'action' => $action,
        'subject' => $subject,
        'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        'created_at' => now(),
    ]);
}

function course_level_label(string $level): string
{
    return match ($level) {
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        default => 'All levels',
    };
}

function lesson_type_label(string $type): string
{
    return match ($type) {
        'video' => 'Video',
        'article' => 'Reading',
        'document' => 'Document',
        'quiz' => 'Quiz',
        'assignment' => 'Assignment',
        'live' => 'Live class',
        default => ucfirst($type),
    };
}

function can_edit_course(array $course): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }
    if (Auth::can('courses.manage')) {
        return true;
    }
    if ((int) $course['instructor_id'] === (int) $user['id'] && Auth::can('courses.create')) {
        return true;
    }
    if (setting('course_management_mode', 'collaborative') === 'collaborative') {
        $row = Database::first(
            'SELECT 1 AS ok FROM course_instructors WHERE course_id = ? AND user_id = ?',
            [(int) $course['id'], (int) $user['id']]
        );
        return $row !== null && Auth::can('courses.create');
    }
    return false;
}

function is_course_staff(array $course): bool
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }
    if (Auth::can('courses.manage') || Auth::can('courses.review')) {
        return true;
    }
    if ((int) $course['instructor_id'] === (int) $user['id']) {
        return true;
    }
    return (bool) Database::first(
        'SELECT 1 AS ok FROM course_instructors WHERE course_id = ? AND user_id = ?',
        [(int) $course['id'], (int) $user['id']]
    );
}

function enrollment_for(?int $userId, int $courseId): ?array
{
    if (!$userId) {
        return null;
    }
    return Database::first(
        'SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?',
        [$userId, $courseId]
    );
}

function safe_hex(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

function icon(string $name): string
{
    $paths = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/>',
        'book' => '<path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H20v16H7.5A2.5 2.5 0 0 0 5 21.5z"/><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3"/>',
        'play' => '<path d="M8 6.5v11l9-5.5z"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/>',
        'cart' => '<circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/><path d="M3 4h2l2.2 11h11.3l2-7H7"/>',
        'cert' => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5 7 21l5-2 5 2-1.5-7.5"/>',
        'message' => '<path d="M5 6h14v9H8l-3 3z"/>',
        'bell' => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'users' => '<path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="8" r="3"/><path d="M20 20v-1.2a3 3 0 0 0-2.2-2.9"/><path d="M16 5.2a3 3 0 0 1 0 5.6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21M5.6 5.6l1.6 1.6M16.8 16.8l1.6 1.6M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6"/>',
        'chart' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-3"/>',
        'live' => '<circle cx="12" cy="12" r="2"/><path d="M7.5 7.5a6.4 6.4 0 0 0 0 9"/><path d="M16.5 7.5a6.4 6.4 0 0 1 0 9"/><path d="M5 5a10 10 0 0 0 0 14"/><path d="M19 5a10 10 0 0 1 0 14"/>',
        'dollar' => '<path d="M12 3v18"/><path d="M16.5 7.5c0-1.8-2-3-4.5-3S7.5 5.7 7.5 7.8 9.4 11 12 11s4.8 1.2 4.8 3.4-2.1 3.6-4.8 3.6-4.5-1.4-4.5-3.2"/>',
        'folder' => '<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6H9l2 2h8.5A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5z"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 9 4.2-1.8 7-4.8 7-9V6z"/>',
        'backup' => '<path d="M12 4v10"/><path d="m8 10 4 4 4-4"/><path d="M5 18h14"/>',
        'mail' => '<path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/>',
        'image' => '<rect x="4" y="5" width="16" height="14" rx="1.5"/><circle cx="9" cy="10" r="1.4"/><path d="m7 17 3.2-3.2a1 1 0 0 1 1.4 0L16 17"/>',
        'page' => '<path d="M7 3.5h7l5 5V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1z"/><path d="M14 3.5V9h5"/>',
        'tag' => '<path d="M3 12.5 12.5 3H20v7.5L10.5 20z"/><circle cx="15.5" cy="8.5" r="1.2"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m20 20-3.5-3.5"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="1.5"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/>',
        'star' => '<path d="m12 3 2.4 5.2 5.6.6-4.2 3.8 1.2 5.5L12 15.8 6.9 18.1 8.1 12.6 3.9 8.8l5.6-.6z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'download' => '<path d="M12 4v10"/><path d="m8 10 4 4 4-4"/><path d="M5 19h14"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
        'quiz' => '<circle cx="12" cy="12" r="8"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.4 2.3c-.7.4-1.4 1-1.4 1.9"/><path d="M12 17h.01"/>',
        'doc' => '<path d="M7 3.5h7l5 5V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1z"/><path d="M9 13h6M9 17h4"/>',
        'out' => '<path d="M10 7V5H5v14h5v-2"/><path d="M10 12h9"/><path d="m16 8 4 4-4 4"/>',
    ];
    $body = $paths[$name] ?? $paths['book'];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function stars(float $rating): string
{
    $full = (int) round($rating);
    $html = '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $full ? 'on' : '') . '">★</span>';
    }
    $html .= '</span>';
    return $html;
}

function status_badge(string $status): string
{
    $label = str_replace('_', ' ', $status);
    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

function dashboard_nav(): array
{
    $user = Auth::user();
    if (!$user) {
        return [];
    }
    $items = [];
    if (Auth::can('admin.access')) {
        $items[] = ['label' => 'Admin', 'items' => array_values(array_filter([
            Auth::can('analytics.view') ? ['overview', 'Overview', '/admin', 'home'] : null,
            Auth::can('analytics.view') ? ['analytics', 'Analytics', '/admin/analytics', 'chart'] : null,
            Auth::can('users.manage') ? ['users', 'Users', '/admin/users', 'users'] : null,
            Auth::can('applications.review') ? ['applications', 'Applications', '/admin/applications', 'cert'] : null,
            Auth::can('courses.review') || Auth::can('courses.manage') ? ['courses', 'Courses', '/admin/courses', 'book'] : null,
            Auth::can('categories.manage') ? ['categories', 'Categories', '/admin/categories', 'tag'] : null,
            Auth::can('orders.manage') ? ['orders', 'Orders', '/admin/orders', 'cart'] : null,
            Auth::can('payouts.manage') ? ['payouts', 'Payouts', '/admin/payouts', 'dollar'] : null,
            Auth::can('content.manage') ? ['pages', 'Pages', '/admin/pages', 'page'] : null,
            Auth::can('content.manage') ? ['appearance', 'Appearance', '/admin/appearance', 'image'] : null,
            Auth::can('newsletter.send') ? ['newsletter', 'Newsletter', '/admin/newsletter', 'mail'] : null,
            Auth::can('media.manage') ? ['media', 'Media', '/admin/media', 'folder'] : null,
            ['inbox', 'Inbox', '/admin/inbox', 'message'],
            Auth::can('settings.manage') ? ['settings', 'Settings', '/admin/settings', 'settings'] : null,
            Auth::can('roles.manage') ? ['permissions', 'Permissions', '/admin/permissions', 'shield'] : null,
            Auth::can('backups.run') ? ['backups', 'Backups', '/admin/backups', 'backup'] : null,
            Auth::can('updates.apply') ? ['updates', 'Updates', '/admin/updates', 'download'] : null,
        ]))];
    }
    if (Auth::can('studio.access')) {
        $items[] = ['label' => 'Studio', 'items' => [
            ['studio', 'Studio', '/studio', 'home'],
            ['studio-courses', 'Courses', '/studio/courses', 'book'],
            ['studio-live', 'Live classes', '/studio/live', 'live'],
            ['studio-students', 'Students', '/studio/students', 'users'],
            ['studio-quizzes', 'Quizzes', '/studio/quizzes', 'quiz'],
            ['studio-earnings', 'Earnings', '/studio/earnings', 'dollar'],
            ['studio-analytics', 'Analytics', '/studio/analytics', 'chart'],
        ]];
    }
    $items[] = ['label' => 'Learning', 'items' => [
        ['learn', 'My learning', '/learn', 'play'],
        ['history', 'Watch history', '/learn/history', 'clock'],
        ['live', 'Live classes', '/live', 'live'],
        ['wishlist', 'Wishlist', '/wishlist', 'heart'],
        ['orders', 'Orders', '/orders', 'cart'],
        ['certificates', 'Certificates', '/certificates', 'cert'],
        ['messages', 'Messages', '/messages', 'message'],
        ['notifications', 'Notifications', '/notifications', 'bell'],
        ['account', 'Profile', '/account', 'settings'],
    ]];
    if (($user['role'] ?? '') === 'student') {
        $items[] = ['label' => 'Teach', 'items' => [
            ['teach', 'Become an instructor', '/teach', 'cert'],
        ]];
    }
    return $items;
}

function selected($current, string $value): string
{
    return (string) $current === $value ? 'selected' : '';
}

function checked($value): string
{
    return $value ? 'checked' : '';
}

function json_flags(): int
{
    return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
}

function video_embed(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('~(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return '<div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/' . e($m[1]) . '" title="Lesson video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return '<div class="video-frame"><iframe src="https://player.vimeo.com/video/' . e($m[1]) . '" title="Lesson video" allowfullscreen></iframe></div>';
    }
    if (preg_match('~\.(mp4|webm)(\?|$)~i', $url)) {
        return '<video class="video-file" controls preload="metadata" src="' . e($url) . '"></video>';
    }
    return '<p><a class="text-link" href="' . e($url) . '" rel="noopener" target="_blank">Open the recording</a></p>';
}
