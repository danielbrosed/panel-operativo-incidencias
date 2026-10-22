<?php

declare(strict_types=1);

namespace App\Diagnostics\Fixes;

use App\Data\Actor;
use App\Diagnostics\Finding;
use App\Enums\IncidentLogAction;
use App\Models\Incident;
use App\Models\IncidentLog;

/**
 * Leaves a flagged_for_review entry on the incident and nothing else: a person decides what the truth is.
 * The panel shows the "Revisión manual" badge while the flag is active.
 */
final readonly class FlagForReview implements Fix
{
    /** @param  string  $why  why it cannot be fixed automatically, in Spanish */
    public function __construct(public string $why) {}

    public function isAutomatic(): bool
    {
        return false;
    }

    public function describe(): string
    {
        return "Revisión manual. {$this->why}";
    }

    public function apply(Incident $incident, Finding $finding): void
    {
        $incident->logs()->create([
            'action' => IncidentLogAction::FlaggedForReview,
            'old_value' => null,
            'new_value' => null,
            'user_name' => Actor::system()->name,
            'metadata' => [
                'check' => $finding->check,
                'evidence_log_id' => $finding->evidenceLogId,
                'detail' => "{$finding->detail} {$this->why}",
            ],
        ]);
    }

    /** The active flag already left for the same finding (same check and evidence log), if any. */
    public static function existingFor(Finding $finding): ?IncidentLog
    {
        return IncidentLog::query()
            ->where('incident_id', $finding->incidentId)
            ->where('action', IncidentLogAction::FlaggedForReview)
            ->active()
            ->orderBy('id')
            ->get()
            ->first(fn (IncidentLog $flag): bool => ($flag->metadata['check'] ?? null) === $finding->check
                && ($flag->metadata['evidence_log_id'] ?? null) === $finding->evidenceLogId);
    }
}
