<?php

namespace G8Key\Client\Http\Middleware;

use Closure;
use G8Key\Client\LicenseManager;
use Illuminate\Http\Request;

class RequireFeature
{
    public function __construct(private readonly LicenseManager $license) {}

    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        if (! $this->license->isValid() || ! $this->license->has($feature)) {
            abort(403, "Feature '{$feature}' not available on this license.");
        }

        return $next($request);
    }
}
