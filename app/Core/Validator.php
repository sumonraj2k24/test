<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function check(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $text = is_scalar($value) ? trim((string) $value) : '';
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $message = self::fail($name, $arg, $field, $text, $data);
                if ($message !== null) {
                    $errors[$field] = $message;
                    break;
                }
            }
        }
        return $errors;
    }

    private static function fail(string $name, ?string $arg, string $field, string $text, array $data): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));
        return match ($name) {
            'required' => $text === '' ? $label . ' is required.' : null,
            'email' => ($text !== '' && !filter_var($text, FILTER_VALIDATE_EMAIL)) ? 'Enter a valid email address.' : null,
            'min' => ($text !== '' && strlen($text) < (int) $arg) ? $label . ' must be at least ' . $arg . ' characters.' : null,
            'max' => ($text !== '' && strlen($text) > (int) $arg) ? $label . ' must be at most ' . $arg . ' characters.' : null,
            'numeric' => ($text !== '' && !is_numeric($text)) ? $label . ' must be a number.' : null,
            'integer' => ($text !== '' && !preg_match('/^-?\d+$/', $text)) ? $label . ' must be a whole number.' : null,
            'confirmed' => $text !== trim((string) ($data[$field . '_confirmation'] ?? '')) ? $label . ' confirmation does not match.' : null,
            'in' => ($text !== '' && !in_array($text, explode(',', (string) $arg), true)) ? $label . ' is not a valid choice.' : null,
            'url' => ($text !== '' && !preg_match('#^https?://#i', $text)) ? $label . ' must be a full URL.' : null,
            default => null,
        };
    }
}
