<?php

namespace G8Key\Client\Services;

use G8Key\Client\Contracts\TokenVerifier;
use G8Key\Client\Exceptions\AlgConfusionException;
use G8Key\Client\Exceptions\AudienceMismatchException;
use G8Key\Client\Exceptions\InvalidSignatureException;
use G8Key\Client\Exceptions\MalformedTokenException;
use G8Key\Client\Exceptions\TokenExpiredException;
use G8Key\Client\Exceptions\UnknownKidException;

final class Verifier implements TokenVerifier
{
    /**
     * @param  array<string, string>  $publicKeys  keyed by kid, base64-encoded public keys
     */
    public function __construct(
        private readonly array $publicKeys,
        private readonly string $expectedAud,
    ) {}

    public function verify(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new MalformedTokenException('Token must have exactly three segments.');
        }

        [$h, $p, $s] = $segments;

        $header = json_decode((string) base64_decode(strtr($h, '-_', '+/'), true), true);

        if (! is_array($header)) {
            throw new MalformedTokenException('Header is not a JSON object.');
        }

        if (($header['alg'] ?? null) !== 'EdDSA') {
            throw new AlgConfusionException('Unexpected token algorithm.');
        }

        if (($header['typ'] ?? null) !== 'G8K') {
            throw new MalformedTokenException('Unexpected token type.');
        }

        $kid = $header['kid'] ?? null;

        if (! is_string($kid) || ! isset($this->publicKeys[$kid]) || $this->publicKeys[$kid] === null) {
            throw new UnknownKidException("Unknown kid: ".(is_string($kid) ? $kid : 'null'));
        }

        $sig = base64_decode(strtr($s, '-_', '+/'), true);

        if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new InvalidSignatureException('Bad signature length.');
        }

        $publicKey = base64_decode($this->publicKeys[$kid], true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new InvalidSignatureException('Configured public key is malformed.');
        }

        if (! sodium_crypto_sign_verify_detached($sig, "{$h}.{$p}", $publicKey)) {
            throw new InvalidSignatureException('Signature mismatch.');
        }

        $payload = json_decode((string) base64_decode(strtr($p, '-_', '+/'), true), true);

        if (! is_array($payload)) {
            throw new MalformedTokenException('Payload is not a JSON object.');
        }

        if (($payload['aud'] ?? null) !== $this->expectedAud) {
            throw new AudienceMismatchException('Token audience does not match.');
        }

        $now = time();

        if ((int) ($payload['nbf'] ?? 0) > $now) {
            throw new MalformedTokenException('Token is not yet valid.');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] > 0 && (int) $payload['exp'] < $now) {
            throw new TokenExpiredException('Token has expired.');
        }

        return $payload;
    }
}
