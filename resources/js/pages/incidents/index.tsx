import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { CreateIncidentForm } from '@/components/create-incident-form';
import { FilterBar } from '@/components/filter-bar';
import { IncidentList } from '@/components/incident-list';
import { Pagination } from '@/components/pagination';
import { StatusActions } from '@/components/status-actions';
import { StatusCounters } from '@/components/status-counters';
import { Button, buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { IconPlus } from '@/components/ui/icons';
import { PageTitle } from '@/components/ui/page-title';
import { useIncidentFilters } from '@/hooks/use-incident-filters';
import { useStatusChange } from '@/hooks/use-status-change';
import { LOADING_DELAY_CLASS } from '@/lib/app-config';
import { cx } from '@/lib/cx';
import { rememberListUrl } from '@/lib/list-url';
import type { IncidentIndexProps } from '@/types/incident';

const CREATE_FORM_ID = 'create-incident';

export default function IncidentIndex({ incidents, counts, filters, options, urls }: IncidentIndexProps) {
    const filtering = useIncidentFilters(filters, urls.index);
    const statusChange = useStatusChange(incidents.data);
    const { url } = usePage();
    // Open from the start only when there is nothing yet: the first thing to do is open an incident.
    const [creating, setCreating] = useState(() => incidents.meta.total === 0 && !filtering.isFiltered);

    // "Volver al listado" on the detail page comes back to these filters and this page.
    useEffect(() => rememberListUrl(url), [url]);

    return (
        <>
            <Head title="Incidencias" />

            <div className="flex flex-wrap items-center justify-between gap-4">
                <PageTitle>Incidencias</PageTitle>
                <Button
                    variant={creating ? 'secondary' : 'primary'}
                    aria-expanded={creating}
                    aria-controls={CREATE_FORM_ID}
                    onClick={() => setCreating((open) => !open)}
                >
                    {!creating && <IconPlus />}
                    {creating ? 'Cerrar formulario' : 'Abrir incidencia'}
                </Button>
            </div>

            <CreateIncidentForm id={CREATE_FORM_ID} hidden={!creating} url={urls.store} options={options} />

            <StatusCounters
                statuses={options.statuses}
                counts={counts}
                active={filtering.filters.status}
                onChange={filtering.setStatus}
            />

            <FilterBar
                search={filtering.search}
                priority={filtering.filters.priority}
                priorities={options.priorities}
                filtered={filtering.isFiltered}
                onSearchChange={filtering.changeSearch}
                onSearchSubmit={filtering.submitSearch}
                onSearchClear={filtering.clearSearch}
                onPriorityChange={filtering.setPriority}
                onClearAll={filtering.clearAll}
            />

            {/* Takes focus when the focused row or button disappears (see useFocusFallback). Dimmed only when the
                response takes a moment, like the progress bar. */}
            <Card
                aria-label="Listado de incidencias"
                aria-busy={filtering.loading}
                data-focus-fallback
                tabIndex={-1}
                flush
                className={cx('transition-opacity', filtering.loading && `opacity-60 ${LOADING_DELAY_CLASS}`)}
            >
                {incidents.data.length > 0 ? (
                    <IncidentList
                        incidents={incidents.data}
                        actions={(incident) => (
                            <StatusActions
                                incident={incident}
                                busy={statusChange.isProcessing(incident.id)}
                                error={statusChange.errorFor(incident.id)}
                                onChoose={statusChange.choose}
                            />
                        )}
                    />
                ) : (
                    <EmptyList
                        total={incidents.meta.total}
                        firstPage={incidents.links.first ?? urls.index}
                        filtered={filtering.isFiltered}
                        onClear={filtering.clearAll}
                    />
                )}
            </Card>

            <Pagination meta={incidents.meta} />

            {statusChange.dialog}
        </>
    );
}

interface EmptyListProps {
    total: number;
    /** Page 1 with the same filters. */
    firstPage: string;
    filtered: boolean;
    onClear: () => void;
}

function EmptyList({ total, firstPage, filtered, onClear }: EmptyListProps) {
    // A page past the end (an old link, a list that shrank): there are incidents, just not on this page.
    if (total > 0) {
        return (
            <EmptyMessage title="Esta página no tiene incidencias.">
                <Link
                    href={firstPage}
                    only={['incidents']}
                    preserveState
                    preserveScroll
                    className={buttonClasses('link')}
                >
                    Ir a la primera página
                </Link>
            </EmptyMessage>
        );
    }

    if (filtered) {
        return (
            <EmptyMessage title="Ninguna incidencia coincide con estos filtros.">
                <Button variant="link" onClick={onClear}>
                    Quitar filtros
                </Button>
            </EmptyMessage>
        );
    }

    return (
        <EmptyMessage title="Todavía no hay incidencias.">
            <p className="text-sm text-muted">Abre la primera con el formulario de arriba.</p>
        </EmptyMessage>
    );
}

function EmptyMessage({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <p className="font-semibold text-navy">{title}</p>
            {children}
        </div>
    );
}
