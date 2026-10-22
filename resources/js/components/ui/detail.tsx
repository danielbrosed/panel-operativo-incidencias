import type { ReactNode } from 'react';

/** A label and its value, inside a <dl>. */
export function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-xs font-medium text-muted">{label}</dt>
            <dd>{children}</dd>
        </div>
    );
}
