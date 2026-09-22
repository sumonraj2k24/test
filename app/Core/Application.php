<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\CartService;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Throwable;

final class Application
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function run(): void
    {
        $this->boot();
        $request = Request::capture();
        try {
            if ($request->isMutating() && !str_starts_with($request->path(), '/webhooks/')) {
                Csrf::verify();
            }
            $router = new Router();
            require $this->basePath . '/routes/web.php';
            $router->dispatch($request);
        } catch (HttpException $e) {
            $this->renderHttp($e, $request);
        } catch (Throwable $e) {
            $this->renderException($e, $request);
        }
    }

    private function boot(): void
    {
        date_default_timezone_set((string) config('timezone', 'UTC'));
        Session::start();
        Database::pdo();
        SettingsService::warm();
        $user = Auth::user();
        View::share('currentUser', $user);
        View::share('siteName', (string) setting('site_name', 'Meridian'));
        View::share('tagline', (string) setting('tagline', 'A studio for serious learning.'));
        View::share('cartCount', CartService::count());
        View::share('unreadCount', $user ? NotificationService::unreadCount((int) $user['id']) : 0);
        View::share('footerPages', Database::select(
            "SELECT title, slug FROM pages WHERE status = 'published' AND show_in_footer = 1 ORDER BY position, title"
        ));
        View::share('navPages', Database::select(
            "SELECT title, slug FROM pages WHERE status = 'published' AND show_in_nav = 1 ORDER BY position, title"
        ));
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');
    }

    private function renderHttp(HttpException $e, Request $request): void
    {
        http_response_code($e->status);
        if ($request->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()], json_flags());
            return;
        }
        $view = match ($e->status) {
            403 => 'errors/403',
            419 => 'errors/419',
            default => 'errors/404',
        };
        if (!is_file(BASE_PATH . '/resources/views/' . $view . '.php')) {
            $view = 'errors/404';
        }
        view($view, [
            'title' => $e->status . ' · ' . setting('site_name', 'Meridian'),
            'message' => $e->getMessage(),
            'status' => $e->status,
        ], 'layouts/app');
    }

    private function renderException(Throwable $e, Request $request): void
    {
        http_response_code(500);
        $log = storage_path('logs');
        if (!is_dir($log)) {
            mkdir($log, 0775, true);
        }
        file_put_contents(
            $log . '/app.log',
            '[' . now() . '] ' . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n",
            FILE_APPEND
        );
        if ($request->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => config('debug') ? $e->getMessage() : 'Server error'], json_flags());
            return;
        }
        if (config('debug')) {
            echo '<pre style="padding:24px;font:14px/1.5 ui-monospace,monospace;white-space:pre-wrap">';
            echo e($e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString());
            echo '</pre>';
            return;
        }
        echo 'Something went wrong. The studio has been notified.';
    }
}
