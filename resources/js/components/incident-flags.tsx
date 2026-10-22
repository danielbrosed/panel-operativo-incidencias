import { Badge } from '@/components/ui/badge';
import { IconClock, IconFlag } from '@/components/ui/icons';
import type { Incident } from '@/types/incident';

/** "Vencida" (response commitment missed) and "Revisión manual" (marked by the diagnostic). */
export function IncidentFlags({ incident }: { incident: Pick<Incident, 'is_overdue' | 'flagged_for_review'> }) {
    if (!incident.is_overdue && !incident.flagged_for_review) {
        return null;
    }

    return (
        <span className="flex flex-wrap gap-1.5">
            {incident.is_overdue && (
                <Badge className="bg-danger-bg text-danger">
                    <IconClock className="size-3.5" />
                    Vencida
                </Badge>
            )}
            {incident.flagged_for_review && (
                <Badge className="border border-navy/25 bg-white text-navy">
                    <IconFlag className="size-3.5" />
                    Revisión manual
                </Badge>
            )}
        </span>
    );
}
