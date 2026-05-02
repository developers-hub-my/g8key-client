<?php

namespace G8Key\Client\Services;

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Contracts\TokenVerifier;
use G8Key\Client\Exceptions\HeartbeatFailedException;
use G8Key\Client\Exceptions\LicenseRevokedException;
use G8Key\Client\Exceptions\LicenseSuspendedException;
use G8Key\Client\Exceptions\NotActivatedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

final class Heartbeat
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly TokenVerifier $verifier,
        private readonly LicenseStore $store,
        private readonly string $apiBase,
        private readonly int $timeout,
    ) {}

    /**
     * @return array{status: string, payload?: array<string, mixed>}
     */
    public function pulse(?string $productVersion = null): array
    {
        $cached = $this->store->read();

        if ($cached === null) {
            throw new NotActivatedException('No cached activation; run license:activate first.');
        }

        $body = array_filter(
            ['product_version' => $productVersion],
            fn ($v) => $v !== null && $v !== '',
        );

        try {
            $response = $this->http
                ->withToken($cached['activation_uuid'])
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->apiBase, '/').'/api/v1/g8key/heartbeat', $body);
        } catch (ConnectionException) {
            // Network failure: leave cached state untouched. Caller relies on offline_grace_days.
            return ['status' => 'offline'];
        }

        if ($response->status() === 410) {
            $this->store->write(array_merge($cached, ['status' => 'revoked']));

            throw new LicenseRevokedException('License has been revoked.');
        }

        if ($response->status() === 423) {
            $this->store->write(array_merge($cached, ['status' => 'suspended']));

            throw new LicenseSuspendedException('License has been suspended.');
        }

        if (! $response->successful()) {
            throw HeartbeatFailedException::withStatus(
                $response->status(),
                "Heartbeat failed (HTTP {$response->status()}): ".$response->body(),
            );
        }

        $responseBody = $response->json();

        if (! is_array($responseBody) || ! isset($responseBody['token'])) {
            throw new HeartbeatFailedException('Heartbeat response missing token.');
        }

        $payload = $this->verifier->verify($responseBody['token']);

        $this->store->write([
            'activation_uuid' => $cached['activation_uuid'],
            'token' => $responseBody['token'],
            'status' => 'active',
            'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
        ]);

        return ['status' => 'active', 'payload' => $payload];
    }
}
