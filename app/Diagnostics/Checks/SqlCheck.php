<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Diagnostics\Finding;
use App\Enums\IncidentLogAction;
use Illuminate\Support\Facades\DB;
use stdClass;

/** One SQL query that detects one kind of inconsistency between incidents and their status trail. */
abstract class SqlCheck
{
    /** Stable identifier, stored in metadata.check of the logs the diagnostic writes. */
    abstract public function key(): string;

    abstract public function title(): string;

    /** Why the query detects the problem, in one paragraph. Printed with -v. */
    abstract public function rationale(): string;

    /** The exact SQL that findings() runs. Printed with -v. */
    abstract public function sql(): string;

    abstract protected function toFinding(stdClass $row): Finding;

    /**
     * Every finding, or only those of one incident (the re-check under the row lock). The re-check wraps the very
     * same query, so the check and its re-check can never disagree.
     *
     * @return list<Finding>
     */
    final public function findings(?int $incidentId = null): array
    {
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
}
