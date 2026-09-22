<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class OAuthService
{
    public static function configured(): bool
    {
        return setting('google_client_id', '') !== '' && setting('google_client_secret', '') !== '';
    }

    public static function authorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Session::put('oauth_state', $state);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => setting('google_client_id'),
            'redirect_uri' => url('/auth/google/callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    public static function profile(string $code): array
    {
        $token = HttpClient::form('POST', 'https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => setting('google_client_id'),
            'client_secret' => setting('google_client_secret'),
            'redirect_uri' => url('/auth/google/callback'),
            'grant_type' => 'authorization_code',
        ]);
        $access = $token['json']['access_token'] ?? null;
        if (!$access) {
            throw new \RuntimeException('Google did not return an access token.');
        }
        $profile = HttpClient::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'Authorization: Bearer ' . $access,
        ]);
        if (empty($profile['json']['email'])) {
            throw new \RuntimeException('Google did not share an email address.');
        }
        return $profile['json'];
    }
}
