<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Enums\IncidentStatus;

final class ResolvedWithoutResolutionLog extends LatestTrailLogCheck
{
    public function key(): string
    {
        return 'resolved_without_resolution_log';
    }

    public function title(): string
    {
        return 'Incidencias resueltas sin log de resolución';
    }

    public function rationale(): string
    {
        return 'Una incidencia solo llega a «resuelta» con ChangeIncidentStatus, que en la misma transacción escribe '.
            "un log con new_value = 'resolved'. Por tanto, en una incidencia resuelta el último log de estado activo ".
            '(created, status_changed, reopened o status_reconciled, sin anular, el más reciente por created_at e id) '.
            "tiene que decir 'resolved'. ROW_NUMBER() se queda con ese último log de cada incidencia y el LEFT JOIN ".
            'conserva también las que no tienen ninguno (new_value llega como NULL). Si falta o dice otra cosa, la '.
            'incidencia se resolvió por fuera de la aplicación y ese paso no está en la trazabilidad.';
    }

    protected function mismatch(): string
    {
        $resolved = self::literal(IncidentStatus::Resolved->value);

        return "i.status = {$resolved}\n    AND (t.new_value IS NULL OR t.new_value <> {$resolved})";
    }
}
