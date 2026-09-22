<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class RoleMiddleware
{
    public function handle(Request $request, callable $next, array $args = []): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user['role'], $args, true)) {
            abort(403);
        }
        $next($request);
    }
}
