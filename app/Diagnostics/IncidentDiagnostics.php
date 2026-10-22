<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Checks\ConsistencyCheck;
use App\Diagnostics\Checks\DuplicateOrIncoherentLog;
use App\Diagnostics\Checks\ResolvedWithoutResolutionLog;
use App\Diagnostics\Checks\StatusDiffersFromLastLog;
use App\Diagnostics\Fixes\FlagForReview;
use App\Diagnostics\Fixes\ReconcileStatus;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

/**
 * Finds inconsistencies between incidents and their status trail and plans what to do with each one.
 *
 * The correction rule: a finding is fixed automatically only when
 *  1. the fix does not change the incident's status,
 *  2. nothing has to be invented (date, author or reason), and
 *  3. the gap is explained by a single transition allowed by the state machine that does not require a reason.
 * Everything else is flagged for manual review. Audit rows are never deleted or backdated, and incidents.status is
 * never changed from here: when the row and its trail disagree, nothing tells us which one is right.
 */
final class IncidentDiagnostics
{
    public const RULE = 'Solo se corrige automáticamente cuando (1) la corrección no cambia el estado de la incidencia, '.
        '(2) no hay que inventar fecha, autor ni motivo y (3) el hueco se explica con una única transición '.
        'permitida que no exige motivo. Todo lo demás se marca para revisión manual. Ningún log se borra ni se atrasa.';

    /** @var list<ConsistencyCheck> */
    private readonly array $checks;

    public function __construct()
    {
        $this->checks = [
            new ResolvedWithoutResolutionLog,
            new StatusDiffersFromLastLog,
            new DuplicateOrIncoherentLog(config()->integer('incidents.diagnostics.duplicate_window_seconds')),
        ];
    }

    /** @return list<ConsistencyCheck> */
    public function checks(): array
    {
        return $this->checks;
    }

    /** @return list<PlannedAction> */
    public function plan(): array
    {
        return array_map(fn (array $found): PlannedAction => new PlannedAction(
            $found[0],
            $found[1],
            $found[1]->fix instanceof FlagForReview ? FlagForReview::existingFor($found[1]) : null,
        ), $this->findings());
    }

    /**
     * Applies one planned action in its own transaction. The incident row is locked first and the finding is
     * re-checked under the lock, because someone may have changed the incident since the plan was made.
     */
    public function apply(PlannedAction $action): FixOutcome
    {
        return DB::transaction(function () use ($action): FixOutcome {
            $finding = $action->finding;
            $incident = Incident::query()->whereKey($finding->incidentId)->lockForUpdate()->first();

            // Loose comparison on purpose: the same finding means equal check, incident, evidence, detail and fix.
            if ($incident === null || ! in_array($finding, array_column($this->findings($incident->id), 1))) {
                return FixOutcome::NoLongerTrue;
            }

            $existing = $finding->fix instanceof FlagForReview ? FlagForReview::existingFor($finding) : null;

            if ($existing !== null) {
                return $existing->isVoided() ? FixOutcome::AlreadyReviewed : FixOutcome::AlreadyFlagged;
            }

            $finding->fix->apply($incident, $finding);

            return FixOutcome::Applied;
        });
    }

    /**
     * Every finding of every check, with automatic reconciliations the trail cannot back downgraded to a flag.
     * The re-check under the lock goes through here too, so it applies the same downgrades as the plan.
     *
     * @return list<array{ConsistencyCheck, Finding}>
     */
    private function findings(?int $incidentId = null): array
    {
        $found = [];
        $suspectTrails = [];

        foreach ($this->checks as $check) {
            foreach ($check->findings($incidentId) as $finding) {
                $found[] = [$check, $finding];

                if ($check instanceof DuplicateOrIncoherentLog) {
                    $suspectTrails[$finding->incidentId] = true;
                }
            }
        }

        return array_map(
            fn (array $pair): array => [$pair[0], self::guarded($pair[1], isset($suspectTrails[$pair[1]->incidentId]))],
            $found,
        );
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
            check: $finding->check,
            incidentId: $finding->incidentId,
            incidentTitle: $finding->incidentTitle,
            evidenceLogId: $finding->evidenceLogId,
            detail: $finding->detail,
            fix: new FlagForReview($why),
        );
    }
}
