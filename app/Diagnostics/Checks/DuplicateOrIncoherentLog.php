<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Diagnostics\Finding;
use App\Diagnostics\Fixes\FlagForReview;
use App\Diagnostics\Fixes\VoidDuplicateLog;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use stdClass;

final class DuplicateOrIncoherentLog extends SqlCheck
{
    private const DUPLICATE = 'duplicate';

    private const BROKEN_CHAIN = 'broken_chain';

    /** @param  int  $windowSeconds  two identical consecutive entries this close are one request processed twice */
    public function __construct(private readonly int $windowSeconds) {}

    public function key(): string
    {
        return 'duplicate_or_incoherent_log';
    }

    public function title(): string
    {
        return 'Logs de estado duplicados o incoherentes';
    }

    public function rationale(): string
    {
        return 'LAG() pone junto a cada log de estado activo el anterior de la misma incidencia, por created_at e id. '.
            'Es un duplicado si repite acción, valores y autor en '.$this->windowSeconds.' s o menos: la misma '.
            'petición procesada dos veces, como un doble clic. Es una cadena rota si su old_value no es el new_value '.
            'del log anterior (falta un paso) o si el par (old_value, new_value) no es una transición permitida por '.
            'IncidentStatus; si la historia no empieza por created, su primer log sale así porque no tiene anterior. '.
            'Un duplicado también rompe la cadena, pero se clasifica como duplicado porque se sabe qué pasó. Límites: '.
            'el log created abre la cadena y no se compara con nada, así que no se detecta un created repetido más '.
            'adelante ni uno que no deje la incidencia en open; un mismo cambio repetido fuera de la ventana sale como '.
            'cadena rota y va a revisión manual; y el orden lo da created_at, de modo que un log insertado con fecha '.
            'atrasada se evalúa en la posición de esa fecha.';
    }

    public function sql(): string
    {
        $trailActions = self::trailActions();
        $created = self::literal(IncidentLogAction::Created->value);
        $allowed = self::allowedTransitions();
        $duplicate = self::literal(self::DUPLICATE);
        $brokenChain = self::literal(self::BROKEN_CHAIN);

        return <<<SQL
            WITH trail AS (
                SELECT l.id, l.incident_id, l.action, l.old_value, l.new_value, l.user_name, l.created_at,
                    LAG(l.id) OVER w AS previous_id,
                    LAG(l.action) OVER w AS previous_action,
                    LAG(l.old_value) OVER w AS previous_old_value,
                    LAG(l.new_value) OVER w AS previous_new_value,
                    LAG(l.user_name) OVER w AS previous_user_name,
                    TIMESTAMPDIFF(SECOND, LAG(l.created_at) OVER w, l.created_at) AS seconds_since_previous
                FROM incident_logs l
                WHERE l.voided_at IS NULL
                    AND l.action IN ({$trailActions})
                WINDOW w AS (PARTITION BY l.incident_id ORDER BY l.created_at, l.id)
            ),
            classified AS (
                SELECT t.*,
                    CASE
                        WHEN t.previous_id IS NOT NULL
                            AND t.action = t.previous_action
                            AND t.old_value <=> t.previous_old_value
                            AND t.new_value <=> t.previous_new_value
                            AND t.user_name = t.previous_user_name
                            AND t.seconds_since_previous <= {$this->windowSeconds}
                            THEN {$duplicate}
                        WHEN t.action <> {$created}
                            AND (NOT (t.old_value <=> t.previous_new_value)
                                OR ((t.old_value, t.new_value) IN ({$allowed})) IS NOT TRUE)
                            THEN {$brokenChain}
                    END AS kind
                FROM trail t
            )
            SELECT c.incident_id, i.title, c.kind, c.id AS log_id, c.action, c.old_value, c.new_value, c.user_name,
                c.previous_id AS previous_log_id, c.previous_new_value, c.seconds_since_previous
            FROM classified c
            JOIN incidents i ON i.id = c.incident_id
            WHERE c.kind IS NOT NULL
            ORDER BY c.incident_id, c.created_at, c.id
            SQL;
    }

    protected function toFinding(stdClass $row): Finding
    {
        $logId = (int) $row->log_id;
        $previousLogId = $row->previous_log_id === null ? null : (int) $row->previous_log_id;
        $step = sprintf('«%s» → «%s»', IncidentStatus::lowerLabelOf($row->old_value), IncidentStatus::lowerLabelOf($row->new_value));

        if ($row->kind === self::DUPLICATE && $previousLogId !== null) {
            return new Finding(
                checkKey: $this->key(),
                incidentId: (int) $row->incident_id,
                incidentTitle: $row->title,
                evidenceLogId: $logId,
                detail: sprintf(
                    'El log #%d repite el #%d (%s, %s) %d s después.',
                    $logId, $previousLogId, $step, $row->user_name, $row->seconds_since_previous,
                ),
                fix: new VoidDuplicateLog($logId, $previousLogId),
            );
        }

        return new Finding(
            checkKey: $this->key(),
            incidentId: (int) $row->incident_id,
            incidentTitle: $row->title,
            evidenceLogId: $logId,
            detail: match (true) {
                $previousLogId === null => sprintf('El log #%d (%s) es el primero de la incidencia y no es el de apertura.', $logId, $step),
                $row->old_value !== $row->previous_new_value => sprintf(
                    'El log #%d (%s) no sigue al anterior, el #%d, que la dejó «%s».',
                    $logId, $step, $previousLogId, IncidentStatus::lowerLabelOf($row->previous_new_value),
                ),
                default => sprintf('El log #%d registra %s, que no es una transición permitida.', $logId, $step),
            },
            fix: new FlagForReview('Falta al menos un paso de la historia y no se sabe cuándo ni quién lo dio.'),
        );
    }

    /** Every allowed (from, to) pair, straight from the state machine. */
    private static function allowedTransitions(): string
    {
        $pairs = [];
        foreach (IncidentStatus::cases() as $from) {
            foreach ($from->allowedTransitions() as $to) {
                $pairs[] = '('.self::literal($from->value, $to->value).')';
            }
        }

        return implode(', ', $pairs);
    }
}
