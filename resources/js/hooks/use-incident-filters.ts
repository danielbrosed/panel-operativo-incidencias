import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { IncidentFilters } from '@/types/incident';

const SEARCH_DEBOUNCE_MS = 300;

/** Query string for a set of filters: no empty values, and no page (a new filter starts at page 1). */
function toQuery(filters: IncidentFilters): Record<string, string> {
    const query: Record<string, string> = {};
    for (const [key, value] of Object.entries(filters)) {
        if (value !== null && value.trim() !== '') {
            query[key] = value.trim();
        }
    }

    return query;
}

/**
 * The only place that fires filter visits. The URL is the source of truth for the filters: each change is a
 * partial Inertia reload of this same page (list, counters and the filters echoed by the server) that replaces
 * the history entry, so there is no full page load and no client-side copy of the data.
 *
 * Local state is limited to two things:
 *  - the search text, initialised once from the URL and never overwritten by a response (a slow response would
 *    otherwise clobber what the person is still typing);
 *  - the filters of the visit in flight, shown straight away so a control does not snap back while it loads.
 */
export function useIncidentFilters(serverFilters: IncidentFilters, indexUrl: string) {
    const [search, setSearch] = useState(serverFilters.q ?? '');
    const [pending, setPending] = useState<IncidentFilters | null>(null);
    const debounce = useRef<number | undefined>(undefined);
    const filters = pending ?? serverFilters;

    // A search still waiting for its debounce must not fire after leaving the page.
    useEffect(() => () => window.clearTimeout(debounce.current), []);

    const visit = (changes: Partial<IncidentFilters>) => {
        window.clearTimeout(debounce.current);
        const next = { ...filters, q: search, ...changes };

        router.get(indexUrl, toQuery(next), {
            only: ['incidents', 'counts', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            // A newer visit cancels this one, and its onStart runs after this onFinish.
            onStart: () => setPending(next),
            onFinish: () => setPending(null),
        });
    };

    return {
        filters,
        search,
        loading: pending !== null,
        isFiltered: filters.status !== null || filters.priority !== null || (filters.q ?? '') !== '',
        setStatus: (status: IncidentFilters['status']) => visit({ status }),
        setPriority: (priority: IncidentFilters['priority']) => visit({ priority }),
        changeSearch: (q: string) => {
            setSearch(q);
            window.clearTimeout(debounce.current);
            debounce.current = window.setTimeout(() => visit({ q }), SEARCH_DEBOUNCE_MS);
        },
        submitSearch: () => visit({}),
        clearSearch: () => {
            setSearch('');
            visit({ q: null });
        },
        clearAll: () => {
            setSearch('');
            visit({ status: null, priority: null, q: null });
        },
    };
}

export type IncidentFiltersState = ReturnType<typeof useIncidentFilters>;
