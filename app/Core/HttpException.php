<?php

declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : (match ($status) {
            403 => 'You do not have permission to view this page.',
            404 => 'That page could not be found.',
            419 => 'Your session expired. Please try again.',
            default => 'Something went wrong.',
        }));
    }
}
