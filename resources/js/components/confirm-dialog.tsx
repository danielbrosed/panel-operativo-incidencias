import { useId, useLayoutEffect, useRef, type ReactNode, type RefObject } from 'react';
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

// Native modal <dialog>: the browser traps focus, makes the page inert and gives focus back to the opener. `open` is
// the source of truth; a layout effect keeps the element in step, so it never paints open with stale content.
export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    tone = 'primary',
    processing = false,
    confirmDisabled = false,
    initialFocus,
    onConfirm,
    onDismiss,
    onClosed,
    children,
}: ConfirmDialogProps) {
    const dialogRef = useRef<HTMLDialogElement>(null);
    const confirmRef = useRef<HTMLButtonElement>(null);
    const titleId = useId();
    const descriptionId = useId();
    const confirmBlocked = processing || confirmDisabled;

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

    const dismiss = () => {
        if (!processing) onDismiss();
    };

    return (
        <dialog
            ref={dialogRef}
            aria-labelledby={titleId}
            aria-describedby={descriptionId}
            onCancel={(event) => {
                // Esc dismisses, except while the request is on its way.
                event.preventDefault();
                dismiss();
            }}
            // The browser can close it on its own (a repeated Esc): keep the state in step. The close event is queued,
            // so it can arrive after the dialog has been opened again; then it is stale and ignored.
            onClose={(event) => open && !event.currentTarget.open && onDismiss()}
            className="m-auto w-[min(34rem,calc(100%-2rem))] rounded-card bg-white p-0 text-ink backdrop:bg-navy/40"
        >
            <form
                noValidate
                className="flex flex-col gap-4 p-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    if (!confirmBlocked) onConfirm();
                }}
            >
                <h2 id={titleId} className="text-lg font-semibold text-navy">
                    {title}
                </h2>
                <div id={descriptionId} className="text-sm leading-relaxed text-muted">
                    {description}
                </div>
                {children}
                <div className="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <Button variant="secondary" onClick={dismiss} aria-disabled={processing || undefined}>
                        Cancelar
                    </Button>
                    <Button
                        ref={confirmRef}
                        type="submit"
                        variant={tone}
                        aria-disabled={confirmBlocked || undefined}
                        aria-busy={processing || undefined}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </form>
        </dialog>
    );
}
