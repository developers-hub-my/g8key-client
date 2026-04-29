<?php

namespace G8Key\Client\Services;

final class Fingerprint
{
    public static function default(): string
    {
        return hash('sha256', (string) config('app.url').gethostname());
    }

    public static function resolve(?string $override = null): string
    {
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $configured = config('g8key-client.fingerprint');

        if (is_callable($configured)) {
            return (string) $configured();
        }

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return self::default();
    }
}
