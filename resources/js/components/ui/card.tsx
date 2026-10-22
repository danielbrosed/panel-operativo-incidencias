import type { ComponentProps } from 'react';
import { cx } from '@/lib/cx';

interface CardProps extends ComponentProps<'section'> {
    /** No inner padding, for content that runs to the edges (a table, a list). */
    flush?: boolean;
}

/** White surface with the brand's 30px radius. Flat on purpose: no shadow. */
export function Card({ flush = false, className, ...props }: CardProps) {
    return (
        <section
            className={cx('rounded-card bg-white', flush ? 'overflow-hidden' : 'p-5 sm:p-6', className)}
            {...props}
        />
    );
}

export function CardTitle({ className, ...props }: ComponentProps<'h2'>) {
    return <h2 className={cx('text-lg font-semibold text-navy', className)} {...props} />;
}
