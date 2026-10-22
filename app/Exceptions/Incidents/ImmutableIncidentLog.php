<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Models\IncidentLog;
use LogicException;

/** A programming error, not a user error: code tried to rewrite the audit trail. */
final class ImmutableIncidentLog extends LogicException
{
    public static function cannotUpdate(IncidentLog $log): self
    {
        return new self("incident_logs #{$log->id} is append-only: it can only be voided once, never edited.");
    }

    public static function cannotDelete(IncidentLog $log): self
    {
        return new self("incident_logs #{$log->id} is append-only: void it with a reason instead of deleting it.");
    }
}
