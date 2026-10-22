<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Diagnostics\Finding;

/** One SQL query that detects one kind of inconsistency between incidents and their status trail. */
interface ConsistencyCheck
{
    /** Stable identifier, stored in metadata.check of the logs the diagnostic writes. */
    public function key(): string;

    public function title(): string;

    /** Why the query detects the problem, in one paragraph. Printed with -v and copied into the README. */
    public function rationale(): string;

    /** The exact SQL that findings() runs. */
    public function sql(): string;

    /**
     * Every finding, or only those of one incident (used to re-verify a finding under the row lock).
     *
     * @return list<Finding>
     */
    public function findings(?int $incidentId = null): array;
}
