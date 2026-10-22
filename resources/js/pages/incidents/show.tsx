import { Head, Link } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CommentForm } from '@/components/comment-form';
import { IncidentFlags } from '@/components/incident-flags';
import { Assignee } from '@/components/incident-row-parts';
import { PriorityBadge } from '@/components/priority-badge';
import { StatusActions } from '@/components/status-actions';
import { StatusBadge } from '@/components/status-badge';
import { Timeline } from '@/components/timeline';
import { buttonClasses } from '@/components/ui/button';
import { Card, CardTitle } from '@/components/ui/card';
import { DateWithRelative } from '@/components/ui/date-with-relative';
import { Detail } from '@/components/ui/detail';
import { IconArrowLeft } from '@/components/ui/icons';
import { PageTitle } from '@/components/ui/page-title';
import { useStatusChange } from '@/hooks/use-status-change';
import { lastListUrl } from '@/lib/list-url';
import type { IncidentShowProps } from '@/types/incident';

export default function IncidentShow({ incident, timeline, urls }: IncidentShowProps) {
    const [listUrl] = useState(() => lastListUrl(urls.index));
    const statusChange = useStatusChange([incident]);

    return (
        <>
            <Head title={`#${incident.id} ${incident.title}`} />

            <Link href={listUrl} className={buttonClasses('link', 'md', 'self-start text-sm')}>
                <IconArrowLeft />
                Volver al listado
            </Link>

            <div className="flex flex-col gap-3">
                <p className="text-sm font-semibold text-muted">Incidencia #{incident.id}</p>
                <PageTitle>{incident.title}</PageTitle>
                <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge status={incident.status} />
                    <PriorityBadge priority={incident.priority} />
                    <IncidentFlags incident={incident} />
                </div>
            </div>

            {/* Data and actions first in reading order (top on mobile); on desktop they sit in the right column. */}
            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div className="flex flex-col gap-6 lg:col-start-2 lg:row-start-1">
                    <Card aria-labelledby="status-title">
                        <CardTitle id="status-title" className="mb-4">
                            Cambiar estado
                        </CardTitle>
                        <StatusActions
                            incident={incident}
                            size="md"
                            busy={statusChange.isProcessing(incident.id)}
                            error={statusChange.errorFor(incident.id)}
                            onChoose={statusChange.choose}
                        />
                    </Card>
                    <Card aria-labelledby="details-title">
                        <CardTitle id="details-title" className="mb-4">
                            Datos
                        </CardTitle>
                        <dl className="flex flex-col gap-4 text-sm">
                            <Detail label="Tienda">{incident.store ?? <Muted>Sin indicar</Muted>}</Detail>
                            <Detail label="Solicitante">{incident.requester_name}</Detail>
                            <Detail label="Responsable">
                                <Assignee name={incident.assigned_to} />
                            </Detail>
                            <Detail label="Abierta el">
                                <DateWithRelative iso={incident.created_at} />
                            </Detail>
                            <Detail label="Compromiso de respuesta">
                                <DateWithRelative iso={incident.due_at} danger={incident.is_overdue} />
                                <span className="block text-xs text-muted">
                                    {incident.priority.label} · {incident.priority.impact}
                                </span>
                            </Detail>
                        </dl>
                    </Card>
                </div>

                <div className="flex min-w-0 flex-col gap-6 lg:row-start-1">
                    <Card aria-labelledby="description-title">
                        <CardTitle id="description-title">Descripción</CardTitle>
                        <p className="mt-3 leading-relaxed whitespace-pre-line">{incident.description}</p>
                    </Card>
                    <Card id="historial" aria-labelledby="history-title" className="scroll-mt-6">
                        <CardTitle id="history-title" className="mb-5">
                            Historial
                        </CardTitle>
                        <Timeline entries={timeline} />
                        <div className="mt-8 border-t border-line pt-6">
                            <CommentForm url={incident.links.comments} />
                        </div>
                    </Card>
                </div>
            </div>

            {statusChange.dialog}
        </>
    );
}

function Muted({ children }: { children: ReactNode }) {
    return <span className="text-muted italic">{children}</span>;
}
