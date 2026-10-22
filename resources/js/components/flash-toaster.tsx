import type { FlashData } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { IconCheck, IconClose } from '@/components/ui/icons';
import { cx } from '@/lib/cx';

type Toast = NonNullable<FlashData['toast']>;

const DISMISS_AFTER_MS = 5000;

/**
 * Shows the server's one-off `toast` flash. The first one may arrive with the page itself; later ones arrive
 * with each visit through the router's "flash" event. `seq` changes for every new toast, even with the same
 * text, so the message is re-rendered and announced again.
 */
export function FlashToaster() {
    const { flash } = usePage();
    const [current, setCurrent] = useState(() => (flash.toast ? { toast: flash.toast, seq: 1 } : null));

    useEffect(
        () =>
            router.on('flash', (event) => {
                const toast = event.detail.flash.toast;
                if (toast) {
                    // Same object as the one already shown when the initial page's event fires after mount.
                    setCurrent((shown) => (shown?.toast === toast ? shown : { toast, seq: (shown?.seq ?? 0) + 1 }));
                }
            }),
        [],
    );

    useEffect(() => {
        if (!current) return;
        const timer = window.setTimeout(() => setCurrent(null), DISMISS_AFTER_MS);
        return () => window.clearTimeout(timer);
    }, [current]);

    return (
        <div
            role="status"
            aria-live="polite"
            className="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4 sm:bottom-6 sm:justify-end sm:px-6"
        >
            {current && <ToastMessage key={current.seq} toast={current.toast} onDismiss={() => setCurrent(null)} />}
        </div>
    );
}

function ToastMessage({ toast, onDismiss }: { toast: Toast; onDismiss: () => void }) {
    const isError = toast.type === 'error';

    return (
        <div
            className={cx(
                'pointer-events-auto flex w-full max-w-md items-center gap-3 rounded-card py-3 pr-3 pl-5 text-sm font-medium',
                isError ? 'border border-danger/30 bg-danger-bg text-danger' : 'bg-navy text-white',
            )}
        >
            {isError ? <span className="sr-only">Error: </span> : <IconCheck className="text-brand" />}
            <p className="flex-1">{toast.message}</p>
            <button
                type="button"
                onClick={onDismiss}
                aria-label="Cerrar aviso"
                className="grid size-8 shrink-0 place-items-center rounded-full opacity-80 hover:opacity-100"
            >
                <IconClose />
            </button>
        </div>
    );
}
