import { Link } from '@inertiajs/react';
import { cx } from '@/lib/cx';
import type { Paginated } from '@/types/incident';

const item = 'grid h-9 min-w-9 place-items-center rounded-full px-3 text-sm font-semibold';

/**
 * Laravel's paginator links. The first and last entries are "previous" and "next" (their labels come in English
 * with HTML entities, so they are replaced, never rendered raw). The filters are already in each link's URL.
 */
export function Pagination({ meta }: { meta: Paginated<unknown>['meta'] }) {
    if (meta.total === 0) {
        return null;
    }

    const lastIndex = meta.links.length - 1;

    return (
        <nav aria-label="Paginación" className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted">
                {meta.from}–{meta.to} de {meta.total} incidencias
            </p>
            {meta.last_page > 1 && (
                <ul className="flex flex-wrap justify-center gap-1">
                    {meta.links.map((link, index) => {
                        const label = index === 0 ? 'Anterior' : index === lastIndex ? 'Siguiente' : link.label;

                        return (
                            <li key={index}>
                                {link.url === null || link.active ? (
                                    <span
                                        aria-current={link.active ? 'page' : undefined}
                                        aria-disabled={link.url === null || undefined}
                                        className={cx(item, link.active ? 'bg-navy text-white' : 'text-muted/60')}
                                    >
                                        {label}
                                    </span>
                                ) : (
                                    <Link
                                        href={link.url}
                                        only={['incidents']}
                                        preserveState
                                        preserveScroll
                                        className={cx(item, 'text-navy hover:bg-white hover:text-brand-600')}
                                    >
                                        {label}
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </nav>
    );
}
