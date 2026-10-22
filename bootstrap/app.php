<?php

declare(strict_types=1);

use App\Exceptions\Incidents\IncidentRuleViolation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Toast;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, so error pages (404, 419, 500) also carry the security headers.
        $middleware->append(SecurityHeaders::class);

        // Outside local and testing, only the host of APP_URL (and its subdomains) is accepted: absolute URLs built
        // from a forged Host header would otherwise point assets and redirects elsewhere. Local: AppServiceProvider.
        $middleware->trustHosts();

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A business-rule rejection is a validation error (422) on the field that caused it: an Inertia request is
        // redirected back with the error shown in the same form.
        $exceptions->map(
            IncidentRuleViolation::class,
            fn (IncidentRuleViolation $e): ValidationException => ValidationException::withMessages([
                $e->field() => $e->getMessage(),
            ]),
        );

        // Expired session (419) and rate limit (429): back with a readable message instead of an error page. It
        // travels as a validation error too (key "request"), so the form keeps what the person typed.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            if (! $request->inertia() || ! in_array($status, [419, 429], true)) {
                return $response;
            }

            $message = $status === 419
                ? 'La sesión ha caducado. Vuelve a intentarlo.'
                : 'Demasiadas acciones seguidas. Espera un momento y vuelve a intentarlo.';

            Toast::error($message);

            // Back to the same page on this host: only the path and the query of the Referer are kept.
            $query = parse_url(url()->previous(), PHP_URL_QUERY);

            return redirect()->to(url()->previousPath().(is_string($query) ? "?{$query}" : ''))
                ->withErrors(['request' => $message]);
        });
    })->create();
