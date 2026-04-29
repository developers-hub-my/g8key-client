<?php

namespace G8Key\Client\Http\Middleware;

use Closure;
use G8Key\Client\LicenseManager;
use Illuminate\Http\Request;

class RequireLicense
{
    public function __construct(private readonly LicenseManager $license) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->license->isValid()) {
            abort(403, 'License required.');
        }

        return $next($request);
    }
}
