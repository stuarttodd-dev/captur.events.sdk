<?php

declare(strict_types=1);

namespace Captur;

final class Webhook
{
    /**
     * Verify an HMAC-SHA256 webhook signature over the exact raw request body.
     */
    public static function verify(string $payload, ?string $signature, string $secret): bool
    {
        if ($signature === null || $signature === '' || $payload === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
