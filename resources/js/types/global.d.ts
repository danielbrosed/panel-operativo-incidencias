// Types for what every Inertia page receives. Mirrors App\Http\Middleware\HandleInertiaRequests::share()
// and the Inertia::flash('toast', …) calls in the controllers.
import '@inertiajs/core';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            actor: {
                current: string;
                options: string[];
                update_url: string;
            };
        };
        flashDataType: {
            toast?: { type: 'success' | 'error'; message: string };
        };
    }
}
