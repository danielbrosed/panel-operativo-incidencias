import { cx } from '@/lib/cx';
import { formatDateTime, formatRelative } from '@/lib/format';

interface DateWithRelativeProps {
    iso: string;
    /** On two lines, as in the list; on one with the relative time in brackets otherwise. */
    stacked?: boolean;
    /** A missed deadline. */
    danger?: boolean;
}

/** "22 oct 2026, 13:45" and how far it is from now: "hace 2 h". */
export function DateWithRelative({ iso, stacked = false, danger = false }: DateWithRelativeProps) {
    const relative = formatRelative(iso);

    return (
        <time dateTime={iso} className={cx(stacked && 'flex flex-col', danger && 'font-semibold text-danger')}>
            <span className={stacked ? 'whitespace-nowrap' : undefined}>{formatDateTime(iso)}</span>{' '}
            <span className={cx(stacked && 'text-xs', !danger && 'text-muted')}>
                {stacked ? relative : `(${relative})`}
            </span>
        </time>
    );
}
