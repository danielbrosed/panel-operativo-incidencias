<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use App\Enums\IncidentStatus;

final class InvalidStatusTransition extends IncidentDomainException
{
    public static function between(IncidentStatus $from, IncidentStatus $to): self
    {
        if ($from === $to) {
            return new self(sprintf('La incidencia ya está «%s».', mb_strtolower($from->label())));
        }

        return new self(sprintf(
            'Una incidencia «%s» no puede pasar a «%s».',
            mb_strtolower($from->label()),
            mb_strtolower($to->label()),
        ));
    }

    public function field(): string
    {
        return 'status';
    }
}
