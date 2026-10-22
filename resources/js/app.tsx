import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { APP_NAME, LOADING_DELAY_MS } from '@/lib/app-config';

// Set by the SecurityHeaders middleware; Inertia stamps it on the <style> tags of its progress bar.
const nonce = document.querySelector<HTMLMetaElement>('meta[property="csp-nonce"]')?.nonce || undefined;

const pages = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx');

void createInertiaApp({
    title: (title) => (title ? `${title} · ${APP_NAME}` : APP_NAME),
    resolve: async (name) => {
        const page = pages[`./pages/${name}.tsx`];
        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }
        return (await page()).default;
    },
    layout: () => AppLayout,
    // Inertia writes the colour into the progress bar's <style> tag as is, so a theme token works.
    progress: { color: 'var(--color-brand)', delay: LOADING_DELAY_MS },
    nonce,
    strictMode: true,
});
