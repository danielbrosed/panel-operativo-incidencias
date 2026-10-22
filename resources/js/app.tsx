import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

const appName = 'Panel operativo de incidencias';

// Set by the SecurityHeaders middleware; Inertia stamps it on the <style> tags of its progress bar.
const nonce = document.querySelector<HTMLMetaElement>('meta[property="csp-nonce"]')?.nonce || undefined;

const pages = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx');

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: async (name) => {
        const page = pages[`./pages/${name}.tsx`];
        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }
        return (await page()).default;
    },
    layout: () => AppLayout,
    progress: { color: '#0086FF', delay: 150 },
    nonce,
    strictMode: true,
});
