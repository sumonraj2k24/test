<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Services\BackupService;

final class CronController
{
    public function run(Request $request, array $params = []): void
    {
        $token = (string) $request->query('token', '');
        $known = (string) setting('cron_token', '');
        if ($known === '' || !hash_equals($known, $token)) {
            abort(403, 'Cron token missing or wrong.');
        }
        header('Content-Type: application/json');
        $result = ['backup' => false];
        if (BackupService::due()) {
            $backup = BackupService::create(null);
            $result['backup'] = $backup['filename'];
        }
        echo json_encode($result, json_flags());
    }
}
