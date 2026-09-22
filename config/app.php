<?php

declare(strict_types=1);

return [
    'name' => 'Meridian',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (getenv('APP_DEBUG') ?: '1') === '1',
    'version' => '1.1.0',
    'database' => dirname(__DIR__) . '/storage/database.sqlite',
    'timezone' => 'UTC',
];
