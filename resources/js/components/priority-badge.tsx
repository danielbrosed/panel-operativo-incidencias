import { Badge } from '@/components/ui/badge';
import { cx } from '@/lib/cx';
import type { IncidentPriorityValue, Option } from '@/types/incident';

export const priorityDots: Record<IncidentPriorityValue, string> = {
    low: 'bg-priority-low',
    medium: 'bg-priority-medium',
    high: 'bg-priority-high',
};

export function PriorityDot({ priority }: { priority: IncidentPriorityValue }) {
    return <span aria-hidden="true" className={cx('size-2 shrink-0 rounded-full', priorityDots[priority])} />;
}

/** Outlined, unlike the filled status badge, so the two never read as the same kind of thing. */
export function PriorityBadge({ priority }: { priority: Option<IncidentPriorityValue> }) {
    return (
        <Badge className="border border-line bg-white text-ink">
            <PriorityDot priority={priority.value} />
            {priority.label}
        </Badge>
    );
}
