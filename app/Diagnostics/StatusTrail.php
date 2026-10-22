<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Enums\IncidentLogAction;
use App\Models\IncidentLog;
use Illuminate\Database\Eloquent\Builder;

/** An incident's status trail: its active entries whose action is in IncidentLogAction::statusTrail(). */
final class StatusTrail
{
    /** The entry the checks compare with incidents.status: the most recent by created_at, then id. */
    public static function latest(int $incidentId): ?IncidentLog
    {
        return self::of($incidentId)->orderByDesc('created_at')->orderByDesc('id')->first();
    }

    public static function startsWithCreated(int $incidentId): bool
    {
        return self::of($incidentId)->orderBy('created_at')->orderBy('id')->first()?->action === IncidentLogAction::Created;
    }

    /**
     * A log dated after now cannot be followed: an entry appended now sorts before it, so every run would append
     * another one. Compared in SQL against a bound now(), the same conversion the appended entry would go through.
     */
    public static function isFutureDated(int $logId): bool
    {
        return IncidentLog::query()->whereKey($logId)->where('created_at', '>', now())->exists();
    }

    /** @return Builder<IncidentLog> */
    private static function of(int $incidentId): Builder
    {
        return IncidentLog::query()
            ->where('incident_id', $incidentId)
            ->active()
            ->whereIn('action', IncidentLogAction::statusTrailValues());
    }
}
