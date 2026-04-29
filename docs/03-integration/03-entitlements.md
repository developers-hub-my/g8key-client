# Entitlements

How the application reads the verified license payload.

## Facade

```php
use G8Key\Client\Facades\License;

License::isValid();        // bool
License::status();         // 'active' | 'expired' | 'revoked' | 'suspended' | 'offline_grace' | 'not_activated'

License::tier();           // 'pro' | 'enterprise' | …
License::seats();          // int|null
License::features();       // string[]
License::has('sso');       // bool
License::expiresAt();      // CarbonImmutable|null
License::activationUuid(); // string|null
License::payload();        // array|null  (entire decoded payload, for diagnostics)
```

The payload is verified once per request and memoized.

## Route middleware

The service provider registers two aliases.

```php
// Every route here requires License::isValid()
Route::middleware('license')->group(function () {
    Route::get('/dashboard', DashboardController::class);
});

// Requires both isValid() and the named feature
Route::middleware('license.feature:audit_log')->group(function () {
    Route::get('/audit', AuditController::class);
});
```

Both middleware return `403 Forbidden` on failure. Customise the abort response by extending `RequireLicense` and
binding it in your application provider.

## Blade directive

```blade
@licenseFeature('priority_support')
    <a href="/support/priority">Priority support</a>
@endlicenseFeature

@licenseFeature('sso')
    <livewire:sso-config />
@else
    <p>Upgrade to enable SSO.</p>
@endlicenseFeature
```

The `@else` branch is supported.

## Pattern: capability-keyed UI

For complex products with many features, prefer a single capability map rather than scattered `License::has()` calls:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    View::composer('*', function ($view) {
        $view->with('caps', [
            'sso'              => License::has('sso'),
            'audit_log'        => License::has('audit_log'),
            'priority_support' => License::has('priority_support'),
        ]);
    });
}
```

```blade
@if ($caps['sso'])
    ...
@endif
```

This isolates the License facade behind a single boundary and makes feature flags easy to inventory.

## Next Steps

- [Deactivation](04-deactivation.md)
- [Troubleshooting](../05-operations/03-troubleshooting.md)
