<?php

declare(strict_types=1);

namespace App\Services;

final class ZoomService
{
    public static function configured(): bool
    {
        return setting('zoom_account_id', '') !== ''
            && setting('zoom_client_id', '') !== ''
            && setting('zoom_client_secret', '') !== '';
    }

    public static function createMeeting(string $topic, string $startsAt, int $duration): array
    {
        if (!self::configured()) {
            return [
                'ok' => false,
                'error' => 'Zoom is not configured. The in-app room still works, or paste a join link.',
            ];
        }
        $token = self::token();
        if (!$token) {
            return ['ok' => false, 'error' => 'Zoom refused the server-to-server credentials.'];
        }
        $start = strtotime($startsAt . ' UTC') ?: time();
        $res = HttpClient::json('POST', 'https://api.zoom.us/v2/users/me/meetings', [
            'topic' => $topic,
            'type' => 2,
            'start_time' => gmdate('Y-m-d\TH:i:s\Z', $start),
            'duration' => max(15, $duration),
            'timezone' => 'UTC',
            'settings' => [
                'waiting_room' => true,
                'join_before_host' => false,
                'approval_type' => 2,
            ],
        ], ['Authorization: Bearer ' . $token]);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => 'Zoom did not create the meeting (' . $res['status'] . ').'];
        }
        return [
            'ok' => true,
            'meeting_id' => (string) ($res['json']['id'] ?? ''),
            'join_url' => (string) ($res['json']['join_url'] ?? ''),
            'start_url' => (string) ($res['json']['start_url'] ?? ''),
        ];
    }

    private static function token(): ?string
    {
        $res = HttpClient::form(
            'POST',
            'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . rawurlencode((string) setting('zoom_account_id')),
            [],
            ['Authorization: Basic ' . base64_encode(setting('zoom_client_id') . ':' . setting('zoom_client_secret'))]
        );
        return $res['json']['access_token'] ?? null;
    }
}
