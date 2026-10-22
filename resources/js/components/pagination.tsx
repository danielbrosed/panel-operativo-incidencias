import { Link } from '@inertiajs/react';
import { cx } from '@/lib/cx';
import type { Paginated } from '@/types/incident';

type PageLink = Paginated<unknown>['meta']['links'][number];

const item = 'grid h-9 min-w-9 place-items-center rounded-full px-3 text-sm font-semibold';

/** Laravel's paginator links, "Anterior" first and "Siguiente" last. The filters are already in each link's URL. */
export function Pagination({ meta }: { meta: Paginated<unknown>['meta'] }) {
    if (meta.total === 0) {
        return null;
    }

    const lastIndex = meta.links.length - 1;

    return (
        <nav aria-label="Paginación" className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted">
                {meta.from === null
                    ? `${meta.total} incidencias en total`
                    : `${meta.from}–${meta.to} de ${meta.total} incidencias`}
            </p>
            {meta.last_page > 1 && (
                <ul className="flex flex-wrap justify-center gap-1">
                    {meta.links.map((link, index) => (
                        <li key={index}>
                            <PageItem link={link} edge={index === 0 || index === lastIndex} />
                        </li>
                    ))}
                </ul>
            )}
        </nav>
    );
}

interface PageItemProps {
    link: PageLink;
    /** "Anterior" or "Siguiente". */
    edge: boolean;
}

function PageItem({ link, edge }: PageItemProps) {
    if (link.active) {
        return (
            <span aria-current="page" className={cx(item, 'bg-navy text-white')}>
                {link.label}
            </span>
        );
    }

    if (link.url !== null) {
        return (
            <Link
                href={link.url}
                only={['incidents']}
                preserveState
                preserveScroll
                className={cx(item, 'text-navy hover:bg-white hover:text-brand-600')}
            >
                {link.label}
            </Link>
        );
    }

    // "Anterior" on the first page or "Siguiente" on the last: a link without a target, announced as unavailable.
    if (edge) {
        return (
            <a role="link" aria-disabled="true" className={cx(item, 'text-muted/60')}>
                {link.label}
            </a>
        );
    }

    // The paginator's "..." between distant pages.
    return <span className={cx(item, 'text-muted')}>{link.label}</span>;
}
