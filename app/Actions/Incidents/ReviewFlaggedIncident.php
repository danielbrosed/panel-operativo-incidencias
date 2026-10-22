<?php

declare(strict_types=1);

namespace App\Actions\Incidents;

use App\Data\Actor;
use App\Data\ReviewOutcome;
use App\Diagnostics\StatusTrail;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Support\Facades\DB;

final class ReviewFlaggedIncident
{
    /** incident_logs.voided_reason is VARCHAR(255). */
    private const VOIDED_REASON_LENGTH = 255;

    /**
     * A person closes what `incidents:diagnose` flagged: the single allowed step it could not invent is written with
     * their name and reason, and the active flags are voided as reviewed. The status itself never changes.
     */
    public function handle(Incident $incident, Actor $actor, string $reason): ReviewOutcome
    {
        $reason = trim($reason);

        return DB::transaction(function () use ($incident, $actor, $reason): ReviewOutcome {
            $locked = Incident::query()->whereKey($incident->getKey())->lockForUpdate()->firstOrFail();
            $flags = $locked->activeReviewFlags()->orderBy('id')->get();

            if ($flags->isEmpty()) {
                return new ReviewOutcome(voidedFlags: []);
            }

            $latest = StatusTrail::latest($locked->id);
            $logged = $latest === null ? null : IncidentStatus::tryFrom((string) $latest->new_value);
            $whyNoStep = self::whyNoStep($latest, $logged, $locked->status);
            $step = null;

            if ($whyNoStep === null && $logged !== null) {
                $step = $locked->logs()->create([
                    'action' => IncidentLogAction::StatusReconciled,
                    'old_value' => $logged->value,
                    'new_value' => $locked->status->value,
                    'user_name' => $actor->name,
                    'metadata' => ['reason' => $reason, 'reviewed' => true, 'inferred' => false],
                ]);
            }

            $flags->each(fn (IncidentLog $flag) => $flag->void(self::voidReason($actor->name, $reason)));

            return new ReviewOutcome(array_values($flags->all()), $step, $whyNoStep);
        });
    }

    /** What a reviewed flag says in voided_reason. */
    public static function voidReason(string $actorName, string $reason): string
    {
        return "Revisada por {$actorName}: {$reason}";
    }

    /** The longest reason that still fits in voided_reason next to the reviewer's name. */
    public static function maxReasonLength(string $actorName): int
    {
        return self::VOIDED_REASON_LENGTH - mb_strlen(self::voidReason($actorName, ''));
    }

    /** Null when the gap is exactly one allowed transition from the latest status log to the current status. */
    private static function whyNoStep(?IncidentLog $latest, ?IncidentStatus $logged, IncidentStatus $current): ?string
    {
        return match (true) {
            $latest === null, $logged === null => 'no tiene ningún log de estado válido del que partir',
            $logged === $current => sprintf('su último log de estado ya la deja «%s», como está', $current->lowerLabel()),
            ! $logged->canTransitionTo($current) => sprintf(
                'de «%s» a «%s» no hay un único paso permitido',
                $logged->lowerLabel(),
                $current->lowerLabel(),
            ),
            StatusTrail::isFutureDated($latest->id) => sprintf(
                'su último log de estado (#%d) tiene fecha futura y uno añadido ahora quedaría antes que él',
                $latest->id,
            ),
            default => null,
        };
    }
}
