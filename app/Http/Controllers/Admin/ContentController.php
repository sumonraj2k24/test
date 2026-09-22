<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\MailService;
use App\Services\SettingsService;
use App\Services\StorageService;

final class ContentController
{
    public function pages(Request $request, array $params = []): void
    {
        view('admin/pages', [
            'title' => 'Pages',
            'active' => 'pages',
            'pages' => Database::select('SELECT * FROM pages ORDER BY position, title'),
        ], 'layouts/dashboard');
    }

    public function editPage(Request $request, array $params): void
    {
        $page = ($params['id'] ?? 'new') === 'new'
            ? null
            : Database::first('SELECT * FROM pages WHERE id = ?', [(int) $params['id']]);
        if (($params['id'] ?? '') !== 'new' && !$page) {
            abort(404);
        }
        view('admin/page-form', [
            'title' => $page ? 'Edit page' : 'New page',
            'active' => 'pages',
            'page' => $page,
        ], 'layouts/dashboard');
    }

    public function savePage(Request $request, array $params = []): void
    {
        $data = validate([
            'title' => 'required|min:2|max:120',
            'content' => 'required|min:2',
        ]);
        $id = $request->int('id');
        $payload = [
            'title' => $data['title'],
            'slug' => unique_slug('pages', $request->string('slug') ?: $data['title'], $id ?: null),
            'content' => $data['content'],
            'seo_title' => $request->string('seo_title') ?: null,
            'seo_description' => $request->string('seo_description') ?: null,
            'status' => $request->string('status') === 'draft' ? 'draft' : 'published',
            'show_in_footer' => $request->input('show_in_footer') ? 1 : 0,
            'show_in_nav' => $request->input('show_in_nav') ? 1 : 0,
            'position' => $request->int('position'),
            'updated_at' => now(),
        ];
        if ($id) {
            Database::update('pages', $payload, 'id = ?', [$id]);
        } else {
            Database::insert('pages', $payload);
        }
        flash('success', 'Page saved.');
        redirect('/admin/pages');
    }

    public function appearance(Request $request, array $params = []): void
    {
        view('admin/appearance', [
            'title' => 'Appearance',
            'active' => 'appearance',
            'navLinks' => json_decode((string) setting('nav_links', '[]'), true) ?: [],
        ], 'layouts/dashboard');
    }

    public function saveAppearance(Request $request, array $params = []): void
    {
        $links = [];
        $labels = (array) $request->input('nav_label', []);
        $hrefs = (array) $request->input('nav_href', []);
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $href = trim((string) ($hrefs[$i] ?? ''));
            if ($label !== '' && str_starts_with($href, '/')) {
                $links[] = ['label' => $label, 'href' => $href];
            }
        }
        SettingsService::setMany([
            'site_name' => $request->string('site_name', 'Meridian'),
            'tagline' => $request->string('tagline'),
            'announcement' => $request->string('announcement'),
            'hero_kicker' => $request->string('hero_kicker'),
            'hero_title' => $request->string('hero_title'),
            'hero_lede' => $request->string('hero_lede'),
            'footer_blurb' => $request->string('footer_blurb'),
            'primary_color' => safe_hex($request->string('primary_color'), '#1f4f45'),
            'accent_color' => safe_hex($request->string('accent_color'), '#b5812d'),
            'seo_title' => $request->string('seo_title'),
            'seo_description' => $request->string('seo_description'),
            'nav_links' => json_encode($links, json_flags()),
        ]);
        audit('appearance.update', 'settings');
        flash('success', 'Appearance saved. Reload the public site to see it.');
        redirect('/admin/appearance');
    }

    public function newsletter(Request $request, array $params = []): void
    {
        view('admin/newsletter', [
            'title' => 'Newsletter',
            'active' => 'newsletter',
            'subscribers' => Database::select('SELECT * FROM subscribers ORDER BY datetime(created_at) DESC LIMIT 100'),
            'campaigns' => Database::select('SELECT * FROM campaigns ORDER BY datetime(created_at) DESC LIMIT 20'),
            'mailLog' => MailService::logged(),
            'count' => (int) Database::value("SELECT COUNT(*) FROM subscribers WHERE status = 'active'"),
        ], 'layouts/dashboard');
    }

    public function sendNewsletter(Request $request, array $params = []): void
    {
        $data = validate([
            'subject' => 'required|min:3|max:160',
            'body' => 'required|min:8',
        ]);
        $subscribers = Database::select("SELECT email, name FROM subscribers WHERE status = 'active'");
        $html = '<div style="font-family:Georgia,serif;max-width:560px;margin:0 auto;color:#1b1814"><h1 style="font-weight:500">' . e($data['subject']) . '</h1>' . md($data['body']) . '</div>';
        $sent = 0;
        foreach ($subscribers as $subscriber) {
            if (MailService::send($subscriber['email'], $data['subject'], $html)) {
                $sent++;
            }
        }
        Database::insert('campaigns', [
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipients_count' => $sent,
            'sent_at' => now(),
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
        audit('newsletter.send', $data['subject'], ['recipients' => $sent]);
        flash('success', 'Newsletter sent to ' . $sent . ' subscriber' . ($sent === 1 ? '' : 's') . '. Copies are in the mail log.');
        redirect('/admin/newsletter');
    }

    public function media(Request $request, array $params = []): void
    {
        view('admin/media', [
            'title' => 'Media library',
            'active' => 'media',
            'files' => Database::select('SELECT m.*, u.name AS owner FROM media m LEFT JOIN users u ON u.id = m.user_id ORDER BY datetime(m.created_at) DESC LIMIT 80'),
        ], 'layouts/dashboard');
    }

    public function upload(Request $request, array $params = []): void
    {
        $file = $request->file('file');
        if (!$file) {
            flash('error', 'Choose a file.');
            redirect('/admin/media');
        }
        try {
            StorageService::store($file, 'library');
            flash('success', 'File stored on the ' . setting('storage_disk', 'local') . ' disk.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/media');
    }

    public function deleteMedia(Request $request, array $params): void
    {
        $file = Database::first('SELECT * FROM media WHERE id = ?', [(int) $params['id']]);
        if ($file && $file['disk'] === 'local' && str_starts_with((string) $file['path'], '/uploads/')) {
            $full = public_path(ltrim($file['path'], '/'));
            if (is_file($full)) {
                unlink($full);
            }
        }
        Database::delete('media', 'id = ?', [(int) $params['id']]);
        flash('success', 'Media record removed.');
        redirect('/admin/media');
    }

    public function inbox(Request $request, array $params = []): void
    {
        if ($id = $request->int('read')) {
            Database::update('contact_messages', ['read_at' => now()], 'id = ?', [$id]);
        }
        view('admin/inbox', [
            'title' => 'Inbox',
            'active' => 'inbox',
            'messages' => Database::select('SELECT * FROM contact_messages ORDER BY datetime(created_at) DESC'),
        ], 'layouts/dashboard');
    }
}
