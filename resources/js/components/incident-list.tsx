import type { ReactNode } from 'react';
import { Assignee, blockedTint, HistoryLink, IncidentHeading, overdueEdge } from '@/components/incident-row-parts';
import { PriorityBadge } from '@/components/priority-badge';
import { StatusBadge } from '@/components/status-badge';
import { DateWithRelative } from '@/components/ui/date-with-relative';
import { Detail } from '@/components/ui/detail';
import { cx } from '@/lib/cx';
import type { Incident } from '@/types/incident';

const cell = 'px-3 py-4';
const COLUMNS = ['Incidencia', 'Prioridad', 'Estado', 'Fecha de apertura', 'Solicitante', 'Responsable', 'Acciones'];

interface IncidentListProps {
    incidents: Incident[];
    /** The status buttons of a row: the page owns the change itself (useStatusChange). */
    actions: (incident: Incident) => ReactNode;
}

/**
 * A table from xl up (seven columns need ~1150px) and stacked cards below, switched by CSS only: the hidden one is
 * display:none, so it is out of the accessibility tree and the tab order.
 */
export function IncidentList({ incidents, actions }: IncidentListProps) {
    return (
        <>
            {/* Scrolls sideways rather than being clipped by the card if a long word ever makes it wider. */}
            <div className="hidden overflow-x-auto xl:block">
                <table className="w-full text-left text-sm">
                    <caption className="sr-only">Incidencias</caption>
                    <thead className="border-b border-line">
                        <tr>
                            {COLUMNS.map((column) => (
                                <th key={column} scope="col" className="px-3 py-3 eyebrow first:pl-6 last:pr-6">
                                    {column}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-line">
                        {incidents.map((incident) => (
                            <tr key={incident.id} className={cx('align-top', blockedTint(incident))}>
                                <td className={cx('max-w-sm min-w-60 py-4 pr-3 pl-6', overdueEdge(incident))}>
                                    <IncidentHeading incident={incident} />
                                </td>
                                <td className={cell}>
                                    <PriorityBadge priority={incident.priority} />
                                </td>
                                <td className={cell}>
                                    <StatusBadge status={incident.status} />
                                </td>
                                <td className={cell}>
                                    <DateWithRelative iso={incident.created_at} stacked />
                                </td>
                                <td className={cell}>{incident.requester_name}</td>
                                <td className={cell}>
                                    <Assignee name={incident.assigned_to} />
                                </td>
                                <td className="py-4 pr-6 pl-3">
                                    <div className="flex min-w-48 flex-col items-start gap-2">
                                        {actions(incident)}
                                        <HistoryLink incident={incident} />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <ul className="divide-y divide-line xl:hidden">
                {incidents.map((incident) => (
                    <li
                        key={incident.id}
                        className={cx('flex flex-col gap-3 p-4 sm:p-5', overdueEdge(incident), blockedTint(incident))}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <IncidentHeading incident={incident} />
                            <StatusBadge status={incident.status} />
                        </div>
                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <Detail label="Prioridad">
                                <PriorityBadge priority={incident.priority} />
                            </Detail>
                            <Detail label="Fecha de apertura">
                                <DateWithRelative iso={incident.created_at} stacked />
                            </Detail>
                            <Detail label="Solicitante">{incident.requester_name}</Detail>
                            <Detail label="Responsable">
                                <Assignee name={incident.assigned_to} />
                            </Detail>
                        </dl>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            {actions(incident)}
                            <HistoryLink incident={incident} />
                        </div>
                    </li>
                ))}
            </ul>
        </>
    );
}
