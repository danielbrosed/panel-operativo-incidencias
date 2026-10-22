<?php

declare(strict_types=1);

return [

    /*
     * Content-Security-Policy mode, applied by App\Http\Middleware\SecurityHeaders:
     *  - "enforce": the browser blocks anything outside the policy (tests and production).
     *  - "report":  same policy as Content-Security-Policy-Report-Only; violations only show in the console.
     *               Default for local development, so a reviewer never gets a blank page.
     *  - "off":     no CSP header (the other security headers are still sent).
     */
    'csp' => env('CSP_MODE', env('APP_ENV') === 'local' ? 'report' : 'enforce'),

];
