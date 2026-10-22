import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { IconBan, IconCheck, IconCircle, IconEye, type IconProps } from '@/components/ui/icons';
import type { IncidentStatusValue, Option } from '@/types/incident';

/** Visual tone per status. Typed by the value union, so a new status fails to compile until it has one. */
export const statusTones: Record<IncidentStatusValue, { colors: string; Icon: ComponentType<IconProps> }> = {
    open: { colors: 'bg-status-open-bg text-status-open', Icon: IconCircle },
    in_review: { colors: 'bg-status-in-review-bg text-status-in-review', Icon: IconEye },
    blocked: { colors: 'bg-status-blocked-bg text-status-blocked', Icon: IconBan },
    resolved: { colors: 'bg-status-resolved-bg text-status-resolved', Icon: IconCheck },
};

export function StatusBadge({ status }: { status: Option<IncidentStatusValue> }) {
    const { colors, Icon } = statusTones[status.value];

    return (
        <Badge className={colors}>
            <Icon className="size-3.5" />
            {status.label}
        </Badge>
    );
}
