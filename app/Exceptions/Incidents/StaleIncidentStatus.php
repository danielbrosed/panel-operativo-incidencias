<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Enums\IncidentStatus;

/** Someone else changed the status in the meantime: applying the request would overwrite their decision. */
final class StaleIncidentStatus extends IncidentRuleViolation
{
    public static function for(IncidentStatus $current): self
    {
        return new self(sprintf(
            'Otra persona ha cambiado esta incidencia a «%s» mientras la mirabas. Revisa su estado actual antes de seguir.',
            $current->lowerLabel(),
        ));
    }

    public function field(): string
    {
        return 'status';
    }
}
