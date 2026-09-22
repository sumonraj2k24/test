<?php

declare(strict_types=1);

namespace App\Support;

final class Markdown
{
    public static function render(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace_callback('/```(.*?)```/s', static function (array $m): string {
            return '<pre><code>' . trim($m[1]) . '</code></pre>';
        }, $escaped) ?? $escaped;

        $escaped = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $escaped) ?? $escaped;
        $escaped = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $escaped) ?? $escaped;
        $escaped = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
        $escaped = preg_replace_callback(
            '/\[(.+?)\]\((https?:\/\/[^\s)]+|\/[^\s)]*)\)/',
            static function (array $m): string {
                return '<a href="' . $m[2] . '" rel="noopener noreferrer">' . $m[1] . '</a>';
            },
            $escaped
        ) ?? $escaped;
        $escaped = preg_replace('/^&gt; (.+)$/m', '<blockquote><p>$1</p></blockquote>', $escaped) ?? $escaped;

        $escaped = preg_replace_callback('/(?:^(?:- |\d+\. ).+(?:\n|$))+/m', static function (array $m): string {
            $lines = preg_split('/\n/', trim($m[0])) ?: [];
            $ordered = (bool) preg_match('/^\d+\. /', $lines[0] ?? '');
            $items = '';
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }
                $line = preg_replace('/^(?:- |\d+\. )/', '', $line) ?? $line;
                $items .= '<li>' . $line . '</li>';
            }
            $tag = $ordered ? 'ol' : 'ul';
            return '<' . $tag . '>' . $items . '</' . $tag . '>';
        }, $escaped) ?? $escaped;

        $parts = preg_split("/\n{2,}/", $escaped) ?: [];
        $html = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^<(h2|h3|ul|ol|pre|blockquote)/', $part)) {
                $html .= $part;
            } else {
                $html .= '<p>' . nl2br($part, false) . '</p>';
            }
        }
        return $html;
    }
}
