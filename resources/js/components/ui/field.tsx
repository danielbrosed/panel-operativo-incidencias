import type { ComponentProps, ReactNode } from 'react';
import { cx } from '@/lib/cx';

/** Props a control needs to be tied to its label, hint and error. */
export interface ControlProps {
    id: string;
    'aria-invalid': true | undefined;
    'aria-describedby': string | undefined;
}

interface FieldProps {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    optional?: boolean;
    className?: string;
    children: (control: ControlProps) => ReactNode;
}

export function Field({ id, label, hint, error, optional = false, className, children }: FieldProps) {
    const hintId = hint ? `${id}-hint` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    // The error first, so it is what a screen reader reads right after the label.
    const describedBy = [errorId, hintId].filter(Boolean).join(' ') || undefined;

    return (
        <div className={cx('flex flex-col gap-1.5', className)}>
            <label htmlFor={id} className="text-sm font-semibold text-navy">
                {label}
                {optional && <span className="font-normal text-muted"> (opcional)</span>}
            </label>
            {children({ id, 'aria-invalid': error ? true : undefined, 'aria-describedby': describedBy })}
            {hint && (
                <p id={hintId} className="text-xs text-muted">
                    {hint}
                </p>
            )}
            {error && <FieldError id={errorId}>{error}</FieldError>}
        </div>
    );
}

export function FieldError({ className, ...props }: ComponentProps<'p'>) {
    return <p className={cx('text-sm font-medium text-danger', className)} {...props} />;
}
