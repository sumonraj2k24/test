<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class GuestMiddleware
{
    public function handle(Request $request, callable $next, array $args = []): void
    {
        if (Auth::check()) {
            redirect(match (Auth::user()['role'] ?? 'student') {
                'admin' => '/admin',
                'instructor' => '/studio',
                default => '/learn',
            });
        }
        $next($request);
    }
}
