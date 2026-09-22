<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }
        return $token;
    }

    public static function verify(): void
    {
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $known = Session::get('_csrf', '');
        if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
            throw new HttpException(419, 'Your session expired. Refresh the page and try again.');
        }
    }
}
