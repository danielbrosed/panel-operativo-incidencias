<?php

declare(strict_types=1);

// Only the blocks this app changes; the rest fall back to vendor/inertiajs/inertia-laravel/config/inertia.php.
// Laravel merges top-level keys only, so every block declared here is complete.
return [

    // No SSR: this is an internal panel, and SSR would need a Node process running next to PHP.
    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),
        'runtime' => env('INERTIA_SSR_RUNTIME', 'node'),
        'ensure_runtime_exists' => false,
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'hot_url' => env('INERTIA_SSR_HOT_URL'),
        'timeout' => env('INERTIA_SSR_TIMEOUT'),
        'ensure_bundle_exists' => true,
        'throw_on_error' => false,
    ],

    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/pages')],
        'extensions' => ['tsx'],
    ],

    // assertInertia() fails if the page component does not exist on disk (case-sensitive on Linux CI).
    'testing' => [
        'ensure_pages_exist' => true,
    ],

    // The package switches DevTools on in local when the variable is missing, and they serve the recorded requests
    // without authentication: here they only run with INERTIA_DEVTOOLS_ENABLED=true. The rest is the package default.
    'devtools' => [
        'enabled' => (bool) env('INERTIA_DEVTOOLS_ENABLED', false),
        'except' => ['telescope*', 'horizon*', '_inertia/devtools*'],
        'storage' => [
            'path' => storage_path('inertia-devtools'),
            'ttl' => (int) env('INERTIA_DEVTOOLS_TTL_HOURS', 24),
            'prune_interval' => (int) env('INERTIA_DEVTOOLS_PRUNE_INTERVAL_SECONDS', 300),
            'limit' => (int) env('INERTIA_DEVTOOLS_LIMIT', 100),
        ],
        'middleware' => ['web'],
        'gate' => env('INERTIA_DEVTOOLS_GATE'),
        'redact' => [
            'keys' => [
                'password',
                'password_confirmation',
                'current_password',
                'token',
                '_token',
                'access_token',
                'refresh_token',
                'secret',
                'client_secret',
                'api_key',
            ],
            'headers' => [
                'cookie',
                'set-cookie',
                'authorization',
                'proxy-authorization',
                'x-xsrf-token',
                'x-csrf-token',
            ],
        ],
    ],

];
