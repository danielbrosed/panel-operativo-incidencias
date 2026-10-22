<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Enums\IncidentStatus;

final class ReasonRequired extends IncidentRuleViolation
{
    public static function for(IncidentStatus $from, IncidentStatus $to): self
    {
        return new self(sprintf(
            'Indica el motivo para %s la incidencia: queda en su historial.',
            mb_strtolower($from->actionLabel($to)),
        ));
    }

    public function field(): string
    {
        return 'reason';
    }
}
