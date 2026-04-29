<?php

namespace G8Key\Client\Services;

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Contracts\TokenVerifier;
use G8Key\Client\Exceptions\ActivationFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

final class Activator
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly TokenVerifier $verifier,
        private readonly LicenseStore $store,
        private readonly string $apiBase,
        private readonly int $timeout,
    ) {}

    /**
     * @return array{activation_uuid: string, token: string, payload: array<string, mixed>}
     */
    public function activate(string $key, ?string $fingerprint = null): array
    {
        $fingerprint = Fingerprint::resolve($fingerprint);

        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->apiBase, '/').'/api/v1/g8key/activate', [
                    'key' => $key,
                    'fingerprint' => $fingerprint,
                ]);
        } catch (ConnectionException $e) {
            throw new ActivationFailedException("Cannot reach G8Key server: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            throw ActivationFailedException::withStatus(
                $response->status(),
                "Activation failed (HTTP {$response->status()}): ".$response->body(),
            );
        }

        $body = $response->json();

        if (! is_array($body) || ! isset($body['activation_uuid'], $body['token'])) {
            throw new ActivationFailedException('Activation response missing activation_uuid or token.');
        }

        $payload = $this->verifier->verify($body['token']);

        $this->store->write([
            'activation_uuid' => $body['activation_uuid'],
            'token' => $body['token'],
            'status' => 'active',
            'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
        ]);

        return [
            'activation_uuid' => $body['activation_uuid'],
            'token' => $body['token'],
            'payload' => $payload,
        ];
    }
}
