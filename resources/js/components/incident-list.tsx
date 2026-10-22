import type { ReactNode } from 'react';
import {
    Assignee,
    blockedTint,
    HistoryLink,
    IncidentHeading,
    OpenedAt,
    overdueEdge,
} from '@/components/incident-cells';
import { PriorityBadge } from '@/components/priority-badge';
import { StatusActions } from '@/components/status-actions';
import { StatusBadge } from '@/components/status-badge';
import { cx } from '@/lib/cx';
import type { Incident } from '@/types/incident';

const cell = 'px-3 py-4';
const COLUMNS = ['Incidencia', 'Prioridad', 'Estado', 'Fecha de apertura', 'Solicitante', 'Responsable', 'Acciones'];

/**
 * A table from xl up (seven columns need ~1150px) and stacked cards below. Two renderings of the same rows,
 * switched by CSS only; the hidden one is display:none, so it is out of the accessibility tree and the tab order.
 * The table scrolls sideways rather than being clipped by the card if a long word ever makes it wider.
 */
export function IncidentList({ incidents }: { incidents: Incident[] }) {
    return (
        <>
            <div className="hidden overflow-x-auto xl:block">
                <table className="w-full text-left text-sm">
                    <caption className="sr-only">Incidencias</caption>
                    <thead className="border-b border-line">
                        <tr>
                            {COLUMNS.map((column) => (
                                <th
                                    key={column}
                                    scope="col"
                                    className="px-3 py-3 text-xs font-semibold tracking-wide text-muted uppercase first:pl-6 last:pr-6"
                                >
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
                                    <OpenedAt iso={incident.created_at} />
                                </td>
                                <td className={cell}>{incident.requester_name}</td>
                                <td className={cell}>
                                    <Assignee name={incident.assigned_to} />
                                </td>
                                <td className="py-4 pr-6 pl-3">
                                    <div className="flex min-w-48 flex-col items-start gap-2">
                                        <StatusActions incident={incident} />
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
                            <CardDetail label="Prioridad">
                                <PriorityBadge priority={incident.priority} />
                            </CardDetail>
                            <CardDetail label="Fecha de apertura">
                                <OpenedAt iso={incident.created_at} />
                            </CardDetail>
                            <CardDetail label="Solicitante">{incident.requester_name}</CardDetail>
                            <CardDetail label="Responsable">
                                <Assignee name={incident.assigned_to} />
                            </CardDetail>
                        </dl>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <StatusActions incident={incident} />
                            <HistoryLink incident={incident} />
                        </div>
                    </li>
                ))}
            </ul>
        </>
    );
}

function CardDetail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-xs font-medium text-muted">{label}</dt>
            <dd>{children}</dd>
        </div>
    );
}
