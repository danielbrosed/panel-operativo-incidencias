import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field, FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/form-controls';
import { focusFallback, focusIsLost, isShown } from '@/lib/focus';
import { requestError } from '@/lib/request-error';
import type { Incident, IncidentStatusValue, Transition } from '@/types/incident';

export type StatusChangeTarget = Pick<Incident, 'id' | 'title' | 'status' | 'transitions' | 'links'>;

/** The transition and the incident as it was on screen when it was chosen. */
interface Choice {
    incident: StatusChangeTarget;
    transition: Transition;
}

/** Focus goes to the incident's first status button on screen, or to the page's fallback if its row is gone. */
function focusStatusActions(incidentId: number) {
    if (!focusIsLost()) return;
    const buttons = document.querySelectorAll<HTMLElement>(`[data-status-actions="${incidentId}"] button`);
    const first = [...buttons].find(isShown);
    if (first) first.focus();
    else focusFallback();
}

/**
 * One status form and one confirmation dialog per page, for the buttons of every incident on it (StatusActions).
 * The server redirects back and Inertia refreshes the props, so badges, buttons and counters come from the response.
 */
export function useStatusChange(onScreen: StatusChangeTarget[]) {
    const page = usePage();
    const form = useForm<{
        status: IncidentStatusValue | '';
        expected_status: IncidentStatusValue | '';
        reason: string;
    }>({
        status: '',
        expected_status: '',
        reason: '',
    });
    const { clearErrors } = form;
    const [choice, setChoice] = useState<Choice | null>(null);
    const [confirming, setConfirming] = useState(false);
    const reasonRef = useRef<HTMLTextAreaElement>(null);
    // A change sent without the dialog, until its response is on screen.
    const sentDirectly = useRef<number | null>(null);
    const reasonId = useId();

    const statusOnScreen = choice
        ? onScreen.find((incident) => incident.id === choice.incident.id)?.status.value
        : undefined;

    // "Someone else changed it" or "it already is…" stop being true once the status moves on. The response that brings
    // such an error renders before the form receives it, so the error itself survives this.
    useEffect(() => {
        clearErrors();
    }, [statusOnScreen, clearErrors]);

    // With the new props, before the layout's page-wide fallback runs (effects run children first).
    useEffect(() => {
        if (sentDirectly.current !== null) {
            focusStatusActions(sentDirectly.current);
            sentDirectly.current = null;
        }
    }, [page]);

    const send = (incident: StatusChangeTarget) =>
        form.patch(incident.links.status, {
            errorBag: 'changeStatus',
            preserveScroll: true,
            // Only success closes the dialog: with any error (an expired session included) it stays with the text.
            onSuccess: () => setConfirming(false),
            onError: (errors) => {
                if (errors.reason) reasonRef.current?.focus();
            },
            onFinish: () => {
                sentDirectly.current = null;
            },
        });

    const choose = (incident: StatusChangeTarget, transition: Transition) => {
        if (form.processing) return;
        clearErrors();
        // expected_status is the status on screen: if someone changed it in the meantime, the server rejects it.
        form.setData({ status: transition.to.value, expected_status: incident.status.value, reason: '' });
        setChoice({ incident, transition });
        if (transition.requires_confirmation) {
            setConfirming(true);
        } else {
            sentDirectly.current = incident.id;
            send(incident);
        }
    };

    const requiresReason = choice?.transition.requires_reason ?? false;
    const failedRequest = requestError(form.errors);

    const dialog = (
        <ConfirmDialog
            open={confirming}
            title={choice ? `${choice.transition.action_label} la incidencia #${choice.incident.id}` : ''}
            description={
                choice &&
                `«${choice.incident.title}» pasará de «${choice.incident.status.label}» a ` +
                    `«${choice.transition.to.label}» y el cambio quedará en el historial a nombre de ` +
                    `${page.props.actor.current}.`
            }
            confirmLabel={choice?.transition.action_label ?? ''}
            tone={choice?.transition.destructive ? 'danger' : 'primary'}
            processing={form.processing}
            // Someone else changed it while the dialog was open: the error explains it, the buttons are already new.
            confirmDisabled={choice !== null && choice.incident.status.value !== statusOnScreen}
            initialFocus={requiresReason ? reasonRef : undefined}
            onConfirm={() => choice && send(choice.incident)}
            onDismiss={() => {
                setConfirming(false);
                clearErrors('reason');
            }}
            onClosed={() => choice && focusStatusActions(choice.incident.id)}
        >
            {confirming && (
                <>
                    {form.errors.status && <FieldError role="alert">{form.errors.status}</FieldError>}
                    {/* The page behind the modal is inert, so its toast is not read out: say it here too. */}
                    {failedRequest && <FieldError role="alert">{failedRequest}</FieldError>}
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
                                // Mirrors the rule in ChangeIncidentStatusRequest.
                                maxLength={500}
                                rows={3}
                                value={form.data.reason}
                                onChange={(event) => form.setData('reason', event.target.value)}
                            />
                        )}
                    </Field>
                </>
            )}
        </ConfirmDialog>
    );

    return {
        choose,
        /** The incident whose change is on its way: its buttons show as busy. */
        isProcessing: (incidentId: number) => form.processing && choice?.incident.id === incidentId,
        /** A failed change sent without the dialog (or left after closing it), shown under that incident's buttons. */
        errorFor: (incidentId: number) =>
            !confirming && choice?.incident.id === incidentId ? form.errors.status : undefined,
        dialog,
    };
}
