<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class StorageService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'audio/mpeg' => 'mp3',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
    ];

    public static function store(array $file, string $directory = 'library'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('The upload did not complete.');
        }
        $max = ((int) setting('max_upload_mb', '20')) * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $max) {
            throw new \RuntimeException('That file is larger than the upload limit.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('That file type is not allowed.');
        }
        $name = gmdate('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . self::ALLOWED[$mime];
        $key = trim($directory, '/') . '/' . $name;
        $disk = (string) setting('storage_disk', 'local');
        if ($disk === 's3' && self::s3Ready()) {
            $path = self::putS3($file['tmp_name'], $key, $mime);
        } else {
            $dir = public_path('uploads/' . trim($directory, '/'));
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Could not create the upload directory.');
            }
            if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
                throw new \RuntimeException('Could not store the file.');
            }
            $path = '/uploads/' . $key;
            $disk = 'local';
        }
        $id = Database::insert('media', [
            'user_id' => Auth::id(),
            'filename' => (string) ($file['name'] ?? $name),
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => (int) ($file['size'] ?? 0),
            'disk' => $disk,
            'created_at' => now(),
        ]);
        return ['id' => $id, 'path' => $path, 'mime' => $mime, 'disk' => $disk];
    }

    public static function s3Ready(): bool
    {
        return setting('s3_bucket', '') !== '' && setting('s3_key', '') !== '' && setting('s3_secret', '') !== '';
    }

    public static function putS3(string $source, string $key, string $mime): string
    {
        $bucket = (string) setting('s3_bucket');
        $region = (string) setting('s3_region', 'us-east-1');
        $access = (string) setting('s3_key');
        $secret = (string) setting('s3_secret');
        $endpoint = rtrim((string) setting('s3_endpoint', ''), '/');
        $body = (string) file_get_contents($source);
        $pathStyle = $endpoint !== '';
        $host = $pathStyle ? (string) parse_url($endpoint, PHP_URL_HOST) : ($bucket . '.s3.' . $region . '.amazonaws.com');
        $canonicalUri = $pathStyle ? '/' . $bucket . '/' . str_replace('%2F', '/', rawurlencode($key)) : '/' . str_replace('%2F', '/', rawurlencode($key));
        $url = ($pathStyle ? $endpoint : 'https://' . $host) . $canonicalUri;
        $amzDate = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $payloadHash = hash('sha256', $body);
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
        $canonical = "PUT\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $scope = "{$date}/{$region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonical);
        $signingKey = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 's3', hash_hmac('sha256', $region, hash_hmac('sha256', $date, 'AWS4' . $secret, true), true), true), true);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorization = "AWS4-HMAC-SHA256 Credential={$access}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        $res = HttpClient::request('PUT', $url, $body, [
            'Host: ' . $host,
            'Content-Type: ' . $mime,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: ' . $amzDate,
            'Authorization: ' . $authorization,
        ]);
        if (!$res['ok']) {
            throw new \RuntimeException('S3 upload failed (' . $res['status'] . ').');
        }
        return $url;
    }
}
