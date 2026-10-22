<?php

declare(strict_types=1);

use App\Exceptions\Incidents\IncidentDomainException;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, so error pages (404, 419, 500) also carry the security headers.
        $middleware->append(SecurityHeaders::class);

        // Outside local and testing, only the host of APP_URL (and its subdomains) is accepted: absolute URLs built
        // from a forged Host header would otherwise point assets and redirects elsewhere.
        $middleware->trustHosts();

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business-rule rejections become validation errors on the field that caused them: an Inertia request
        // gets a redirect back with the error in the same form; a JSON client gets 422, or 409 for a conflict.
        // (A plain 409 must never reach Inertia: its client reserves 409 for its own redirects.)
        $exceptions->map(
            IncidentDomainException::class,
            fn (IncidentDomainException $e): ValidationException => ValidationException::withMessages([
                $e->field() => $e->getMessage(),
            ])->status($e->isConflict() ? 409 : 422),
        );

        // Expired session (419) and rate limit (429): go back with a readable message instead of an error page. It
        // travels as a validation error too (key "request"), so the form treats it as a failure and keeps what the
        // person typed instead of resetting as it does on success.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            if ($request->header('X-Inertia') && in_array($status, [419, 429], true)) {
                $message = $status === 419
                    ? 'La sesión ha caducado. Vuelve a intentarlo.'
                    : 'Demasiadas acciones seguidas. Espera un momento y vuelve a intentarlo.';

                Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

                return back()->withErrors(['request' => $message]);
            }

            return $response;
        });
    })->create();
