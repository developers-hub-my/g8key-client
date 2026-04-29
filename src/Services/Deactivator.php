<?php

namespace G8Key\Client\Services;

use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Exceptions\HeartbeatFailedException;
use G8Key\Client\Exceptions\NotActivatedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

final class Deactivator
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly LicenseStore $store,
        private readonly string $apiBase,
        private readonly int $timeout,
    ) {}

    public function deactivate(?string $fingerprint = null): void
    {
        $cached = $this->store->read();

        if ($cached === null) {
            throw new NotActivatedException('No cached activation to deactivate.');
        }

        $fingerprint = Fingerprint::resolve($fingerprint);

        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->apiBase, '/').'/api/v1/g8key/deactivate', [
                    'activation_uuid' => $cached['activation_uuid'],
                    'fingerprint'     => $fingerprint,
                ]);
        } catch (ConnectionException $e) {
            throw new HeartbeatFailedException("Cannot reach G8Key server: {$e->getMessage()}", previous: $e);
        }

        // Treat 404 as already-deactivated; clear local state regardless.
        if (! $response->successful() && $response->status() !== 404) {
            throw HeartbeatFailedException::withStatus(
                $response->status(),
                "Deactivation failed (HTTP {$response->status()}): ".$response->body(),
            );
        }

        $this->store->clear();
    }
}
