<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audience
    |--------------------------------------------------------------------------
    |
    | The product slug that must match the `aud` claim in every signed token.
    | Set this in `.env` to whatever the G8Key admin assigned to your product.
    |
    */

    'audience' => env('G8KEY_AUDIENCE', 'g8stack'),

    /*
    |--------------------------------------------------------------------------
    | API endpoint
    |--------------------------------------------------------------------------
    */

    'api_base'    => env('G8KEY_API_BASE', 'https://g8key.devhub.my'),
    'api_timeout' => (int) env('G8KEY_API_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Public keys (kid → base64 EdDSA public key)
    |--------------------------------------------------------------------------
    |
    | Multiple kids may coexist during a key rotation; the verifier picks the
    | one matching the token's `kid` header.
    |
    */

    'public_keys' => [
        // 'g8stack-2026-04' => env('G8STACK_LICENSE_PUBLIC_KEY_G8STACK_2026_04'),
    ],

    /*
    |--------------------------------------------------------------------------
    | License store
    |--------------------------------------------------------------------------
    |
    | Where the package persists `{ activation_uuid, token, status,
    | last_heartbeat_at }`. `file` writes JSON to `cache_path`; `database`
    | uses the table named in `database_table`.
    |
    */

    'store'          => env('G8KEY_STORE', 'file'),
    'cache_path'     => storage_path('app/license.json'),
    'database_table' => 'g8key_licenses',

    /*
    |--------------------------------------------------------------------------
    | Offline grace
    |--------------------------------------------------------------------------
    |
    | Days the cached token continues to be honoured after the most recent
    | successful heartbeat.
    |
    */

    'offline_grace_days' => (int) env('G8KEY_OFFLINE_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Fingerprint
    |--------------------------------------------------------------------------
    |
    | Optional callable returning a string used to identify this host on
    | activation and heartbeat. The default is `sha256(app.url + hostname)`.
    |
    */

    'fingerprint' => null,

];
