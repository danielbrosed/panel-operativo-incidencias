import { useForm, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/form-controls';
import { focusFallback, focusIsLost } from '@/lib/focus';
import { requestError } from '@/lib/request-error';
import type { Incident, IncidentStatusValue, Option, Transition } from '@/types/incident';

interface StatusActionsProps {
    incident: Pick<Incident, 'id' | 'title' | 'status' | 'transitions' | 'links'>;
    size?: 'sm' | 'md';
}

/** The transition being confirmed and the status the person saw when choosing it. */
interface Pending {
    transition: Transition;
    from: Option<IncidentStatusValue>;
}

/**
 * One button per transition the server allows (the UI has no copy of the state machine). Transitions with
 * consequences open a confirmation dialog; the rest are sent straight away. The server redirects back and
 * Inertia refreshes the props, so the badge, the buttons and the counters update from the response.
 */
export function StatusActions({ incident, size = 'sm' }: StatusActionsProps) {
    const actor = usePage().props.actor.current;
    const form = useForm<{ status: IncidentStatusValue | ''; expected_status: IncidentStatusValue; reason: string }>({
        status: '',
        expected_status: incident.status.value,
        reason: '',
    });
    const { clearErrors } = form;
    const [pending, setPending] = useState<Pending | null>(null);
    const firstActionRef = useRef<HTMLButtonElement>(null);
    const reasonRef = useRef<HTMLTextAreaElement>(null);
    // A transition sent without the dialog, until its response is on screen.
    const sentDirectly = useRef(false);
    const reasonId = useId();
    const status = incident.status.value;
    const shownStatus = useRef(status);

    // "Someone else changed it" or "it already is…" stop being true once the status moves on.
    useEffect(() => {
        if (shownStatus.current !== status) {
            shownStatus.current = status;
            clearErrors();
        }
    }, [status, clearErrors]);

    // The buttons change with the status, so the one that was used (or the dialog's opener) may be gone. Focus goes to
    // the first action, or to the page's fallback when this copy of the row is the hidden one.
    const keepFocus = useCallback(() => {
        if (!focusIsLost()) return;
        const first = firstActionRef.current;
        if (first?.checkVisibility()) first.focus();
        else focusFallback();
    }, []);

    // With the new props, before the page-wide fallback runs (effects run children first).
    useEffect(() => {
        if (sentDirectly.current) {
            sentDirectly.current = false;
            keepFocus();
        }
    }, [incident, keepFocus]);

    const submit = () =>
        form.patch(incident.links.status, {
            errorBag: 'changeStatus',
            preserveScroll: true,
            // Only success closes the dialog: with any error (an expired session included) it stays with the text.
            onSuccess: () => setPending(null),
            onError: (errors) => {
                if (errors.reason) reasonRef.current?.focus();
            },
            onFinish: () => {
                sentDirectly.current = false;
            },
        });

    const choose = (transition: Transition) => {
        if (form.processing) return;
        form.clearErrors();
        // expected_status is the status on screen: if someone changed it in the meantime, the server rejects it.
        form.setData({ status: transition.to.value, expected_status: incident.status.value, reason: '' });
        if (transition.requires_confirmation) {
            setPending({ transition, from: incident.status });
        } else {
            sentDirectly.current = true;
            submit();
        }
    };

    const requiresReason = pending?.transition.requires_reason ?? false;
    const failedRequest = requestError(form.errors);

    return (
        <div className="flex flex-col gap-2">
            <div className="flex flex-wrap gap-2">
                {incident.transitions.map((transition, index) => (
                    <Button
                        key={transition.to.value}
                        ref={index === 0 ? firstActionRef : undefined}
                        variant="secondary"
                        size={size}
                        aria-disabled={form.processing || undefined}
                        onClick={() => choose(transition)}
                    >
                        {transition.action_label}
                    </Button>
                ))}
            </div>
            {!pending && form.errors.status && <FieldError role="alert">{form.errors.status}</FieldError>}

            <ConfirmDialog
                open={pending !== null}
                title={pending ? `${pending.transition.action_label} la incidencia #${incident.id}` : ''}
                description={
                    pending &&
                    `«${incident.title}» pasará de «${pending.from.label}» a «${pending.transition.to.label}» y el ` +
                        `cambio quedará en el historial a nombre de ${actor}.`
                }
                confirmLabel={pending?.transition.action_label ?? ''}
                tone={pending?.transition.to.value === 'blocked' ? 'danger' : 'primary'}
                processing={form.processing}
                // Someone else changed it while the dialog was open: the error explains it, the buttons are already new.
                confirmDisabled={pending !== null && pending.from.value !== incident.status.value}
                initialFocus={requiresReason ? reasonRef : undefined}
                onConfirm={submit}
                onDismiss={() => {
                    setPending(null);
                    form.clearErrors('reason');
                }}
                onClosed={keepFocus}
            >
                {form.errors.status && <FieldError role="alert">{form.errors.status}</FieldError>}
                {/* The page behind the modal is inert, so its toast is not read out: say it here too. */}
                {failedRequest && <FieldError role="alert">{failedRequest}</FieldError>}
                {pending && (
                    <Field
                        id={reasonId}
                        label={requiresReason ? 'Motivo' : 'Nota'}
                        optional={!requiresReason}
                        hint="Queda en el historial de la incidencia. Máximo 500 caracteres."
                        error={form.errors.reason}
                    >
                        {(control) => (
                            <Textarea
                                {...control}
                                ref={reasonRef}
                                name="reason"
                                required={requiresReason}
                                maxLength={500}
                                rows={3}
                                value={form.data.reason}
                                onChange={(event) => form.setData('reason', event.target.value)}
                            />
                        )}
                    </Field>
                )}
            </ConfirmDialog>
        </div>
    );
}
