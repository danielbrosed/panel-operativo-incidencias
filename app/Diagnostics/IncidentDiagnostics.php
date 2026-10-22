<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Checks\DuplicateOrIncoherentLog;
use App\Diagnostics\Checks\ResolvedWithoutResolutionLog;
use App\Diagnostics\Checks\SqlCheck;
use App\Diagnostics\Checks\StatusDiffersFromLastLog;
use App\Diagnostics\Fixes\FlagForReview;
use App\Diagnostics\Fixes\ReconcileStatus;
use App\Models\Incident;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Finds inconsistencies between incidents and their status trail and plans what to do with each one, following
 * RULE. Audit rows are never deleted or backdated and incidents.status is never changed from here.
 */
final class IncidentDiagnostics
{
    /** The correction rule. The single place it is written; every report prints it. */
    public const RULE = 'Solo se corrige automáticamente cuando (1) la corrección no cambia el estado de la incidencia, '.
        '(2) no hay que inventar fecha, autor ni motivo y (3) el hueco se explica con una única transición '.
        'permitida que no exige motivo. Todo lo demás se marca para revisión manual. Ningún log se borra ni se atrasa.';

    /** @var list<SqlCheck> */
    private readonly array $checks;

    public function __construct()
    {
        $this->checks = [
            new ResolvedWithoutResolutionLog,
            new StatusDiffersFromLastLog,
            new DuplicateOrIncoherentLog(config()->integer('incidents.diagnostics.duplicate_window_seconds')),
        ];
    }

    /** @return list<SqlCheck> */
    public function checks(): array
    {
        return $this->checks;
    }

    /** @return list<PlannedAction> */
    public function plan(): array
    {
        return $this->findings();
    }

    /**
     * Applies one planned action in its own transaction. The incident row is locked and the finding re-checked under
     * the lock, because someone may have changed the incident, or flagged or reviewed it, since the plan was made.
     */
    public function apply(PlannedAction $planned): FixOutcome
    {
        return DB::transaction(function () use ($planned): FixOutcome {
            $incident = Incident::query()->whereKey($planned->finding->incidentId)->lockForUpdate()->first();

            // Loose comparison: the same finding means equal check, incident, evidence, detail and fix.
            $current = $incident === null ? null : Arr::first(
                $this->findings($incident->id),
                fn (PlannedAction $action): bool => $action->finding == $planned->finding,
            );

            if ($incident === null || $current === null) {
                return FixOutcome::NoLongerTrue;
            }

            if ($current->flag !== null) {
                return $current->isFlagReviewed() ? FixOutcome::AlreadyReviewed : FixOutcome::AlreadyFlagged;
            }

            $current->finding->fix->apply($incident, $current->finding);

            return FixOutcome::Applied;
        });
    }

    /**
     * Every finding of every check, or only one incident's, with the flag already left for it. A reconcile the trail
     * cannot back is downgraded to a flag here, so the re-check under the lock applies the same downgrades.
     *
     * @return list<PlannedAction>
     */
    private function findings(?int $incidentId = null): array
    {
        $found = [];
        $suspectTrails = [];

        foreach ($this->checks as $check) {
            foreach ($check->findings($incidentId) as $finding) {
                $found[] = new PlannedAction($check, $finding);

                if ($check instanceof DuplicateOrIncoherentLog) {
                    $suspectTrails[$finding->incidentId] = true;
                }
            }
        }

        return array_map(function (PlannedAction $action) use ($suspectTrails): PlannedAction {
            $finding = self::guarded($action->finding, isset($suspectTrails[$action->finding->incidentId]));

            return new PlannedAction(
                $action->check,
                $finding,
                $finding->fix instanceof FlagForReview ? FlagForReview::existingFor($finding) : null,
            );
        }, $found);
    }

    /** A reconcile is only safe on a trail that can be trusted: otherwise a person has to look at it. */
    private static function guarded(Finding $finding, bool $trailHasSuspectLogs): Finding
    {
        if (! $finding->fix instanceof ReconcileStatus || $finding->evidenceLogId === null) {
            return $finding;
        }

        $why = match (true) {
            StatusTrail::isFutureDated($finding->evidenceLogId) => sprintf(
                'El log #%d tiene fecha futura: uno añadido ahora quedaría antes que él, así que el historial no es fiable.',
                $finding->evidenceLogId,
            ),
            ! StatusTrail::startsWithCreated($finding->incidentId) => 'La historia no empieza por el log de apertura: no '.
                'hay una base fiable de la que deducir el paso que falta.',
            $trailHasSuspectLogs => 'La misma incidencia tiene un log duplicado o incoherente: hay que revisar su '.
                'historia entera antes de añadirle nada.',
            default => null,
        };

        return $why === null ? $finding : new Finding(
            checkKey: $finding->checkKey,
            incidentId: $finding->incidentId,
            incidentTitle: $finding->incidentTitle,
            evidenceLogId: $finding->evidenceLogId,
            detail: $finding->detail,
            fix: new FlagForReview($why),
        );
    }
}
