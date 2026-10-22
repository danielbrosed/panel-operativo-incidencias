<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser hardening for every response, plus a nonce-based Content-Security-Policy. The nonce is created
 * before the controller runs so that @vite and the root view can stamp it on their tags.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fails closed: anything other than an explicit "report" or "off" (a typo included) enforces the policy.
        $mode = config()->string('security.csp');
        $nonce = $mode === 'off' ? null : Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($nonce !== null) {
            $header = $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $response->headers->set($header, $this->policy($nonce, $request->isSecure()));
        }

        return $response;
    }

    private function policy(string $nonce, bool $secure): string
    {
        // While `npm run dev` is running, assets and HMR come from the Vite server (http://127.0.0.1:5173).
        $vite = Vite::isRunningHot() ? Vite::devServerUrl() : null;
        $viteSocket = $vite !== null ? preg_replace('#^http#', 'ws', $vite) : null;

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", $vite],
            'style-src' => ["'self'", "'nonce-{$nonce}'", $vite],
            'img-src' => ["'self'", 'data:', $vite],
            'font-src' => ["'self'", 'data:', $vite],
            'connect-src' => ["'self'", $vite, $viteSocket],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', array_filter($sources)))
            ->implode('; ');

        return $secure ? $policy.'; upgrade-insecure-requests' : $policy;
    }
}
