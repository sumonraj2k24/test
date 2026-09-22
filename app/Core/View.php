<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $data = array_merge(self::$shared, $data);
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    public static function partial(string $view, array $data = []): void
    {
        echo self::capture($view, array_merge(self::$shared, $data));
    }

    private static function capture(string $view, array $data): string
    {
        $path = BASE_PATH . '/resources/views/' . $view . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
