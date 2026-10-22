<?php

declare(strict_types=1);

// Only the keys this app changes. Anything missing falls back to the package defaults
// (vendor/inertiajs/inertia-laravel/config/inertia.php); Laravel merges top-level keys.
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

];
