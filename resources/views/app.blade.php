<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Vite's dev client and Inertia's progress bar read the CSP nonce from here. --}}
        <meta property="csp-nonce" nonce="{{ Vite::cspNonce() }}">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body class="min-h-dvh bg-surface font-sans text-ink antialiased">
        <x-inertia::app />
    </body>
</html>
