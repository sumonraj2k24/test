<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\OAuthService;

final class AuthController
{
    public function showLogin(Request $request, array $params = []): void
    {
        view('auth/login', [
            'title' => 'Sign in · ' . setting('site_name', 'Meridian'),
            'next' => (string) $request->query('next', ''),
        ], 'layouts/auth');
    }

    public function login(Request $request, array $params = []): void
    {
        $attempts = (int) Session::get('login_attempts', 0);
        if ($attempts >= 8) {
            flash('error', 'Too many attempts. Wait a moment and try again.');
            back('/login');
        }
        $data = validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (!Auth::attempt($data['email'], $data['password'])) {
            Session::put('login_attempts', $attempts + 1);
            flash('error', 'Those credentials do not match a studio account.');
            flash('old', ['email' => $data['email']]);
            back('/login');
        }
        Session::forget('login_attempts');
        $this->sendHome((string) $request->input('next', ''));
    }

    public function showRegister(Request $request, array $params = []): void
    {
        view('auth/register', [
            'title' => 'Create an account · ' . setting('site_name', 'Meridian'),
        ], 'layouts/auth');
    }

    public function register(Request $request, array $params = []): void
    {
        $data = validate([
            'name' => 'required|min:2|max:80',
            'email' => 'required|email|max:160',
            'password' => 'required|min:8|confirmed',
        ]);
        $email = strtolower($data['email']);
        if (Database::first('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', 'An account with that email already exists.');
            flash('old', ['name' => $data['name'], 'email' => $email]);
            back('/register');
        }
        $id = Database::insert('users', [
            'name' => $data['name'],
            'email' => $email,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = Database::first('SELECT * FROM users WHERE id = ?', [$id]);
        Auth::login($user);
        flash('success', 'Welcome. Your shelf is empty and the catalog is not.');
        redirect('/learn');
    }

    public function logout(Request $request, array $params = []): void
    {
        Auth::logout();
        redirect('/');
    }

    public function googleRedirect(Request $request, array $params = []): void
    {
        if (!OAuthService::configured()) {
            flash('error', 'Google sign-in is not configured. Add a client id and secret in admin settings.');
            redirect('/login');
        }
        redirect(OAuthService::authorizationUrl());
    }

    public function googleCallback(Request $request, array $params = []): void
    {
        $state = (string) $request->query('state', '');
        if ($state === '' || !hash_equals((string) Session::get('oauth_state', ''), $state)) {
            flash('error', 'Google sign-in could not be verified. Try again.');
            redirect('/login');
        }
        Session::forget('oauth_state');
        $code = (string) $request->query('code', '');
        if ($code === '') {
            flash('error', 'Google did not return an authorization code.');
            redirect('/login');
        }
        try {
            $profile = OAuthService::profile($code);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/login');
        }
        $email = strtolower((string) $profile['email']);
        $user = Database::first('SELECT * FROM users WHERE email = ? OR google_id = ?', [$email, (string) ($profile['sub'] ?? '')]);
        if ($user && $user['status'] === 'suspended') {
            flash('error', 'This account is suspended.');
            redirect('/login');
        }
        if (!$user) {
            $id = Database::insert('users', [
                'name' => $profile['name'] ?? 'Google learner',
                'email' => $email,
                'password' => null,
                'role' => 'student',
                'google_id' => $profile['sub'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = Database::first('SELECT * FROM users WHERE id = ?', [$id]);
        } else {
            Database::update('users', [
                'google_id' => $profile['sub'] ?? $user['google_id'],
                'updated_at' => now(),
            ], 'id = ?', [(int) $user['id']]);
            $user = Database::first('SELECT * FROM users WHERE id = ?', [(int) $user['id']]);
        }
        Auth::login($user);
        flash('success', 'Signed in with Google.');
        $this->sendHome('');
    }

    private function sendHome(string $next): void
    {
        if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
            redirect($next);
        }
        $intended = (string) Session::get('intended', '');
        Session::forget('intended');
        if ($intended !== '' && str_starts_with($intended, '/')) {
            redirect($intended);
        }
        redirect(match (Auth::user()['role'] ?? 'student') {
            'admin' => '/admin',
            'instructor' => '/studio',
            default => '/learn',
        });
    }
}
