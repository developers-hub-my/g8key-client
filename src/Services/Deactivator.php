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

    public function deactivate(): void
    {
        $cached = $this->store->read();

        if ($cached === null) {
            throw new NotActivatedException('No cached activation to deactivate.');
        }

        try {
            $response = $this->http
                ->withToken($cached['activation_uuid'])
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->apiBase, '/').'/api/v1/g8key/deactivate');
        } catch (ConnectionException $e) {
            throw new HeartbeatFailedException("Cannot reach G8Key server: {$e->getMessage()}", previous: $e);
        }

        // 401 (token gone server-side) and 404 are treated as already-deactivated; clear local state.
        if (! $response->successful() && ! in_array($response->status(), [401, 404], true)) {
            throw HeartbeatFailedException::withStatus(
                $response->status(),
                "Deactivation failed (HTTP {$response->status()}): ".$response->body(),
            );
        }

        $this->store->clear();
    }
}
