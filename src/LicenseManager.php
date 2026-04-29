<?php

namespace G8Key\Client;

use Carbon\CarbonImmutable;
use G8Key\Client\Contracts\LicenseStore;
use G8Key\Client\Contracts\TokenVerifier;
use G8Key\Client\Exceptions\G8KeyClientException;
use Throwable;

class LicenseManager
{
    private bool $resolved = false;

    /** @var array<string, mixed>|null */
    private ?array $cachedRecord = null;

    /** @var array<string, mixed>|null */
    private ?array $cachedPayload = null;

    private string $resolvedStatus = 'not_activated';

    public function __construct(
        private readonly LicenseStore $store,
        private readonly TokenVerifier $verifier,
        private readonly int $offlineGraceDays,
    ) {}

    /** @return array<string, mixed>|null */
    public function payload(): ?array
    {
        $this->resolve();

        return $this->cachedPayload;
    }

    public function tier(): ?string
    {
        $payload = $this->payload();

        $tier = $payload['tier'] ?? null;

        return is_string($tier) ? $tier : null;
    }

    public function seats(): ?int
    {
        $payload = $this->payload();

        return isset($payload['seats']) ? (int) $payload['seats'] : null;
    }

    /** @return list<string> */
    public function features(): array
    {
        $payload = $this->payload();

        $features = $payload['features'] ?? [];

        return is_array($features) ? array_values(array_filter($features, 'is_string')) : [];
    }

    public function has(string $feature): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        return in_array($feature, $this->features(), true);
    }

    public function expiresAt(): ?CarbonImmutable
    {
        $payload = $this->payload();

        if (! isset($payload['exp'])) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $payload['exp']);
    }

    public function activationUuid(): ?string
    {
        $this->resolve();

        $uuid = $this->cachedRecord['activation_uuid'] ?? null;

        return is_string($uuid) ? $uuid : null;
    }

    public function status(): string
    {
        $this->resolve();

        return $this->resolvedStatus;
    }

    public function isValid(): bool
    {
        $status = $this->status();

        return in_array($status, ['active', 'offline_grace'], true);
    }

    public function isInOfflineGrace(): bool
    {
        return $this->status() === 'offline_grace';
    }

    /** Force a fresh resolve on next access. Useful in tests. */
    public function flush(): void
    {
        $this->resolved = false;
        $this->cachedRecord = null;
        $this->cachedPayload = null;
        $this->resolvedStatus = 'not_activated';
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;

        $record = $this->store->read();

        if ($record === null) {
            $this->resolvedStatus = 'not_activated';

            return;
        }

        $this->cachedRecord = $record;

        $persistedStatus = $record['status'] ?? 'active';

        if ($persistedStatus === 'revoked') {
            $this->resolvedStatus = 'revoked';

            return;
        }

        if ($persistedStatus === 'suspended') {
            $this->resolvedStatus = 'suspended';

            return;
        }

        try {
            $this->cachedPayload = $this->verifier->verify((string) $record['token']);
            $this->resolvedStatus = 'active';

            return;
        } catch (G8KeyClientException) {
            // Token is no longer cryptographically valid (e.g. expired). Fall through to grace check.
        } catch (Throwable) {
            $this->resolvedStatus = 'expired';

            return;
        }

        $lastBeat = $record['last_heartbeat_at'] ?? null;

        if (is_string($lastBeat) && $lastBeat !== '') {
            try {
                $beatAt = CarbonImmutable::parse($lastBeat);

                if ($beatAt->addDays($this->offlineGraceDays)->isFuture()) {
                    $this->resolvedStatus = 'offline_grace';

                    return;
                }
            } catch (Throwable) {
                // fall through
            }
        }

        $this->resolvedStatus = 'expired';
    }
}
