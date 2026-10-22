<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Enums\IncidentStatus;

/**
 * The person acted on a status that is no longer current (someone else changed it in the meantime).
 * Applying the request anyway would silently overwrite the other person's decision.
 */
final class StaleIncidentStatus extends IncidentDomainException
{
    public static function for(IncidentStatus $current): self
    {
        return new self(sprintf(
            'Otra persona ha cambiado esta incidencia a «%s» mientras la mirabas. Revisa su estado actual antes de seguir.',
            mb_strtolower($current->label()),
        ));
    }

    public function field(): string
    {
        return 'status';
    }

    public function isConflict(): bool
    {
        return true;
    }
}
