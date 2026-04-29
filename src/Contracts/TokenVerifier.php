<?php

namespace G8Key\Client\Contracts;

use G8Key\Client\Exceptions\G8KeyClientException;

interface TokenVerifier
{
    /**
     * Verify a signed G8Key token offline. Returns the decoded payload on success.
     *
     * @return array<string, mixed>
     *
     * @throws G8KeyClientException
     */
    public function verify(string $token): array;
}
