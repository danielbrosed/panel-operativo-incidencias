<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Diagnostics\Finding;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Runs the check's SQL and turns each row into a Finding. */
abstract class SqlCheck implements ConsistencyCheck
{
    abstract protected function toFinding(stdClass $row): Finding;

    final public function findings(?int $incidentId = null): array
    {
        // The re-verification wraps the very same query, so the check and its re-check can never disagree.
        /** @var list<stdClass> $rows */
        $rows = $incidentId === null
            ? DB::select($this->sql())
            : DB::select("SELECT * FROM (\n{$this->sql()}\n) AS finding WHERE finding.incident_id = ?", [$incidentId]);

        return array_map($this->toFinding(...), $rows);
    }

    /**
     * Quoted SQL literals. Only for values that come from the enums, never from user input: interpolating them
     * keeps the printed query complete and in step with the state machine.
     */
    protected static function literal(string ...$values): string
    {
        return implode(', ', array_map(fn (string $value): string => "'{$value}'", $values));
    }

    protected static function trailActions(): string
    {
        return self::literal(...IncidentLogAction::statusTrailValues());
    }

    /** Lower-case status label for messages; an unknown value is shown as stored. */
    protected static function label(?string $status): string
    {
        if ($status === null) {
            return 'sin valor';
        }

        return mb_strtolower(IncidentStatus::tryFrom($status)?->label() ?? $status);
    }
}
