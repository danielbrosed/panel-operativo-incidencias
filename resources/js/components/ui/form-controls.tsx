import type { ComponentProps } from 'react';
import { cx } from '@/lib/cx';
import { IconChevronDown } from '@/components/ui/icons';

// Square on the left, rounded on the right: the field shape of optimaretail.eu.
const control =
    'w-full rounded-r-field border border-control bg-white px-4 text-sm text-ink placeholder:text-muted ' +
    'aria-invalid:border-danger disabled:opacity-60';

export function Input({ className, ...props }: ComponentProps<'input'>) {
    return <input className={cx(control, 'h-10', className)} {...props} />;
}

export function Textarea({ className, ...props }: ComponentProps<'textarea'>) {
    return <textarea className={cx(control, 'min-h-24 py-2.5 leading-relaxed', className)} {...props} />;
}

/** Native select with the brand chevron. `className` goes to the wrapper, which sets the width. */
export function Select({ className, ...props }: ComponentProps<'select'>) {
    return (
        <div className={cx('relative', className)}>
            <select className={cx(control, 'h-10 cursor-pointer appearance-none pr-10')} {...props} />
            <IconChevronDown className="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-muted" />
        </div>
    );
}
