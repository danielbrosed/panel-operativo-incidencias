<?php

declare(strict_types=1);

return [

    /*
     * Content-Security-Policy mode, applied by App\Http\Middleware\SecurityHeaders:
     *  - "enforce": the browser blocks anything outside the policy. Default outside APP_ENV=local.
     *  - "report":  same policy as Content-Security-Policy-Report-Only; violations only show in the console.
     *               Default for local development, so a reviewer never gets a blank page.
     *  - "off":     no CSP header (the other security headers are still sent).
     * Any other value enforces: a typo must never switch the policy off.
     */
    'csp' => env('CSP_MODE', env('APP_ENV') === 'local' ? 'report' : 'enforce'),

];
