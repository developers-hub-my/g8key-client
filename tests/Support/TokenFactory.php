<?php

namespace G8Key\Client\Tests\Support;

final class TokenFactory
{
    public string $kid = 'test-2026-04';

    public string $secretKey;

    public string $publicKey;

    public function __construct()
    {
        $kp = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($kp);
        $this->publicKey = sodium_crypto_sign_publickey($kp);
    }

    public function publicKeyBase64(): string
    {
        return base64_encode($this->publicKey);
    }

    /**
     * @param  array<string, mixed>  $payloadOverrides
     */
    public function token(array $payloadOverrides = [], array $headerOverrides = []): string
    {
        $now = time();

        $header = array_merge([
            'alg' => 'EdDSA',
            'typ' => 'G8K',
            'kid' => $this->kid,
        ], $headerOverrides);

        $payload = array_merge([
            'iss' => 'g8key.test',
            'aud' => 'g8stack',
            'sub' => 'activation:test',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'tier' => 'pro',
            'seats' => 5,
            'features' => ['sso', 'audit_log'],
        ], $payloadOverrides);

        $h = self::b64url(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = self::b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $sig = sodium_crypto_sign_detached("{$h}.{$p}", $this->secretKey);

        return $h.'.'.$p.'.'.self::b64url($sig);
    }

    public static function tokenWithSignature(string $header, string $payload, string $signature): string
    {
        return self::b64url($header).'.'.self::b64url($payload).'.'.self::b64url($signature);
    }

    public static function b64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
