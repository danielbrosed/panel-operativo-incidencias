<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Fixes\Fix;

/** One inconsistency found by a check, with the fix the diagnostic proposes for it. */
final readonly class Finding
{
    /**
     * @param  string  $check  key of the check that found it
     * @param  int|null  $evidenceLogId  the incident_logs row that shows the problem (null: the incident has no trail)
     * @param  string  $detail  what is wrong, in Spanish
     */
    public function __construct(
        public string $check,
        public int $incidentId,
        public string $incidentTitle,
        public ?int $evidenceLogId,
        public string $detail,
        public Fix $fix,
    ) {}
}
