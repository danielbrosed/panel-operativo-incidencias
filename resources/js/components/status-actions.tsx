import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/form-controls';
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
    const [pending, setPending] = useState<Pending | null>(null);
    const [completed, setCompleted] = useState(0);
    const rootRef = useRef<HTMLDivElement>(null);
    const firstActionRef = useRef<HTMLButtonElement>(null);
    const reasonRef = useRef<HTMLTextAreaElement>(null);
    const reasonId = useId();

    // A transition replaces the buttons, so the one that was used is gone and focus is left on the page body or on
    // the closed dialog. Move it to the first new action so a keyboard user keeps their place.
    useEffect(() => {
        const active = document.activeElement;
        if (completed > 0 && (active === document.body || rootRef.current?.contains(active))) {
            firstActionRef.current?.focus();
        }
    }, [completed]);

    const submit = () =>
        form.patch(incident.links.status, {
            errorBag: 'changeStatus',
            preserveScroll: true,
            onSuccess: () => {
                setPending(null);
                setCompleted((count) => count + 1);
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
            submit();
        }
    };

    const requiresReason = pending?.transition.requires_reason ?? false;

    return (
        <div ref={rootRef} className="flex flex-col gap-2">
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
            >
                {form.errors.status && <FieldError role="alert">{form.errors.status}</FieldError>}
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
