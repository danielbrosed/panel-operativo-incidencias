import { PriorityDot } from '@/components/priority-badge';
import { FieldError } from '@/components/ui/field';
import { cx } from '@/lib/cx';
import type { IncidentPriorityValue, PriorityOption } from '@/types/incident';

interface PriorityPickerProps {
    name: string;
    priorities: PriorityOption[];
    value: IncidentPriorityValue | '';
    error?: string;
    onChange: (value: IncidentPriorityValue) => void;
}

/** Radio group shown as three pills, each with its impact and response commitment (Optima Retail's codes). */
export function PriorityPicker({ name, priorities, value, error, onChange }: PriorityPickerProps) {
    const errorId = `${name}-error`;

    return (
        <fieldset className="flex flex-col gap-1.5">
            <legend className="mb-1.5 text-sm font-semibold text-navy">Prioridad</legend>
            <div className="grid gap-2 md:grid-cols-3">
                {priorities.map((priority) => (
                    <label
                        key={priority.value}
                        className={cx(
                            'flex cursor-pointer items-center gap-3 rounded-card border px-4 py-2.5 transition-colors',
                            value === priority.value
                                ? 'border-brand-600 bg-brand-50'
                                : cx('bg-white hover:border-muted', error ? 'border-danger' : 'border-control'),
                        )}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={priority.value}
                            checked={value === priority.value}
                            onChange={() => onChange(priority.value)}
                            aria-invalid={error ? true : undefined}
                            aria-describedby={error ? errorId : undefined}
                            className="size-4 shrink-0 accent-brand-600"
                        />
                        <span className="flex flex-col">
                            <span className="flex items-center gap-2 text-sm font-semibold text-navy">
                                <PriorityDot priority={priority.value} />
                                {priority.label}
                            </span>
                            <span className="text-xs text-muted">
                                {priority.impact} · respuesta en {priority.sla_hours} h
                            </span>
                        </span>
                    </label>
                ))}
            </div>
            {error && <FieldError id={errorId}>{error}</FieldError>}
        </fieldset>
    );
}
