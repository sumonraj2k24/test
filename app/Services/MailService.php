<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $dir = storage_path('mail');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safeSubject = preg_replace('/[^a-z0-9]+/i', '-', strtolower($subject)) ?: 'mail';
        $file = $dir . '/' . gmdate('Ymd-His') . '-' . substr($safeSubject, 0, 40) . '-' . bin2hex(random_bytes(2)) . '.html';
        file_put_contents($file, self::document($to, $subject, $html));
        if (setting('smtp_host', '') === '') {
            return true;
        }
        try {
            return self::smtp($to, $subject, $html);
        } catch (\Throwable $e) {
            $log = storage_path('logs');
            if (!is_dir($log)) {
                mkdir($log, 0775, true);
            }
            file_put_contents($log . '/mail.log', '[' . now() . '] ' . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    public static function logged(): array
    {
        $files = glob(storage_path('mail/*.html')) ?: [];
        rsort($files);
        return array_map(static function (string $file): array {
            return [
                'name' => basename($file),
                'size' => filesize($file) ?: 0,
                'mtime' => filemtime($file) ?: time(),
            ];
        }, array_slice($files, 0, 30));
    }

    private static function document(string $to, string $subject, string $html): string
    {
        return '<!doctype html><meta charset="utf-8"><title>' . e($subject) . '</title>'
            . '<p style="font:13px sans-serif;color:#746d62">To ' . e($to) . ' · ' . e($subject) . '</p>'
            . $html;
    }

    private static function smtp(string $to, string $subject, string $html): bool
    {
        $host = (string) setting('smtp_host');
        $port = (int) setting('smtp_port', '587');
        $encryption = (string) setting('smtp_encryption', 'tls');
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = stream_socket_client($remote, $errno, $errstr, 15);
        if (!$fp) {
            throw new \RuntimeException($errstr ?: 'SMTP connection failed');
        }
        stream_set_timeout($fp, 15);
        self::expect($fp, [220]);
        self::command($fp, 'EHLO meridian.local', [250]);
        if ($encryption === 'tls') {
            self::command($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('STARTTLS failed.');
            }
            self::command($fp, 'EHLO meridian.local', [250]);
        }
        $user = (string) setting('smtp_username', '');
        if ($user !== '') {
            self::command($fp, 'AUTH LOGIN', [334]);
            self::command($fp, base64_encode($user), [334]);
            self::command($fp, base64_encode((string) setting('smtp_password', '')), [235]);
        }
        $from = (string) setting('smtp_from', 'studio@meridian.test');
        $fromName = (string) setting('smtp_from_name', setting('site_name', 'Meridian'));
        self::command($fp, 'MAIL FROM:<' . $from . '>', [250]);
        self::command($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        self::command($fp, 'DATA', [354]);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = 'From: ' . self::headerName($fromName) . ' <' . $from . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . $encodedSubject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $html . "\r\n.\r\n";
        fwrite($fp, $message);
        self::expect($fp, [250]);
        self::command($fp, 'QUIT', [221]);
        fclose($fp);
        return true;
    }

    private static function headerName(string $name): string
    {
        return '"' . addcslashes($name, '"\\') . '"';
    }

    private static function command($fp, string $command, array $ok): void
    {
        fwrite($fp, $command . "\r\n");
        self::expect($fp, $ok);
    }

    private static function expect($fp, array $ok): void
    {
        $buffer = '';
        while (($line = fgets($fp, 515)) !== false) {
            $buffer .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($buffer, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new \RuntimeException('SMTP unexpected reply: ' . trim($buffer));
        }
    }
}
