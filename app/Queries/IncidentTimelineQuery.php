<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;

final class IncidentTimelineQuery
{
    /**
     * Status changes and comments of one incident in a single chronological list (oldest first).
     * Voided log entries are kept: the UI shows them struck through with their reason.
     *
     * @return list<IncidentLog|IncidentComment>
     */
    public function for(Incident $incident): array
    {
        $logs = $incident->logs()->orderBy('created_at')->orderBy('id')->get();
        $comments = $incident->comments()->orderBy('created_at')->orderBy('id')->get();

        $entries = [...$logs->all(), ...$comments->all()];

        // Same second: the log goes first (a status change usually prompts the comment), then by id.
        usort($entries, fn (IncidentLog|IncidentComment $a, IncidentLog|IncidentComment $b): int => [
            $a->created_at->getTimestamp(), $a instanceof IncidentLog ? 0 : 1, $a->id,
        ] <=> [
            $b->created_at->getTimestamp(), $b instanceof IncidentLog ? 0 : 1, $b->id,
        ]);

        return $entries;
    }
}
