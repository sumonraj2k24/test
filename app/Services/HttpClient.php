<?php

declare(strict_types=1);

namespace App\Services;

final class HttpClient
{
    public static function request(string $method, string $url, ?string $body = null, array $headers = [], int $timeout = 20): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'status' => 0, 'body' => '', 'json' => null, 'error' => $error];
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $payload = substr($raw, $headerSize);
        $json = json_decode($payload, true);
        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $payload,
            'json' => is_array($json) ? $json : null,
            'error' => null,
        ];
    }

    public static function form(string $method, string $url, array $fields, array $headers = []): array
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return self::request($method, $url, http_build_query($fields), $headers);
    }

    public static function json(string $method, string $url, array $payload, array $headers = []): array
    {
        $headers[] = 'Content-Type: application/json';
        return self::request($method, $url, json_encode($payload, json_flags()), $headers);
    }

    public static function get(string $url, array $headers = []): array
    {
        return self::request('GET', $url, null, $headers);
    }
}
