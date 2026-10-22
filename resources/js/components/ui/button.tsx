import type { ComponentProps } from 'react';
import { cx } from '@/lib/cx';

export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'link';
type ButtonSize = 'sm' | 'md';

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-brand-600 text-white hover:bg-brand-strong',
    secondary: 'border border-line bg-white text-navy hover:border-brand-600 hover:text-brand-600',
    danger: 'bg-danger text-white hover:bg-danger/85',
    link: 'text-brand-600 underline-offset-4 hover:text-brand-strong hover:underline',
};

const sizes: Record<ButtonSize, string> = {
    sm: 'h-8 px-3.5 text-xs',
    md: 'h-10 px-5 text-sm',
};

/**
 * Pill button classes, also used by links that look like buttons. Busy buttons use aria-disabled instead of
 * `disabled`: disabling the focused button would drop keyboard focus to the page.
 */
export function buttonClasses(variant: ButtonVariant = 'primary', size: ButtonSize = 'md', className?: string): string {
    return cx(
        'inline-flex items-center justify-center gap-2 rounded-full font-semibold whitespace-nowrap transition-colors',
        'disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:cursor-not-allowed aria-disabled:opacity-60',
        variant !== 'link' && sizes[size],
        variants[variant],
        className,
    );
}

interface ButtonProps extends ComponentProps<'button'> {
    variant?: ButtonVariant;
    size?: ButtonSize;
}

export function Button({ variant, size, className, type = 'button', ...props }: ButtonProps) {
    return <button type={type} className={buttonClasses(variant, size, className)} {...props} />;
}
