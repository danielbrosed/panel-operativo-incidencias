import { useForm } from '@inertiajs/react';
import { useRef, type FormEvent } from 'react';
import { PriorityPicker } from '@/components/priority-picker';
import { Button } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { Field } from '@/components/ui/field';
import { Input, Select, Textarea } from '@/components/ui/form-controls';
import type { IncidentIndexProps, IncidentPriorityValue } from '@/types/incident';

interface CreateIncidentFormProps {
    id: string;
    hidden: boolean;
    url: string;
    options: Pick<IncidentIndexProps['options'], 'priorities' | 'operators'>;
}

const FIELD_ORDER = ['title', 'description', 'priority', 'requester_name', 'store', 'assigned_to'] as const;

/** Focuses the first control with an error, in reading order. Control names are the server's field names. */
function focusFirstError(form: HTMLFormElement | null, errors: Record<string, unknown>) {
    const field = FIELD_ORDER.find((name) => name in errors);
    const control = field ? form?.elements.namedItem(field) : null;
    const element = control instanceof RadioNodeList ? control[0] : control;
    if (element instanceof HTMLElement) element.focus();
}

export function CreateIncidentForm({ id, hidden, url, options }: CreateIncidentFormProps) {
    const formRef = useRef<HTMLFormElement>(null);
    const form = useForm({
        title: '',
        description: '',
        priority: '' as IncidentPriorityValue | '',
        requester_name: '',
        store: '',
        assigned_to: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) return;
        form.post(url, {
            errorBag: 'createIncident',
            // An error (an expired session or the rate limit too, on "request") keeps the page and what was typed.
            // Success remounts it on the unfiltered list with the new incident first: form, search and filters start clean.
            preserveState: 'errors',
            preserveScroll: 'errors',
            onError: (errors) => focusFirstError(formRef.current, errors),
        });
    };

    return (
        <Card id={id} hidden={hidden} aria-labelledby={`${id}-title`}>
            <CardTitle id={`${id}-title`}>Abrir incidencia</CardTitle>
            <p className="mt-1 text-sm text-muted">
                Se abre en estado «Abierta» y queda en el historial a tu nombre, con fecha y hora.
            </p>
            {/* The maxLength values mirror the rules in StoreIncidentRequest. */}
            <form ref={formRef} noValidate onSubmit={submit} className="mt-5 grid gap-4 md:grid-cols-2">
                <Field id="incident-title" label="Título" error={form.errors.title} className="md:col-span-2">
                    {(control) => (
                        <Input
                            {...control}
                            name="title"
                            required
                            maxLength={160}
                            placeholder="Ej.: Sin climatización en la sala de ventas"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                    )}
                </Field>
                <Field
                    id="incident-description"
                    label="Descripción"
                    hint="Qué pasa, dónde exactamente y desde cuándo."
                    error={form.errors.description}
                    className="md:col-span-2"
                >
                    {(control) => (
                        <Textarea
                            {...control}
                            name="description"
                            required
                            maxLength={5000}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                        />
                    )}
                </Field>
                <div className="md:col-span-2">
                    <PriorityPicker
                        name="priority"
                        priorities={options.priorities}
                        value={form.data.priority}
                        error={form.errors.priority}
                        onChange={(priority) => form.setData('priority', priority)}
                    />
                </div>
                <Field id="incident-requester" label="Solicitante" error={form.errors.requester_name}>
                    {(control) => (
                        <Input
                            {...control}
                            name="requester_name"
                            required
                            maxLength={120}
                            value={form.data.requester_name}
                            onChange={(event) => form.setData('requester_name', event.target.value)}
                        />
                    )}
                </Field>
                <Field id="incident-store" label="Tienda" optional error={form.errors.store}>
                    {(control) => (
                        <Input
                            {...control}
                            name="store"
                            maxLength={150}
                            placeholder="Marca · Tienda · Dirección"
                            value={form.data.store}
                            onChange={(event) => form.setData('store', event.target.value)}
                        />
                    )}
                </Field>
                <Field id="incident-assignee" label="Responsable" optional error={form.errors.assigned_to}>
                    {(control) => (
                        <Select
                            {...control}
                            name="assigned_to"
                            value={form.data.assigned_to}
                            onChange={(event) => form.setData('assigned_to', event.target.value)}
                        >
                            <option value="">Sin asignar</option>
                            {options.operators.map((operator) => (
                                <option key={operator}>{operator}</option>
                            ))}
                        </Select>
                    )}
                </Field>
                <div className="flex items-end md:justify-end">
                    <Button type="submit" aria-disabled={form.processing || undefined} className="w-full md:w-auto">
                        Abrir incidencia
                    </Button>
                </div>
            </form>
        </Card>
    );
}
