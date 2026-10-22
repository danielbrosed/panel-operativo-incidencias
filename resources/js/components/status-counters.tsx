import { statusTones } from '@/components/status-badge';
import { cx } from '@/lib/cx';
import type { IncidentStatusValue, Option, StatusCounts } from '@/types/incident';

interface StatusCountersProps {
    statuses: Option<IncidentStatusValue>[];
    counts: StatusCounts;
    active: IncidentStatusValue | null;
    onChange: (status: IncidentStatusValue | null) => void;
}

/**
 * One counter per status, and the quick status filter at the same time: pressing a counter lists that status,
 * pressing the active one again clears it. The counts ignore the status filter (server side), so each number is
 * what pressing it will show.
 */
export function StatusCounters({ statuses, counts, active, onChange }: StatusCountersProps) {
    return (
        <div role="group" aria-label="Filtrar por estado" className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            {statuses.map((status) => {
                const { colors, Icon } = statusTones[status.value];
                const pressed = active === status.value;

                return (
                    <button
                        key={status.value}
                        type="button"
                        aria-pressed={pressed}
                        onClick={() => onChange(pressed ? null : status.value)}
                        className={cx(
                            'flex items-center gap-2.5 rounded-card border-2 bg-white px-3.5 py-3 text-left transition-colors sm:gap-3 sm:px-5',
                            pressed ? 'border-brand-600' : 'border-transparent hover:border-line',
                        )}
                    >
                        <span className={cx('grid size-8 shrink-0 place-items-center rounded-full sm:size-10', colors)}>
                            <Icon className="size-4 sm:size-5" />
                        </span>
                        <span className="flex min-w-0 flex-col">
                            <span className="text-xs font-medium text-muted sm:text-sm">{status.label}</span>
                            <span className="text-2xl leading-tight font-semibold text-navy tabular-nums">
                                {counts[status.value]}
                            </span>
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
