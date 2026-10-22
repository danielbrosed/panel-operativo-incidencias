<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Enums\IncidentStatus;

final class InvalidStatusTransition extends IncidentRuleViolation
{
    public static function between(IncidentStatus $from, IncidentStatus $to): self
    {
        if ($from === $to) {
            return new self(sprintf('La incidencia ya está «%s».', $from->lowerLabel()));
        }

        return new self(sprintf('Una incidencia «%s» no puede pasar a «%s».', $from->lowerLabel(), $to->lowerLabel()));
    }

    public function field(): string
    {
        return 'status';
    }
}
