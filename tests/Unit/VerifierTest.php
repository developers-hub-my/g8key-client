<?php

use G8Key\Client\Exceptions\AlgConfusionException;
use G8Key\Client\Exceptions\AudienceMismatchException;
use G8Key\Client\Exceptions\InvalidSignatureException;
use G8Key\Client\Exceptions\MalformedTokenException;
use G8Key\Client\Exceptions\TokenExpiredException;
use G8Key\Client\Exceptions\UnknownKidException;
use G8Key\Client\Services\Verifier;
use G8Key\Client\Tests\Support\TokenFactory;

beforeEach(function () {
    $this->factory = new TokenFactory;
    $this->verifier = new Verifier(
        [$this->factory->kid => $this->factory->publicKeyBase64()],
        'g8stack',
    );
});

it('verifies a well-formed signed token and returns the payload', function () {
    $token = $this->factory->token();

    $payload = $this->verifier->verify($token);

    expect($payload['aud'])->toBe('g8stack');
    expect($payload['tier'])->toBe('pro');
    expect($payload['features'])->toContain('sso');
});

it('rejects tokens that do not have three segments', function () {
    $this->verifier->verify('only.two');
})->throws(MalformedTokenException::class);

it('rejects tokens whose alg is not EdDSA', function () {
    $token = $this->factory->token([], ['alg' => 'HS256']);

    $this->verifier->verify($token);
})->throws(AlgConfusionException::class);

it('rejects tokens whose typ is not G8K', function () {
    $token = $this->factory->token([], ['typ' => 'JWT']);

    $this->verifier->verify($token);
})->throws(MalformedTokenException::class);

it('rejects tokens with an unknown kid', function () {
    $token = $this->factory->token([], ['kid' => 'unknown-kid']);

    $this->verifier->verify($token);
})->throws(UnknownKidException::class);

it('rejects tokens whose signature is the wrong length', function () {
    $token = TokenFactory::tokenWithSignature(
        json_encode(['alg' => 'EdDSA', 'typ' => 'G8K', 'kid' => $this->factory->kid]),
        json_encode(['aud' => 'g8stack']),
        'short',
    );

    $this->verifier->verify($token);
})->throws(InvalidSignatureException::class);

it('rejects tokens whose signature does not verify', function () {
    $token = $this->factory->token();
    [$h, $p] = explode('.', $token);
    // Forge a 64-byte garbage signature so length passes but verify fails.
    $forged = $h.'.'.$p.'.'.TokenFactory::b64url(str_repeat("\0", SODIUM_CRYPTO_SIGN_BYTES));

    $this->verifier->verify($forged);
})->throws(InvalidSignatureException::class);

it('rejects tokens whose audience does not match', function () {
    $token = $this->factory->token(['aud' => 'g8id']);

    $this->verifier->verify($token);
})->throws(AudienceMismatchException::class);

it('rejects tokens whose nbf is in the future', function () {
    $token = $this->factory->token(['nbf' => time() + 3600]);

    $this->verifier->verify($token);
})->throws(MalformedTokenException::class);

it('rejects tokens whose exp is in the past', function () {
    $token = $this->factory->token(['nbf' => time() - 7200, 'exp' => time() - 3600]);

    $this->verifier->verify($token);
})->throws(TokenExpiredException::class);
