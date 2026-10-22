<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Checks\ConsistencyCheck;
use App\Diagnostics\Checks\DuplicateOrIncoherentLog;
use App\Diagnostics\Checks\ResolvedWithoutResolutionLog;
use App\Diagnostics\Checks\StatusDiffersFromLastLog;
use App\Diagnostics\Fixes\FlagForReview;
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
        $plan = [];
        foreach ($this->checks as $check) {
            foreach ($check->findings() as $finding) {
                $flag = $finding->fix instanceof FlagForReview ? FlagForReview::existingFor($finding) : null;
                $plan[] = new PlannedAction($check, $finding, $flag?->created_at);
            }
        }

        return $plan;
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
            if ($incident === null || ! in_array($finding, $action->check->findings($incident->id))) {
                return FixOutcome::NoLongerTrue;
            }

            if ($finding->fix instanceof FlagForReview && FlagForReview::existingFor($finding) !== null) {
                return FixOutcome::AlreadyFlagged;
            }

            $finding->fix->apply($incident, $finding);

            return FixOutcome::Applied;
        });
    }
}
