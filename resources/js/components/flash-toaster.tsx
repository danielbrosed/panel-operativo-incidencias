import type { FlashData } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { IconCheck, IconClose } from '@/components/ui/icons';
import { cx } from '@/lib/cx';

type Toast = NonNullable<FlashData['toast']>;
type Item = Toast & { id: number };

const DISMISS_AFTER_MS = 5000;
const MAX_TOASTS = 3;
const NETWORK_ERROR = 'Sin conexión con el servidor. Comprueba la red y vuelve a intentarlo.';
const SERVER_ERROR = 'Algo ha fallado en el servidor. Vuelve a intentarlo en un momento.';

/**
 * The only place that shows notices: the server's `toast` flash and the requests that got no usable answer. A newer
 * toast with the same text replaces the older one, so it is announced again without piling up.
 */
export function FlashToaster() {
    const { flash } = usePage();
    const [toasts, setToasts] = useState<Item[]>(() => (flash.toast ? [{ ...flash.toast, id: 0 }] : []));
    const lastFlash = useRef(flash.toast);
    const nextId = useRef(1);

    useEffect(() => {
        const show = (toast: Toast) => {
            const item = { ...toast, id: nextId.current++ };
            setToasts((list) => [...list.filter((shown) => shown.message !== toast.message), item].slice(-MAX_TOASTS));
        };

        const stopFlash = router.on('flash', (event) => {
            const toast = event.detail.flash.toast;
            // The initial page's event fires after mount with the object that is already on screen.
            if (toast && toast !== lastFlash.current) {
                lastFlash.current = toast;
                show(toast);
            }
        });
        const stopNetwork = router.on('networkError', (event) => {
            event.preventDefault();
            show({ type: 'error', message: NETWORK_ERROR });
        });
        const stopHttp = router.on('httpException', (event) => {
            const { status, headers } = event.detail.response;
            // With `npm run dev` Inertia's own dialog stays: it shows Laravel's error page with the stack trace. An
            // Inertia page sent with a 4xx status renders as usual.
            if (import.meta.env.DEV || (status < 500 && headers['x-inertia'])) return;
            event.preventDefault();
            show({ type: 'error', message: SERVER_ERROR });
        });

        return () => {
            stopFlash();
            stopNetwork();
            stopHttp();
        };
    }, []);

    const dismiss = useCallback((id: number) => setToasts((list) => list.filter((toast) => toast.id !== id)), []);
    const render = (toast: Item) => <ToastMessage key={toast.id} toast={toast} onDismiss={dismiss} />;

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4 sm:bottom-6 sm:items-end sm:px-6">
            {/* Errors interrupt and stay until closed; confirmations are polite and go away on their own. */}
            <div role="alert" className="flex w-full max-w-md flex-col gap-2">
                {toasts.filter((toast) => toast.type === 'error').map(render)}
            </div>
            <div role="status" aria-live="polite" className="flex w-full max-w-md flex-col gap-2">
                {toasts.filter((toast) => toast.type !== 'error').map(render)}
            </div>
        </div>
    );
}

function ToastMessage({ toast, onDismiss }: { toast: Item; onDismiss: (id: number) => void }) {
    const isError = toast.type === 'error';
    const [hovered, setHovered] = useState(false);
    const [focused, setFocused] = useState(false);

    // Not while the pointer or the keyboard is on it: there would be no time to read it or reach its button.
    useEffect(() => {
        if (isError || hovered || focused) return;
        const timer = window.setTimeout(() => onDismiss(toast.id), DISMISS_AFTER_MS);
        return () => window.clearTimeout(timer);
    }, [isError, hovered, focused, onDismiss, toast.id]);

    return (
        <div
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            onFocus={() => setFocused(true)}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) setFocused(false);
            }}
            className={cx(
                'pointer-events-auto flex w-full items-center gap-3 rounded-card py-3 pr-3 pl-5 text-sm font-medium',
                isError ? 'border border-danger/30 bg-danger-bg text-danger' : 'bg-navy text-white',
            )}
        >
            {isError ? <span className="sr-only">Error: </span> : <IconCheck className="text-brand" />}
            <p className="flex-1">{toast.message}</p>
            <button
                type="button"
                onClick={() => onDismiss(toast.id)}
                aria-label="Cerrar aviso"
                className="grid size-8 shrink-0 place-items-center rounded-full opacity-80 hover:opacity-100"
            >
                <IconClose />
            </button>
        </div>
    );
}
