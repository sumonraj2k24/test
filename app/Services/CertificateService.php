<?php

declare(strict_types=1);

namespace App\Services;

final class CertificateService
{
    public static function code(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $chunk = static function () use ($alphabet): string {
            $out = '';
            for ($i = 0; $i < 4; $i++) {
                $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            return $out;
        };
        return 'MRD-' . $chunk() . '-' . $chunk();
    }
}
