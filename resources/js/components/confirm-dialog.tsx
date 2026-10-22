import { useId, useLayoutEffect, useRef, useState, type ReactNode, type RefObject } from 'react';
import { createPortal } from 'react-dom';
import { Button, type ButtonVariant } from '@/components/ui/button';

interface ConfirmDialogProps {
    open: boolean;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    tone?: Extract<ButtonVariant, 'primary' | 'danger'>;
    processing?: boolean;
    confirmDisabled?: boolean;
    /** Element to focus when the dialog opens; the confirm button when omitted. */
    initialFocus?: RefObject<HTMLElement | null>;
    onConfirm: () => void;
    onDismiss: () => void;
    /** Runs right after it closes, once the browser has tried to give focus back to the opener. */
    onClosed?: () => void;
    children?: ReactNode;
}

/**
 * Native modal <dialog>: the browser traps focus, makes the page inert and gives focus back to the opener on
 * close. The `open` prop is the source of truth and the effect only syncs the element with it, so running it
 * twice (StrictMode) changes nothing. A layout effect, so it never paints open with stale content.
 *
 * It lives in <body>, not where it is declared: the list renders each row twice (table and cards, one of them
 * display:none), and an open modal left inside a hidden subtree would vanish while keeping the page inert. It is
 * mounted the first time it opens and kept afterwards, so it always closes through dialog.close().
 */
export function ConfirmDialog(props: ConfirmDialogProps) {
    const { open, processing = false, initialFocus, onDismiss, onClosed } = props;
    const [mounted, setMounted] = useState(open);
    const dialogRef = useRef<HTMLDialogElement>(null);
    const confirmRef = useRef<HTMLButtonElement>(null);
    const titleId = useId();
    const descriptionId = useId();
    const confirmBlocked = processing || props.confirmDisabled === true;

    if (open && !mounted) {
        setMounted(true);
    }

    useLayoutEffect(() => {
        const dialog = dialogRef.current;
        if (open && dialog && !dialog.open) {
            dialog.showModal();
            (initialFocus?.current ?? confirmRef.current)?.focus();
        } else if (!open && dialog?.open) {
            dialog.close();
            onClosed?.();
        }
    }, [open, initialFocus, onClosed]);

    if (!mounted) {
        return null;
    }

    const dismiss = () => {
        if (!processing) onDismiss();
    };

    return createPortal(
        <dialog
            ref={dialogRef}
            aria-labelledby={titleId}
            aria-describedby={descriptionId}
            onCancel={(event) => {
                // Esc dismisses, except while the request is on its way.
                event.preventDefault();
                dismiss();
            }}
            // The browser can still close it on its own (a repeated Esc): keep the state in step.
            onClose={() => open && onDismiss()}
            className="m-auto w-[min(34rem,calc(100%-2rem))] rounded-card bg-white p-0 text-ink backdrop:bg-navy/40"
        >
            <form
                noValidate
                className="flex flex-col gap-4 p-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    if (!confirmBlocked) props.onConfirm();
                }}
            >
                <h2 id={titleId} className="text-lg font-semibold text-navy">
                    {props.title}
                </h2>
                <div id={descriptionId} className="text-sm leading-relaxed text-muted">
                    {props.description}
                </div>
                {props.children}
                <div className="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <Button variant="secondary" onClick={dismiss} aria-disabled={processing || undefined}>
                        Cancelar
                    </Button>
                    <Button
                        ref={confirmRef}
                        type="submit"
                        variant={props.tone ?? 'primary'}
                        aria-disabled={confirmBlocked || undefined}
                        aria-busy={processing || undefined}
                    >
                        {props.confirmLabel}
                    </Button>
                </div>
            </form>
        </dialog>,
        document.body,
    );
}
