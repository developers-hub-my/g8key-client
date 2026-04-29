<?php

namespace G8Key\Client\Facades;

use G8Key\Client\LicenseManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array|null payload()
 * @method static string|null tier()
 * @method static int|null seats()
 * @method static list<string> features()
 * @method static bool has(string $feature)
 * @method static \Carbon\CarbonImmutable|null expiresAt()
 * @method static string|null activationUuid()
 * @method static string status()
 * @method static bool isValid()
 * @method static bool isInOfflineGrace()
 * @method static void flush()
 *
 * @see \G8Key\Client\LicenseManager
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LicenseManager::class;
    }
}
