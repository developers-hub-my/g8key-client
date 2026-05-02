<?php

namespace G8Key\Client\Services;

/**
 * Builds a fingerprint string compatible with the G8Key server's
 * App\Services\G8Key\FingerprintGenerator: sorted, lower-cased,
 * trimmed attributes encoded as JSON, sha256-hashed, prefixed `sha256:`.
 */
final class Fingerprint
{
    /**
     * @return non-empty-string
     */
    public static function default(): string
    {
        return self::compute([
            'app_url' => (string) config('app.url'),
            'hostname' => gethostname() ?: 'unknown',
        ]);
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return non-empty-string
     */
    public static function compute(array $attributes): string
    {
        $normalised = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalised[strtolower(trim((string) $key))] = is_string($value)
                ? strtolower(trim($value))
                : $value;
        }

        ksort($normalised);

        $payload = json_encode($normalised, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return 'sha256:'.hash('sha256', $payload);
    }
}
