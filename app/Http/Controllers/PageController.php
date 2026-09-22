<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\MailService;

final class PageController
{
    public function show(Request $request, array $params): void
    {
        $page = Database::first("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$params['slug']]);
        if (!$page) {
            abort(404);
        }
        view('pages/show', [
            'title' => ($page['seo_title'] ?: $page['title']) . ' · ' . setting('site_name', 'Meridian'),
            'seoDescription' => $page['seo_description'],
            'page' => $page,
        ]);
    }

    public function contact(Request $request, array $params = []): void
    {
        view('pages/contact', [
            'title' => 'Contact · ' . setting('site_name', 'Meridian'),
            'seoDescription' => 'Write to the studio.',
        ]);
    }

    public function sendContact(Request $request, array $params = []): void
    {
        $data = validate([
            'name' => 'required|min:2|max:80',
            'email' => 'required|email',
            'subject' => 'required|min:3|max:140',
            'body' => 'required|min:10|max:5000',
        ]);
        Database::insert('contact_messages', [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'created_at' => now(),
        ]);
        $to = (string) setting('support_email', 'studio@meridian.test');
        MailService::send($to, 'Contact: ' . $data['subject'], '<p>' . e($data['name']) . ' · ' . e($data['email']) . '</p><p>' . nl2br(e($data['body'])) . '</p>');
        flash('success', 'Message received. It is in the studio inbox.');
        redirect('/contact');
    }

    public function subscribe(Request $request, array $params = []): void
    {
        $data = validate(['email' => 'required|email']);
        Database::execute(
            'INSERT INTO subscribers (email, name, status, created_at) VALUES (?, ?, ?, ?)
             ON CONFLICT(email) DO UPDATE SET status = "active"',
            [strtolower($data['email']), Auth::user()['name'] ?? null, 'active', now()]
        );
        flash('success', 'You are on the list.');
        back('/');
    }
}
