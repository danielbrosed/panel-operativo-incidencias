<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Enums\IncidentStatus;

final class StatusDiffersFromLastLog extends LatestTrailLogCheck
{
    public function key(): string
    {
        return 'status_differs_from_last_log';
    }

    public function title(): string
    {
        return 'Incidencias cuyo estado no coincide con su último log';
    }

    public function rationale(): string
    {
        return 'El estado y su log se guardan juntos en una transacción, así que el último log de estado activo '.
            'tiene que dejar la incidencia en el estado que tiene ahora. Si no coincide, alguien cambió el estado o '.
            'el historial por fuera de la aplicación. Es la misma consulta que la comprobación anterior para el '.
            'resto de estados: las resueltas se excluyen porque son de esa, y así una incidencia nunca sale en las '.
            'dos. Una incidencia sin ningún log de estado también aparece, porque new_value llega como NULL.';
    }

    protected function mismatch(): string
    {
        $resolved = self::literal(IncidentStatus::Resolved->value);

        return "i.status <> {$resolved}\n    AND (t.new_value IS NULL OR t.new_value <> i.status)";
    }
}
