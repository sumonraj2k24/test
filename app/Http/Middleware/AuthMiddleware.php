<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next, array $args = []): void
    {
        if (!Auth::check()) {
            Session::put('intended', $request->path());
            flash('error', 'Sign in to continue.');
            redirect('/login');
        }
        $next($request);
    }
}
