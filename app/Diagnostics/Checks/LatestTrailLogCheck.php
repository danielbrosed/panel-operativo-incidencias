<?php

declare(strict_types=1);

namespace App\Diagnostics\Checks;

use App\Diagnostics\Finding;
use App\Diagnostics\Fixes\Fix;
use App\Diagnostics\Fixes\FlagForReview;
use App\Diagnostics\Fixes\ReconcileStatus;
use App\Enums\IncidentStatus;
use stdClass;

/**
 * Compares each incident's status with the latest active entry of its status trail. Checks 1 and 2 share this
 * query and fix policy; they only split incidents by status, so one incident never shows up in both.
 */
abstract class LatestTrailLogCheck extends SqlCheck
{
    /** WHERE clause over `i` (the incident) and `t` (its latest trail entry, all NULL when it has none). */
    abstract protected function mismatch(): string;

    public function sql(): string
    {
        $trailActions = self::trailActions();

        return <<<SQL
            WITH last_trail AS (
                SELECT l.incident_id, l.id AS log_id, l.new_value,
                    ROW_NUMBER() OVER (PARTITION BY l.incident_id ORDER BY l.created_at DESC, l.id DESC) AS rn
                FROM incident_logs l
                WHERE l.voided_at IS NULL
                    AND l.action IN ({$trailActions})
            )
            SELECT i.id AS incident_id, i.title, i.status, t.log_id AS last_log_id, t.new_value AS logged_status
            FROM incidents i
            LEFT JOIN last_trail t ON t.incident_id = i.id AND t.rn = 1
            WHERE {$this->mismatch()}
            ORDER BY i.id
            SQL;
    }

    protected function toFinding(stdClass $row): Finding
    {
        $current = IncidentStatus::from($row->status);
        $evidenceLogId = $row->last_log_id === null ? null : (int) $row->last_log_id;

        return new Finding(
            check: $this->key(),
            incidentId: (int) $row->incident_id,
            incidentTitle: $row->title,
            evidenceLogId: $evidenceLogId,
            detail: $evidenceLogId === null
                ? sprintf('Está «%s», pero no tiene ningún log de estado activo.', self::label($current->value))
                : sprintf(
                    'Está «%s», pero su último log de estado (#%d) la deja «%s».',
                    self::label($current->value),
                    $evidenceLogId,
                    self::label($row->logged_status),
                ),
            fix: self::fixFor($evidenceLogId, IncidentStatus::tryFrom((string) $row->logged_status), $current),
        );
    }

    /** The correction rule (see App\Diagnostics\IncidentDiagnostics): one allowed step without reason, or a person. */
    private static function fixFor(?int $evidenceLogId, ?IncidentStatus $logged, IncidentStatus $current): Fix
    {
        if ($logged !== null && $logged->canTransitionTo($current) && ! $logged->requiresReason($current)) {
            return new ReconcileStatus($logged, $current);
        }

        return new FlagForReview(match (true) {
            $evidenceLogId === null => 'No hay ningún log del que partir: habría que inventar cómo y cuándo llegó a este estado.',
            $logged === null => 'El último log no tiene un estado válido: no se puede deducir el paso que falta.',
            ! $logged->canTransitionTo($current) => sprintf(
                'De «%s» a «%s» no hay un paso permitido: faltan pasos y no se sabe cuándo ni quién los dio.',
                self::label($logged->value),
                self::label($current->value),
            ),
            default => sprintf(
                'Pasar de «%s» a «%s» exige un motivo que no consta en ningún sitio.',
                self::label($logged->value),
                self::label($current->value),
            ),
        });
    }
}
