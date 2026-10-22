import type { ReactNode } from 'react';
import { cx } from '@/lib/cx';

/** Small pill. Colour always comes with a text (and usually an icon): never colour alone. */
export function Badge({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <span
            className={cx(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap',
                className,
            )}
        >
            {children}
        </span>
    );
}
