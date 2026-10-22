import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { CreateIncidentForm } from '@/components/create-incident-form';
import { FilterBar } from '@/components/filter-bar';
import { IncidentList } from '@/components/incident-list';
import { Pagination } from '@/components/pagination';
import { StatusCounters } from '@/components/status-counters';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { IconPlus } from '@/components/ui/icons';
import { useIncidentFilters } from '@/hooks/use-incident-filters';
import { cx } from '@/lib/cx';
import type { IncidentIndexProps } from '@/types/incident';

const CREATE_FORM_ID = 'create-incident';

export default function IncidentsIndex({ incidents, counts, filters, options, urls }: IncidentIndexProps) {
    const filtering = useIncidentFilters(filters, urls.index);
    // Open from the start only when there is nothing yet: the first thing to do is open an incident.
    const [creating, setCreating] = useState(() => incidents.meta.total === 0 && !filtering.isFiltered);

    return (
        <>
            <Head title="Incidencias" />

            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold text-navy sm:text-3xl">Incidencias</h1>
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

            <FilterBar state={filtering} priorities={options.priorities} />

            <Card
                aria-label="Listado de incidencias"
                aria-busy={filtering.loading}
                flush
                className={cx('transition-opacity', filtering.loading && 'opacity-60')}
            >
                {incidents.data.length > 0 ? (
                    <IncidentList incidents={incidents.data} />
                ) : (
                    <EmptyState filtered={filtering.isFiltered} onClear={filtering.clearAll} />
                )}
            </Card>

            <Pagination meta={incidents.meta} />
        </>
    );
}

function EmptyState({ filtered, onClear }: { filtered: boolean; onClear: () => void }) {
    return (
        <div className="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <p className="font-semibold text-navy">
                {filtered ? 'Ninguna incidencia coincide con estos filtros.' : 'Todavía no hay incidencias.'}
            </p>
            {filtered ? (
                <Button variant="link" onClick={onClear}>
                    Quitar filtros
                </Button>
            ) : (
                <p className="text-sm text-muted">Abre la primera con el formulario de arriba.</p>
            )}
        </div>
    );
}
