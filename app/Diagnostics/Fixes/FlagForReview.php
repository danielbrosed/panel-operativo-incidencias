<?php

declare(strict_types=1);

namespace App\Diagnostics\Fixes;

use App\Data\Actor;
use App\Diagnostics\Finding;
use App\Enums\IncidentLogAction;
use App\Models\Incident;
use App\Models\IncidentLog;

/**
 * Leaves a flagged_for_review entry and nothing else: a person decides what the truth is. The panel shows the
 * "Revisión manual" badge while it is active; `incidents:review` voids it, and the voided flag records the review.
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

    public function appliedMessage(): string
    {
        return 'marcada para revisión manual';
    }

    public function apply(Incident $incident, Finding $finding): void
    {
        $incident->logs()->create([
            'action' => IncidentLogAction::FlaggedForReview,
            'old_value' => null,
            'new_value' => null,
            'user_name' => Actor::system()->name,
            'metadata' => [
                'check' => $finding->checkKey,
                'evidence_log_id' => $finding->evidenceLogId,
                'detail' => "{$finding->detail} {$this->why}",
            ],
        ]);
    }

    /**
     * The flag already left for the same finding, an active one before a voided one: either way it is never flagged
     * again. Same finding means same check, evidence log and detail; the detail names the statuses it saw, so a
     * review covers that mismatch and not a different one that shows up later on the same log.
     */
    public static function existingFor(Finding $finding): ?IncidentLog
    {
        $flags = IncidentLog::query()
            ->where('incident_id', $finding->incidentId)
            ->reviewFlags()
            ->orderBy('id')
            ->get()
            ->filter(fn (IncidentLog $flag): bool => ($flag->metadata['check'] ?? null) === $finding->checkKey
                && ($flag->metadata['evidence_log_id'] ?? null) === $finding->evidenceLogId
                && is_string($flag->metadata['detail'] ?? null)
                && str_starts_with($flag->metadata['detail'], "{$finding->detail} "));

        return $flags->first(fn (IncidentLog $flag): bool => ! $flag->isVoided()) ?? $flags->last();
    }
}
