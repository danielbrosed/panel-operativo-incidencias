import { Link } from '@inertiajs/react';
import { IncidentFlags } from '@/components/incident-flags';
import { formatDateTime, formatRelative } from '@/lib/format';
import type { Incident } from '@/types/incident';

// Pieces shared by the table and the card rendering of the list.

// Highlights of the list (besides the "Vencida" tag): a danger bar on the left edge and a tint for blocked rows.
// The bar is inset, so the card's rounded corners never clip it into a curve on the first or last row.
export const overdueEdge = (incident: Incident) =>
    incident.is_overdue &&
    'relative before:absolute before:inset-y-4 before:left-1.5 before:w-1 before:rounded-full before:bg-danger';

export const blockedTint = (incident: Incident) => incident.status.value === 'blocked' && 'bg-status-blocked-bg/60';

export function IncidentHeading({ incident }: { incident: Incident }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <Link href={incident.links.self} className="font-semibold text-navy hover:text-brand-600">
                <span className="font-medium text-muted">#{incident.id}</span> {incident.title}
            </Link>
            {incident.store && <span className="text-xs text-muted">{incident.store}</span>}
            <IncidentFlags incident={incident} />
        </div>
    );
}

export function OpenedAt({ iso }: { iso: string }) {
    return (
        <time dateTime={iso} className="flex flex-col">
            <span className="whitespace-nowrap">{formatDateTime(iso)}</span>
            <span className="text-xs text-muted">{formatRelative(iso)}</span>
        </time>
    );
}

export function Assignee({ name }: { name: string | null }) {
    return name ?? <span className="text-muted italic">Sin asignar</span>;
}

export function HistoryLink({ incident }: { incident: Incident }) {
    return (
        <Link
            href={`${incident.links.self}#historial`}
            className="text-sm font-semibold whitespace-nowrap text-brand-600 underline-offset-4 hover:underline"
        >
            Historial<span className="sr-only"> de la incidencia #{incident.id}</span>
        </Link>
    );
}
