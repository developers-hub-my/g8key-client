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
     * @param  array{instance_label?: string, hostname?: string, product_version?: string}  $context
     * @return array{activation_uuid: string, token: string, payload: array<string, mixed>}
     */
    public function activate(string $licenseKey, ?string $fingerprint = null, array $context = []): array
    {
        $body = array_filter([
            'license_key' => $licenseKey,
            'fingerprint' => Fingerprint::resolve($fingerprint),
            'instance_label' => $context['instance_label'] ?? null,
            'hostname' => $context['hostname'] ?? gethostname() ?: null,
            'product_version' => $context['product_version'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->apiBase, '/').'/api/v1/g8key/activate', $body);
        } catch (ConnectionException $e) {
            throw new ActivationFailedException("Cannot reach G8Key server: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            throw ActivationFailedException::withStatus(
                $response->status(),
                "Activation failed (HTTP {$response->status()}): ".$response->body(),
            );
        }

        $responseBody = $response->json();

        if (! is_array($responseBody) || ! isset($responseBody['activation_uuid'], $responseBody['token'])) {
            throw new ActivationFailedException('Activation response missing activation_uuid or token.');
        }

        $payload = $this->verifier->verify($responseBody['token']);

        $this->store->write([
            'activation_uuid' => $responseBody['activation_uuid'],
            'token' => $responseBody['token'],
            'status' => 'active',
            'last_heartbeat_at' => CarbonImmutable::now()->toIso8601String(),
        ]);

        return [
            'activation_uuid' => $responseBody['activation_uuid'],
            'token' => $responseBody['token'],
            'payload' => $payload,
        ];
    }
}
