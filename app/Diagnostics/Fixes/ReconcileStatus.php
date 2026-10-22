<?php

declare(strict_types=1);

namespace App\Diagnostics\Fixes;

use App\Data\Actor;
use App\Diagnostics\Finding;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;

/**
 * Writes the single missing step as a status_reconciled entry, dated when the fix runs and signed by the diagnostic
 * because the real date and author are unknown; metadata.inferred says so.
 */
final readonly class ReconcileStatus implements Fix
{
    public function __construct(
        public IncidentStatus $from,
        public IncidentStatus $to,
    ) {}

    public function isAutomatic(): bool
    {
        return true;
    }

    public function describe(): string
    {
        return sprintf(
            'Automática. Se añade un log status_reconciled «%s» → «%s» con la fecha de la corrección (inferred): '.
            'es el único paso posible y no exige motivo.',
            $this->from->lowerLabel(),
            $this->to->lowerLabel(),
        );
    }

    public function appliedMessage(): string
    {
        return sprintf('corregida, añadido el paso «%s» → «%s»', $this->from->lowerLabel(), $this->to->lowerLabel());
    }

    public function apply(Incident $incident, Finding $finding): void
    {
        $incident->logs()->create([
            'action' => IncidentLogAction::StatusReconciled,
            'old_value' => $this->from->value,
            'new_value' => $this->to->value,
            'user_name' => Actor::system()->name,
            'metadata' => [
                'check' => $finding->checkKey,
                'inferred' => true,
                'evidence_log_id' => $finding->evidenceLogId,
                'detail' => 'Fecha y autor reales desconocidos: se registra la fecha de la corrección.',
            ],
        ]);
    }
}
