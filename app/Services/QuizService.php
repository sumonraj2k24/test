<?php

declare(strict_types=1);

namespace App\Services;

final class QuizService
{
    public static function grade(array $questions, array $answers): array
    {
        $earned = 0;
        $possible = 0;
        $detail = [];
        foreach ($questions as $question) {
            $points = (int) $question['points'];
            $possible += $points;
            $given = $answers[(string) $question['id']] ?? $answers[$question['id']] ?? null;
            $correct = json_decode((string) $question['correct_json'], true) ?: [];
            $ok = self::isCorrect((string) $question['type'], $given, $correct);
            if ($ok) {
                $earned += $points;
            }
            $detail[] = [
                'id' => (int) $question['id'],
                'correct' => $ok,
                'expected' => $correct,
                'given' => $given,
            ];
        }
        $percent = $possible > 0 ? (int) round($earned / $possible * 100) : 0;
        return [
            'score_percent' => $percent,
            'points_earned' => $earned,
            'points_possible' => $possible,
            'detail' => $detail,
        ];
    }

    private static function isCorrect(string $type, mixed $given, array $correct): bool
    {
        if ($type === 'text') {
            $answer = strtolower(trim((string) $given));
            foreach ($correct as $accepted) {
                if ($answer !== '' && $answer === strtolower(trim((string) $accepted))) {
                    return true;
                }
            }
            return false;
        }
        $givenIds = is_array($given) ? array_map('strval', $given) : ($given === null || $given === '' ? [] : [(string) $given]);
        $expected = array_map('strval', $correct);
        sort($givenIds);
        sort($expected);
        return $givenIds === $expected && $expected !== [];
    }
}
