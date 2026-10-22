<?php

declare(strict_types=1);

namespace App\Diagnostics\Fixes;

use App\Diagnostics\Finding;
use App\Models\Incident;

/** What `incidents:diagnose --fix` does with a finding. None of them changes incidents.status. */
interface Fix
{
    /** True when it corrects the data on its own; false when it only flags the incident for a person. */
    public function isAutomatic(): bool;

    /** The "Acción prevista" column of the report, in Spanish. */
    public function describe(): string;

    /** Called inside the finding's transaction, with the incident row locked and the finding re-verified. */
    public function apply(Incident $incident, Finding $finding): void;
}
