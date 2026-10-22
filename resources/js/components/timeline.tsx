import { Badge } from '@/components/ui/badge';
import { cx } from '@/lib/cx';
import { formatDateTime, formatRelative } from '@/lib/format';
import type { TimelineCommentEntry, TimelineEntry, TimelineLogEntry } from '@/types/incident';

/** Status trail and comments in one list, oldest first, as the server sends them. */
export function Timeline({ entries }: { entries: TimelineEntry[] }) {
    return (
        <ol className="ml-1.5 flex flex-col gap-6 border-l-2 border-line">
            {entries.map((entry) => (
                <li key={`${entry.type}-${entry.id}`} className="relative pl-6">
                    <span
                        aria-hidden="true"
                        className={cx(
                            'absolute top-1.5 -left-1.75 size-3 rounded-full ring-4 ring-white',
                            entry.type === 'comment' ? 'border-2 border-navy bg-white' : 'bg-brand',
                        )}
                    />
                    {entry.type === 'log' ? <LogEntry entry={entry} /> : <CommentEntry entry={entry} />}
                </li>
            ))}
        </ol>
    );
}

function LogEntry({ entry }: { entry: TimelineLogEntry }) {
    // "Abierta → En revisión" when the entry records a status move; the action name otherwise (e.g. a review mark).
    const move = entry.old_label && entry.new_label ? `${entry.old_label} → ${entry.new_label}` : null;
    const Heading = entry.voided ? 's' : 'span';

    return (
        <div className="flex flex-col gap-1">
            {move && <span className="eyebrow">{entry.action.label}</span>}
            <p className="flex flex-wrap items-center gap-2">
                <Heading className={cx('font-semibold', entry.voided ? 'text-muted' : 'text-navy')}>
                    {move ?? entry.action.label}
                </Heading>
                {entry.inferred && <Badge className="bg-brand-50 text-brand-600">Inferido por el diagnóstico</Badge>}
            </p>
            <Byline name={entry.user_name} iso={entry.created_at} />
            {entry.reason && (
                <blockquote className="mt-1 border-l-2 border-line pl-3 text-sm whitespace-pre-line text-ink">
                    «{entry.reason}»
                </blockquote>
            )}
            {entry.detail && <p className="text-sm text-muted">{entry.detail}</p>}
            {entry.voided && <p className="text-sm font-medium text-danger">Anulado: {entry.voided.reason}</p>}
        </div>
    );
}

function CommentEntry({ entry }: { entry: TimelineCommentEntry }) {
    return (
        <div className="flex flex-col gap-1">
            <span className="eyebrow">Comentario</span>
            <Byline name={entry.author_name} iso={entry.created_at} />
            <p className="mt-1 rounded-r-field bg-surface px-4 py-3 text-sm leading-relaxed whitespace-pre-line text-ink">
                {entry.body}
            </p>
        </div>
    );
}

function Byline({ name, iso }: { name: string; iso: string }) {
    return (
        <p className="text-sm text-muted">
            <span className="font-medium text-ink">{name}</span> ·{' '}
            <time dateTime={iso} title={formatRelative(iso)}>
                {formatDateTime(iso)}
            </time>
        </p>
    );
}
