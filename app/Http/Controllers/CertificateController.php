<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;

final class CertificateController
{
    public function show(Request $request, array $params): void
    {
        $row = $this->find($params['code']);
        if (!$row) {
            abort(404, 'No certificate matches that code.');
        }
        view('certificate/show', [
            'title' => 'Certificate · ' . $row['student_name'],
            'certificate' => $row,
        ], 'layouts/print');
    }

    public function verify(Request $request, array $params): void
    {
        $row = $this->find($params['code']);
        view('certificate/verify', [
            'title' => 'Verify a certificate',
            'certificate' => $row,
            'code' => $params['code'],
        ]);
    }

    private function find(string $code): ?array
    {
        return Database::first(
            'SELECT e.*, u.name AS student_name, c.title AS course_title, c.slug AS course_slug, i.name AS instructor_name
             FROM enrollments e
             INNER JOIN users u ON u.id = e.user_id
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN users i ON i.id = c.instructor_id
             WHERE e.certificate_code = ?',
            [strtoupper($code)]
        );
    }
}
